<?php

namespace app\components;

use Yii;
use app\models\Order;
use app\models\OrderItem;

/**
 * Keeps one sale one bill when a POS client sends its checkout twice.
 *
 * Used by the checkout actions of the API: item/order, item/ordertest and
 * item/punchorder. The .NET application and the Android APK resend a checkout
 * whenever the first answer is not a clean OK, and the cashier rebills when the
 * till shows an error. Two things turned one sale into two bills:
 *
 *  - The sale committed and then a later step - the bill number, the webshop
 *    callback, the SMS, the tax summary - threw. The action's catch rolled back
 *    a transaction that had already committed, which in Yii 1 throws again out
 *    of the catch (HTTP 500) and in the port answered NOK. The sale was in the
 *    books, the till said it had failed. recover() and envelope() make that
 *    answer the order that was saved.
 *
 *  - An exact repeat of a request, a retry or a double tap, was billed again.
 *    The clients send no request key, so check() recognises a repeat by what it
 *    contains, under a named lock so that two copies arriving together cannot
 *    both miss each other.
 *
 * A client that does send a key - POST request_id or an X-Request-Id header -
 * gets hard idempotency through tbl_order_request instead (see
 * db_changes/2026-09-27-order-request.sql). Before that table exists the key is
 * ignored and the content check applies.
 *
 * Yii 1 has the same class in protected/components/OrderDedupe.php. The lock
 * names, the window and the matching rules are the same on purpose: a retry
 * that lands on the other stack is caught as well.
 *
 * A retry that comes later than that - the till waited its full 100 seconds,
 * or showed an HTTP 500, kept the cart, and the cashier pressed Save again a
 * minute later - is recognised by whether the first answer reached the till.
 * Every sale checked by content is recorded in tbl_order_attempt when it
 * commits, and at the end of the request marked delivered or not (finish()).
 * An identical request within RETRY_WINDOW_SECONDS of one whose answer was not
 * delivered is answered with that order; one whose answer was delivered is a
 * new sale. See db_changes/2026-09-29-order-attempt.sql; before that table
 * exists this part is off and nothing else changes.
 *
 * Nothing in here may stop a sale. Every failure of the check itself is logged
 * and the checkout goes ahead as it did before.
 */
class OrderDedupe
{
	/**
	 * How long after a sale an identical request counts as a repeat of it, in
	 * seconds, measured on the database clock (create_time is NOW()).
	 *
	 * The trade-off: a repeat is recognised by cashier, customer, payment mode,
	 * total and the exact lines. A genuine second sale that matches all of those
	 * within the window - the same cashier ringing up the same basket for the
	 * same customer again - would be answered with the first bill and not
	 * recorded. That is not a realistic till rhythm at ten seconds; a client
	 * retrying after an error or a timeout, which is what this is for, is. A
	 * longer window catches slower retries and widens the other risk, so keep it
	 * short. Walk-in sales (no customer) share one customer value, so a busy
	 * counter selling one identical item twice within ten seconds is the case to
	 * watch if this is ever raised.
	 */
	const WINDOW_SECONDS = 10;

	/** How long a request waits for an identical one in flight, in seconds. */
	const LOCK_WAIT_SECONDS = 10;

	/**
	 * How long after a sale whose answer did not reach the till an identical
	 * request is taken for the cashier's retry of it, in seconds.
	 *
	 * Only an undelivered answer opens this window, so a cashier ringing up the
	 * same basket again after a sale that went through is a new sale, as before.
	 * The trade-off is the other way round: the till gave up on a sale (or showed
	 * an error for it) that nevertheless went into the books, the cashier did not
	 * press Save again, and within this time sells the identical basket to the
	 * same customer (walk-ins count as one) with the same payment mode. That sale
	 * is answered with the first bill, which is in the books already; the books
	 * show one sale where there were two attempts at one.
	 */
	const RETRY_WINDOW_SECONDS = 600;

	/**
	 * An answer handed over this long after the request started is counted as
	 * not delivered. The .NET till's WebClient gives up after 100 seconds and
	 * shows an error; this is kept below that, so an answer that may have come
	 * after the till stopped listening counts as lost. (An answer the till did
	 * get between 90 and 100 seconds therefore opens the retry window above.)
	 */
	const DELIVERY_LIMIT_SECONDS = 90;

	/**
	 * How long a retry waits for an identical sale still being processed - the
	 * till gave up, the server did not - to finish, in seconds.
	 */
	const WAIT_FOR_PENDING_SECONDS = 30;

	/** tbl_order_attempt.status */
	const ATTEMPT_PENDING = 0;       // committed, the answer not sent yet
	const ATTEMPT_DELIVERED = 1;
	const ATTEMPT_NOT_DELIVERED = 2;

	private static $lock = null;
	private static $requestId = null;
	private static $requestTableUsable = true;
	private static $shutdownRegistered = false;

	private static $fingerprint = null;      // md5 of the content key, for a sale checked by content
	private static $userId = null;
	private static $attemptTableUsable = true;
	private static $attemptId = null;        // this request's sale, recorded by committed()
	private static $answerAttemptId = null;  // the earlier sale this request answers with
	private static $answeredOk = false;      // the action's answer was the success envelope
	private static $finishRegistered = false;

	/**
	 * Called before the order is created. Returns the Order this request
	 * repeats - the caller answers with envelope() and creates nothing - or null
	 * to go ahead. When it returns null it may hold a named lock, released by
	 * release() once the bill number is written, or at the end of the request
	 * whatever way it ends.
	 *
	 * @param mixed $loginid the userlogin header, as stored in create_user_id
	 * @param array $post    the request
	 * @param array $lines   the basket, when the action prices it itself
	 *                       (punchorder); null reads $post['item_details']
	 * @param mixed $total   the order total the action stores; null reads
	 *                       $post['total_amt']
	 */
	public static function check($loginid, $post, $lines = null, $total = null)
	{
		self::$requestId = null;
		self::$fingerprint = null;
		// A till that gives up waiting closes the connection. PHP notices that
		// only when it writes, but when it does it must not stop there: the sale
		// is committed by then, and finish() still has to record that its answer
		// was lost. Nothing here writes output before the answer, so this changes
		// nothing else.
		ignore_user_abort(true);
		// Sales only. Holds (status 2) are drafts; a repeated hold is not a bill.
		if (!isset($post['status_id']) || $post['status_id'] != '1') {
			return null;
		}
		try {
			$requestId = self::requestId($post);
			if ($requestId !== null) {
				$existing = self::orderForRequest($requestId);
				if ($existing !== null) {
					return self::repeatOf($existing, 'request_id ' . $requestId);
				}
				if (!self::$requestTableUsable) {
					$requestId = null;
				}
			}
			$onlineId = self::onlineOrderId($post);

			$customer = isset($post['customer_id']) ? trim((string)$post['customer_id']) : null;
			$payment = isset($post['mode_of_payment']) ? trim((string)$post['mode_of_payment']) : '';
			if ($total === null) {
				$total = isset($post['total_amt']) ? $post['total_amt'] : 0;
			}
			$total = self::money($total);
			$basket = null;

			// One sale, one lock name. An online order is identified by its id,
			// a keyed request by its key, anything else by its contents.
			if ($onlineId !== null) {
				$key = 'online|' . $onlineId;
			} elseif ($requestId !== null) {
				$key = 'req|' . $requestId;
			} else {
				if ($lines === null) {
					$lines = isset($post['item_details']) ? json_decode($post['item_details']) : null;
				}
				$basket = self::basket($lines);
				if ($basket === null) {
					return null;
				}
				$key = 'fp|' . $loginid . '|' . $customer . '|' . $payment . '|' . $total . '|' . implode(';', $basket);
			}
			self::acquire('pos_ord_' . md5($key));

			$existing = null;
			if ($requestId !== null) {
				$existing = self::orderForRequest($requestId);
				if ($existing !== null) {
					return self::repeatOf($existing, 'request_id ' . $requestId);
				}
				if (self::$requestTableUsable) {
					self::$requestId = $requestId;
				}
			}
			if ($onlineId !== null) {
				// An online order is billed once: the application already reads an
				// order carrying its id as "billed" (OnlineOrder::toArray()).
				$id = Yii::$app->db->createCommand('SELECT id FROM tbl_order WHERE online_order_id = :o ORDER BY id ASC LIMIT 1', [':o' => $onlineId])->queryScalar();
				if ($id) {
					return self::repeatOf($id, 'online order ' . $onlineId);
				}
			} elseif (self::$requestId === null) {
				self::$fingerprint = md5($key);
				self::$userId = (int)$loginid;
				$attempt = self::latestAttempt();
				$id = self::recentRepeat($loginid, $customer, $payment, $total, $basket);
				if ($id) {
					return self::answerWith($id, 'an identical request within ' . self::WINDOW_SECONDS . 's',
						$attempt !== null && $attempt['order_id'] == $id ? $attempt : null);
				}
				if ($attempt !== null) {
					if ($attempt['status'] == self::ATTEMPT_PENDING && !$attempt['stale']) {
						// The same sale is still being processed: the till gave up on it,
						// the server did not. Its answer decides what this request is.
						$attempt = self::waitForPending($attempt);
						if ($attempt['status'] == self::ATTEMPT_DELIVERED) {
							// Its answer reached a client after all, so that client has
							// the bill. The identical request is a new sale unless the
							// short window says otherwise.
							$id = self::recentRepeat($loginid, $customer, $payment, $total, $basket);
							if ($id) {
								return self::answerWith($id, 'an identical request within ' . self::WINDOW_SECONDS . 's', null);
							}
							return null;
						}
					}
					if ($attempt['status'] != self::ATTEMPT_DELIVERED) {
						// Not delivered, or still unanswered: nobody has this bill yet.
						return self::answerWith($attempt['order_id'], 'a retry within ' . self::RETRY_WINDOW_SECONDS
							. 's of a sale whose answer did not reach the till (attempt ' . $attempt['id'] . ', status '
							. $attempt['status'] . ($attempt['stale'] ? ', past ' . self::DELIVERY_LIMIT_SECONDS . 's' : '') . ')', $attempt);
					}
				}
			}
		} catch (\Exception $e) {
			Yii::error('OrderDedupe::check skipped: ' . $e->getMessage(), __METHOD__);
		}
		return null;
	}

	/**
	 * Inside the order's transaction, just before the commit: records the
	 * request key against the order. A second request with the same key that
	 * got this far - the lock timed out, say - fails here on the unique key,
	 * rolls back, and recover() answers it with the order that won.
	 */
	public static function remember($orderId)
	{
		if (self::$requestId === null) {
			return;
		}
		try {
			Yii::$app->db->createCommand('INSERT INTO tbl_order_request (request_id, order_id, create_time) VALUES (:r, :o, NOW())', [':r' => self::$requestId, ':o' => $orderId])->execute();
		} catch (\Exception $e) {
			if (self::sqlError($e) == 1062) {
				throw $e;
			}
			// the mapping is a convenience; failing to write it must not fail the sale
			Yii::error('OrderDedupe::remember: ' . $e->getMessage(), __METHOD__);
		}
	}

	/**
	 * Just after the order's commit: records the sale as committed with its
	 * answer still to come, so that a retry arriving meanwhile waits for it, and
	 * lets finish() mark it at the end of the request. Only for a sale checked
	 * by content (check() set the fingerprint).
	 */
	public static function committed($orderId)
	{
		if (self::$fingerprint === null || !self::$attemptTableUsable) {
			return;
		}
		try {
			// started_at is when the request started, on the database clock
			$db = Yii::$app->db;
			$db->createCommand('INSERT INTO tbl_order_attempt (fingerprint, create_user_id, order_id, status, started_at)'
				. ' VALUES (:f, :u, :o, ' . self::ATTEMPT_PENDING . ', NOW(3) - INTERVAL ' . self::elapsedMicroseconds() . ' MICROSECOND)',
				[':f' => self::$fingerprint, ':u' => self::$userId, ':o' => $orderId])->execute();
			self::$attemptId = $db->getLastInsertID();
			self::registerFinish();
		} catch (\Exception $e) {
			self::attemptTableFailed($e, 'committed');
		}
	}

	/**
	 * Returns the action's answer unchanged, noting whether it is the success
	 * envelope. finish() counts only that as a delivered sale: a NOK after the
	 * commit - an order saved without a bill number, say - leaves the till
	 * showing an error with the cart still on it.
	 */
	public static function answering($arr)
	{
		self::$answeredOk = is_array($arr) && isset($arr['status']) && $arr['status'] === 'OK';
		return $arr;
	}

	/**
	 * At the end of the request, whatever way it ends - a fatal error runs
	 * shutdown functions too (Yii 1 needs it because its actions exit; kept the
	 * same here). Marks this request's sale delivered or not, and marks the
	 * earlier sale it answered with delivered once that answer has reached the
	 * till.
	 */
	public static function finish()
	{
		if (self::$attemptId === null && self::$answerAttemptId === null) {
			return;
		}
		$delivered = self::delivered();
		try {
			$db = Yii::$app->db;
			if (self::$attemptId !== null) {
				// Only from pending: a retry may already have delivered it.
				$db->createCommand('UPDATE tbl_order_attempt SET status = :s, finished_at = NOW(3) WHERE id = :id AND status = ' . self::ATTEMPT_PENDING,
					[':s' => $delivered ? self::ATTEMPT_DELIVERED : self::ATTEMPT_NOT_DELIVERED, ':id' => self::$attemptId])->execute();
			}
			if (self::$answerAttemptId !== null && $delivered) {
				$db->createCommand('UPDATE tbl_order_attempt SET status = ' . self::ATTEMPT_DELIVERED . ', finished_at = NOW(3) WHERE id = :id',
					[':id' => self::$answerAttemptId])->execute();
			}
		} catch (\Exception $e) {
			Yii::error('OrderDedupe::finish: ' . $e->getMessage(), __METHOD__);
		}
	}

	/** Lets an identical request waiting on the lock go ahead. Safe to call twice. */
	public static function release()
	{
		if (self::$lock === null) {
			return;
		}
		$name = self::$lock;
		self::$lock = null;
		try {
			Yii::$app->db->createCommand('SELECT RELEASE_LOCK(:n)', [':n' => $name])->queryScalar();
		} catch (\Exception $e) {
			// the lock goes with the connection at the end of the request anyway
		}
	}

	/**
	 * For the checkout's catch. If the transaction is still open, the sale did
	 * not happen: roll back as before, and return the order that won the request
	 * key if that is why it failed, else null. If it is no longer open, the sale
	 * committed and something after the commit threw: log it and return the
	 * saved order, so the caller answers success - rolling back here is what
	 * used to turn a saved sale into an error. The row is read back first, so a
	 * commit that itself failed still answers as a failure.
	 */
	public static function recover($transaction, $e, $order, $what)
	{
		if ($transaction->getIsActive()) {
			Yii::error($what . ' rolled back: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine(), __METHOD__);
			$transaction->rollBack();
			$won = null;
			if (self::$requestId !== null) {
				try {
					$won = self::orderForRequest(self::$requestId);
				} catch (\Exception $ignored) {
				}
				if ($won !== null) {
					$won = Order::findOne($won);
					Yii::warning($what . ': request_id ' . self::$requestId . ' was billed by a concurrent request; answering with order ' . ($won ? $won->id : '?'), __METHOD__);
				}
			}
			self::release();
			return $won ? $won : null;
		}
		$saved = null;
		try {
			$saved = $order::findOne($order->id);
		} catch (\Exception $ignored) {
		}
		Yii::error($what . ': ' . ($saved ? 'order ' . $order->id . ' was committed' : 'the commit did not persist')
			. ', then failed: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine()
			. ($saved ? ' - answering with the saved order' : ''), __METHOD__);
		if ($saved instanceof Order && !$saved->bill_no) {
			// Answered anyway, with the blank number getOrderBillNo() gives: the
			// sale and its stock movement are committed, and a NOK would only get
			// it billed a second time. The row needs numbering by hand.
			Yii::error('OrderDedupe: order ' . $saved->id . ' is saved without a bill number - the allocation after the commit failed', __METHOD__);
		}
		self::release();
		return $saved;
	}

	/**
	 * The answer the action gives for a saved order, key for key as its own
	 * success path builds it, so a repeat or a recovered failure looks to the
	 * client exactly like the first success.
	 *
	 * @param array  $arr    the response built so far
	 * @param mixed  $order  Order, or OrderHold for a held order
	 * @param string $flavour 'order', 'ordertest' or 'punchorder'
	 */
	public static function envelope($arr, $order, $flavour)
	{
		// what the action sends, as answering() would note it
		self::$answeredOk = true;
		$arr['status'] = 'OK';
		if ($flavour === 'punchorder') {
			// processOrder() returns the number itself, an integer
			$arr['bill_no'] = (int)$order->bill_no;
			return $arr;
		}
		if ($flavour === 'order') {
			// the insert id, a string, as the action emits it
			$arr['order_id'] = (string)$order->id;
		}
		if ($order instanceof Order) {
			try {
				$arr['bill_no'] = $order->getOrderBillNo();
			} catch (\Exception $e) {
				Yii::error('OrderDedupe::envelope bill_no: ' . $e->getMessage(), __METHOD__);
				$arr['bill_no'] = (string)$order->bill_no;
			}
			$taxarr = [];
			try {
				if ($flavour === 'order') {
					// as actionOrder: t.* first, then the SUMs - see the note there
					$items = OrderItem::find()
						->select('t.*, SUM(qty) AS qty, SUM(tax_amount) AS tax_amount, SUM(cgst_amt) AS cgst_amt,'
							. ' SUM(sgst_amt) AS sgst_amt, SUM(cess_amt) AS cess_amt, SUM(igst_amt) AS igst_amt')
						->alias('t')
						->joinWith('item item')
						->where(['t.order_id' => $order->id])
						->groupBy(['t.tax_id', 't.item_id', 'item.hsn_code'])
						->orderBy('t.tax_id, t.item_id, item.hsn_code')
						->all();
				} else {
					// as actionOrdertest: grouped by tax_id alone, no SUM()
					$items = OrderItem::find()
						->alias('t')
						->where(['t.order_id' => $order->id])
						->groupBy(['t.tax_id'])
						->orderBy('t.tax_id')
						->all();
				}
				foreach ($items as $item) {
					$taxarr[] = $item->getTaxApiArray();
				}
			} catch (\Exception $e) {
				// the sale stands without its tax summary
				Yii::error('OrderDedupe::envelope taxes for order ' . $order->id . ': ' . $e->getMessage(), __METHOD__);
				$taxarr = array();
			}
			$arr['taxes'] = $taxarr;
		} else {
			$arr['bill_no'] = '0';
		}
		$arr['message'] = 'Order is saved Successfully';
		return $arr;
	}

	private static function repeatOf($orderId, $why)
	{
		$order = Order::findOne($orderId);
		if ($order === null) {
			return null;
		}
		Yii::warning('Duplicate checkout suppressed: repeats order ' . $order->id . ' (bill ' . $order->bill_no . ') by ' . $why, __METHOD__);
		self::release();
		return $order;
	}

	/**
	 * repeatOf(), and when the answer is an earlier sale whose answer has not
	 * been delivered, has finish() mark that sale delivered by this one.
	 */
	private static function answerWith($orderId, $why, $attempt)
	{
		$order = self::repeatOf($orderId, $why);
		if ($order !== null && $attempt !== null && $attempt['status'] != self::ATTEMPT_DELIVERED) {
			self::$answerAttemptId = $attempt['id'];
			self::registerFinish();
		}
		return $order;
	}

	/**
	 * The most recent sale with this request's content by this cashier inside
	 * RETRY_WINDOW_SECONDS: id, order_id, status, and stale (started more than
	 * DELIVERY_LIMIT_SECONDS ago, so its answer can no longer count as
	 * delivered). Null if none, or if the table is not there.
	 */
	private static function latestAttempt()
	{
		if (!self::$attemptTableUsable) {
			return null;
		}
		try {
			$row = Yii::$app->db->createCommand('SELECT id, order_id, status,'
				. ' (started_at < NOW(3) - INTERVAL ' . (int)self::DELIVERY_LIMIT_SECONDS . ' SECOND) AS stale'
				. ' FROM tbl_order_attempt WHERE fingerprint = :f AND create_user_id = :u'
				. ' AND started_at >= NOW(3) - INTERVAL ' . (int)self::RETRY_WINDOW_SECONDS . ' SECOND'
				. ' ORDER BY id DESC LIMIT 1', [':f' => self::$fingerprint, ':u' => self::$userId])
				->queryOne();
			return $row ? $row : null;
		} catch (\Exception $e) {
			self::attemptTableFailed($e, 'latestAttempt');
			return null;
		}
	}

	/** Polls a pending sale until it is marked, is stale, or the wait is over. */
	private static function waitForPending($attempt)
	{
		$until = microtime(true) + self::WAIT_FOR_PENDING_SECONDS;
		while ($attempt['status'] == self::ATTEMPT_PENDING && !$attempt['stale'] && microtime(true) < $until) {
			usleep(500000);
			$row = Yii::$app->db->createCommand('SELECT status,'
				. ' (started_at < NOW(3) - INTERVAL ' . (int)self::DELIVERY_LIMIT_SECONDS . ' SECOND) AS stale'
				. ' FROM tbl_order_attempt WHERE id = :id', [':id' => $attempt['id']])
				->queryOne();
			if (!$row) {
				break;
			}
			$attempt['status'] = $row['status'];
			$attempt['stale'] = $row['stale'];
		}
		return $attempt;
	}

	/**
	 * Whether the answer reached the till: the success envelope, sent with a
	 * 2xx status, no fatal error, to a connection still open, inside
	 * DELIVERY_LIMIT_SECONDS. The answer is flushed first, so that a till that
	 * has gone away shows as an aborted connection; that is as good as the
	 * server can tell, and the time limit covers the till's own timeout.
	 */
	private static function delivered()
	{
		ignore_user_abort(true);
		while (ob_get_level() > 0) {
			if (!@ob_end_flush()) {
				break;
			}
		}
		flush();
		if (!self::$answeredOk) {
			return false;
		}
		$code = http_response_code();
		if (!is_int($code) || $code < 200 || $code > 299) {
			return false;
		}
		$error = error_get_last();
		if ($error !== null && ($error['type'] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR))) {
			return false;
		}
		if (connection_aborted()) {
			return false;
		}
		return self::elapsedMicroseconds() < self::DELIVERY_LIMIT_SECONDS * 1000000;
	}

	/** Microseconds since the request started. */
	private static function elapsedMicroseconds()
	{
		$start = isset($_SERVER['REQUEST_TIME_FLOAT']) ? $_SERVER['REQUEST_TIME_FLOAT'] : $_SERVER['REQUEST_TIME'];
		return max(0, (int)round((microtime(true) - $start) * 1000000));
	}

	private static function registerFinish()
	{
		if (!self::$finishRegistered) {
			self::$finishRegistered = true;
			register_shutdown_function([self::class, 'finish']);
		}
	}

	/** db_changes/2026-09-29-order-attempt.sql not applied: off for the rest of the request. */
	private static function attemptTableFailed($e, $where)
	{
		self::$attemptTableUsable = false;
		self::$fingerprint = null;
		if (self::sqlError($e) != 1146) {
			Yii::error('OrderDedupe::' . $where . ': tbl_order_attempt unusable: ' . $e->getMessage(), __METHOD__);
		}
	}

	/** The order id of the most recent identical sale inside the window, or null. */
	private static function recentRepeat($loginid, $customer, $payment, $total, $basket)
	{
		$db = Yii::$app->db;
		$rows = $db->createCommand('SELECT id, total_amt, mode_of_payment FROM tbl_order'
			. ' WHERE create_time >= NOW() - INTERVAL ' . (int)self::WINDOW_SECONDS . ' SECOND'
			. ' AND create_user_id = :u AND customer_id <=> :c ORDER BY id DESC', [':u' => $loginid, ':c' => $customer])
			->queryAll();
		foreach ($rows as $row) {
			if (self::money($row['total_amt']) !== $total || trim((string)$row['mode_of_payment']) !== $payment) {
				continue;
			}
			$lines = $db->createCommand('SELECT d.bar_code, oi.qty, oi.total_amt AS total_amount FROM tbl_order_item oi'
				. ' LEFT JOIN tbl_item_detail d ON d.id = oi.item_detail_id WHERE oi.order_id = :o', [':o' => $row['id']])
				->queryAll();
			if (self::basket($lines) === $basket) {
				return $row['id'];
			}
		}
		return null;
	}

	/**
	 * A basket as a sorted list of "barcode|qty|line total", or null if it has
	 * no lines. Posted lines are objects, computed and database lines arrays.
	 */
	private static function basket($lines)
	{
		if (!is_array($lines) || count($lines) === 0) {
			return null;
		}
		$out = array();
		foreach ($lines as $line) {
			$line = (array)$line;
			$barcode = isset($line['bar_code']) ? $line['bar_code'] : '';
			$qty = isset($line['qty']) ? $line['qty'] : 0;
			$amount = isset($line['total_amount']) ? $line['total_amount'] : 0;
			if (!is_scalar($barcode) || !is_scalar($qty) || !is_scalar($amount)) {
				return null;
			}
			$out[] = strtolower(trim((string)$barcode)) . '|' . sprintf('%.3f', (float)$qty) . '|' . self::money($amount);
		}
		sort($out, SORT_STRING);
		return $out;
	}

	private static function money($v)
	{
		return sprintf('%.2f', is_scalar($v) ? (float)$v : 0);
	}

	/** POST request_id, else the X-Request-Id header; null if absent or too long. */
	private static function requestId($post)
	{
		$rid = isset($post['request_id']) ? $post['request_id'] : Yii::$app->request->getHeaders()->get('X-Request-Id');
		if (!is_scalar($rid)) {
			return null;
		}
		$rid = trim((string)$rid);
		if ($rid === '' || strlen($rid) > 64) {
			return null;
		}
		return $rid;
	}

	/**
	 * The online order this sale bills, if it names a real one. Only a positive
	 * id with a row in tbl_online_order counts, so a client that sends 0 or a
	 * placeholder with every walk-in sale is not collapsed onto one order.
	 */
	private static function onlineOrderId($post)
	{
		if (!isset($post['online_order_id']) || !is_scalar($post['online_order_id'])) {
			return null;
		}
		$id = trim((string)$post['online_order_id']);
		if (!ctype_digit($id) || (int)$id <= 0) {
			return null;
		}
		$found = Yii::$app->db->createCommand('SELECT id FROM tbl_online_order WHERE id = :o', [':o' => (int)$id])->queryScalar();
		return $found ? (int)$id : null;
	}

	/** The order a request key was billed as, or null. Marks the table unusable if it is missing. */
	private static function orderForRequest($requestId)
	{
		if (!self::$requestTableUsable) {
			return null;
		}
		try {
			$id = Yii::$app->db->createCommand('SELECT order_id FROM tbl_order_request WHERE request_id = :r', [':r' => $requestId])->queryScalar();
			return $id ? $id : null;
		} catch (\Exception $e) {
			// db_changes/2026-09-27-order-request.sql not applied yet: fall back to
			// the content check for the rest of this request
			self::$requestTableUsable = false;
			if (self::sqlError($e) != 1146) {
				Yii::error('OrderDedupe: tbl_order_request unusable: ' . $e->getMessage(), __METHOD__);
			}
			return null;
		}
	}

	private static function acquire($name)
	{
		$got = Yii::$app->db->createCommand('SELECT GET_LOCK(:n, ' . (int)self::LOCK_WAIT_SECONDS . ')', [':n' => $name])->queryScalar();
		if ($got != 1) {
			// go ahead without it rather than refuse a sale; the lookup still runs
			Yii::warning('OrderDedupe: could not take ' . $name . ' in ' . self::LOCK_WAIT_SECONDS . 's; continuing without it', __METHOD__);
			return;
		}
		self::$lock = $name;
		if (!self::$shutdownRegistered) {
			// The actions return from a dozen places; a shutdown function covers
			// every way out, while the connection is still open. (Yii 1 needs it
			// because sendJSONResponse() exits; kept the same here.)
			self::$shutdownRegistered = true;
			register_shutdown_function([self::class, 'release']);
		}
	}

	/** The MySQL error number behind a database exception, or 0. */
	private static function sqlError($e)
	{
		if (isset($e->errorInfo) && is_array($e->errorInfo) && isset($e->errorInfo[1])) {
			return (int)$e->errorInfo[1];
		}
		if (preg_match('/\b(1062|1146)\b/', $e->getMessage(), $m)) {
			return (int)$m[1];
		}
		return 0;
	}
}
