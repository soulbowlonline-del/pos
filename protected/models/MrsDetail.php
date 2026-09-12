<?php

 
/**
 * @property integer $id
 * @property integer $req_qty
 * @property integer $approved_qty
 * @property integer $bal_qty
 * @property integer $status
 * @property integer $type_id
 * @property string $remarks
 * @property string $create_time
 * @property string $update_time
 * @property integer $create_user_id
 * @property integer $updated_by
 * @property integer $item_detail_id
 * @property integer $mrs_id
 * @property integer $outlet_id
 */
Yii::import('application.models._base.BaseMrsDetail');
class MrsDetail extends BaseMrsDetail
{
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}

	protected function beforeSave()
	{
			if (!parent::beforeSave()) {
					return false;
			}

			// Only calculate ai_qty for new records
			if ($this->isNewRecord && isset($this->item_id)) {
					$itemId = $this->item_id;
					$minQty = $this->min_qty ? $this->min_qty : 1;
					$baseQty = $this->req_qty > 0 ? $this->req_qty : 1;
					
					// Primary AI calculation using velocity and lead time
					$aiCalculatedQty = $this->calculateAIQty($itemId);
					
					// Secondary adjustment using velocity change data
					$chaosConstant = isset(Yii::app()->params['chaos_constant']) ? Yii::app()->params['chaos_constant'] : 20;
					$chaosConstantReduce = isset(Yii::app()->params['chaos_constant_reduce']) ? Yii::app()->params['chaos_constant_reduce'] : 10;
					
					// $velocityData = $this->getLatestVelocity($itemId);
					$aiCalculatedQty = $this->calculateAIQty($itemId);

					// if ($velocityData && isset($velocityData['velocity_change_percent'])) {
					// 		$velocityPercent = $velocityData['velocity_change_percent'];
							
					// 		if (abs($velocityPercent) > $chaosConstant) {
					// 				// High volatility - apply velocity adjustment to AI quantity
					// 				$adjustmentFactor = 1 + ($velocityPercent / 100);
					// 				$this->ai_qty = max($minQty, ceil($aiCalculatedQty * $adjustmentFactor));
					// 				Yii::log("High volatility adjustment for item $itemId: AI qty $aiCalculatedQty adjusted by {$velocityPercent}% to {$this->ai_qty}", 'info');
					// 		} else if (abs($velocityPercent) < $chaosConstantReduce) {
					// 				// Low volatility - conservative adjustment
					// 				$adjustmentFactor = 1 - (abs($velocityPercent) / 200); // Gentle reduction
					// 				$this->ai_qty = max($minQty, ceil($aiCalculatedQty * $adjustmentFactor));
					// 				Yii::log("Low volatility adjustment for item $itemId: AI qty $aiCalculatedQty adjusted by {$adjustmentFactor} to {$this->ai_qty}", 'info');
					// 		} else {
					// 				// Normal volatility - use AI calculated quantity
					// 				$this->ai_qty = max($minQty, $aiCalculatedQty);
					// 		}
					// } else {
							// No velocity data - use pure AI calculation
					$this->ai_qty = max($minQty, $aiCalculatedQty);
					// }

					// Set create_time if not already set
					if (empty($this->create_time)) {
							$this->create_time = date('Y-m-d H:i:s');
					}
			}

			return true;
  }

  private function getLatestVelocity($itemId)
  {
			$sql = "
					SELECT 
							item_id,
							velocity_change_percent
					FROM 
							tbl_item_velocity
					WHERE 
							item_id = :itemId
					ORDER BY 
							id DESC
					LIMIT 1
			";

			$connection = Yii::app()->db;
			$command = $connection->createCommand($sql);
			$command->bindParam(':itemId', $itemId, PDO::PARAM_INT);
			return $command->queryRow();
	}

	private function calculateAIQty($itemId) {
		// Calculate AI-based reorder quantity using velocity and lead time analysis
		$sql = "
		SELECT 
			v.item_id,
			lt.title,
			lt.vendor_name,
			v.daily_velocity,
			lt.avg_lead_time,
			lt.receiving_date,
			lt.mrs_date,
			lt.start_date,
			lt.current_reorder_qty,
			
			ROUND(v.daily_velocity * lt.avg_lead_time, 2) AS reorder_point,

			ROUND(v.daily_velocity * lt.avg_lead_time * 1.5, 2) AS calculated_reorder_qty,
			
			ROUND(v.daily_velocity * SQRT(lt.avg_lead_time) * 1.65, 2) AS safety_stock

		FROM 
		(
			SELECT 
				sl.item_id,
				ROUND(SUM(sl.qty) / (DATEDIFF(CURDATE(), DATE_SUB(CURDATE(), INTERVAL 30 DAY)) + 1), 2) AS daily_velocity,
				COUNT(*) AS transaction_count
			FROM tbl_stock_log sl
			WHERE sl.type_id IN (4,7)  -- Sales and consumption types
			  AND sl.create_time >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
			  AND sl.item_id = :item_id
			GROUP BY sl.item_id
			HAVING COUNT(*) >= 2  -- Minimum transactions for reliable calculation
		) v

		LEFT JOIN 
		(
			SELECT 
				md.item_id,
				AVG(DATEDIFF(pb.start_date, m.mrs_date)) AS avg_lead_time,
				po.receiving_date,
				m.mrs_date,
				i.title,
				i.reorder_qty AS current_reorder_qty,
				vv.name AS vendor_name,
				pb.start_date,
				COUNT(*) AS procurement_cycles
			FROM tbl_mrs_detail md
			INNER JOIN tbl_mrs m ON m.id = md.mrs_id
			INNER JOIN tbl_mrn n ON n.mrs_id = m.id
			INNER JOIN tbl_purchase_order po ON po.mrn_id = n.id
			INNER JOIN tbl_purchase_bill pb ON po.id = pb.purchase_order_id
			INNER JOIN tbl_item i ON i.id = md.item_id
			INNER JOIN tbl_item_vendor iv ON i.id = iv.item_detail_id
			INNER JOIN tbl_vendor vv ON iv.vendor_id = vv.id
			WHERE pb.start_date IS NOT NULL
			  AND pb.start_date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
			  AND md.item_id = :item_id
			  AND DATEDIFF(pb.start_date, m.mrs_date) > 0 
			GROUP BY md.item_id, i.title, i.reorder_qty, vv.name
			HAVING COUNT(*) >= 1 
		) lt ON v.item_id = lt.item_id
		
		WHERE v.item_id = :item_id
		  AND v.daily_velocity > 0
		  AND lt.avg_lead_time > 0
		  AND lt.mrs_date != lt.start_date  
		LIMIT 1
		";
		
		$connection = Yii::app()->db;
		$command = $connection->createCommand($sql);
		$command->bindParam(':item_id', $itemId, PDO::PARAM_INT);
		$result = $command->queryRow();
		
		if ($result) {
			// Use calculated reorder quantity with buffer
			$calculatedQty = $result['calculated_reorder_qty'];
			$safetyStock = $result['safety_stock'];
			$currentReorderQty = $result['current_reorder_qty'];
			
			// Apply business rules
			$finalQty = max($calculatedQty, $safetyStock);
			
			// Don't drastically change from current reorder qty - max 50% increase/decrease
			if ($currentReorderQty > 0) {
				$maxChange = $currentReorderQty * 0.5;
				$maxQty = $currentReorderQty + $maxChange;
				$minQty = $currentReorderQty - $maxChange;
				$finalQty = min(max($finalQty, $minQty), $maxQty);
			}
			
			// Minimum quantity should be at least 1
			$finalQty = max(1, ceil($finalQty));
			
			// Log the calculation for debugging
			Yii::log("AI Qty calculation for item $itemId: velocity={$result['daily_velocity']}, lead_time={$result['avg_lead_time']}, calculated=$calculatedQty, safety=$safetyStock, final=$finalQty", 'info', 'mrs.ai_qty');
			
			return $finalQty;
		} else {
			// Fallback: Use historical average or minimum quantity
			return $this->getFallbackQty($itemId);
		}
	}
	
	/**
	 * Fallback quantity calculation when insufficient data for AI calculation
	 */
	private function getFallbackQty($itemId) {
		// Get average MRS quantity for this item in last 6 months
		$sql = "
		SELECT 
			AVG(md.req_qty) as avg_req_qty,
			COUNT(*) as mrs_count
		FROM tbl_mrs_detail md
		INNER JOIN tbl_mrs m ON m.id = md.mrs_id
		WHERE md.item_id = :item_id
		  AND m.create_time >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
		  AND md.req_qty > 0
		";
		
		$connection = Yii::app()->db;
		$command = $connection->createCommand($sql);
		$command->bindParam(':item_id', $itemId, PDO::PARAM_INT);
		$result = $command->queryRow();
		
		if ($result && $result['mrs_count'] >= 2) {
			$avgQty = ceil($result['avg_req_qty']);
			Yii::log("Fallback qty for item $itemId: avg_req_qty=$avgQty from {$result['mrs_count']} MRS records", 'info', 'mrs.fallback_qty');
			return max(1, $avgQty);
		}
		
		// Final fallback - return minimum quantity of 1
		return 1;
	}

	public function getMrsVendorOptions(){
		$list = [];
		$criteria = new CDbCriteria();
		$criteria->addCondition('status ='.Mrs::STATUS_PENDING);
		$criteria->addCondition('Date(create_time) >= DATE_SUB(CURDATE(), INTERVAL 50 DAY)');
		$mrss = Mrs::model()->findAll($criteria);
		if($mrss){
			foreach($mrss as $mrs){
				$count = count($mrs->mrsDetails);
				$create_time = date('d-m-Y',strtotime($mrs->create_time));
				$vendor = Vendor::model()->findByPk($mrs->vendor_id);
				$amt = '0.00';
				if($mrs->bill_amount != null){
					$amt = $mrs->bill_amount;
				}
				if($vendor){
					
					$list[$vendor->id] = $vendor->name.'('.$create_time.')'.'[ Count- '.$count.']';
				}
			}
		}
		//Yii::log ( CVarDumper::dumpAsString ( $list ), CLogger::LEVEL_WARNING, '$list1' );
		asort($list);
		//Yii::log ( CVarDumper::dumpAsString ( $list ), CLogger::LEVEL_WARNING, '$list2' );
		return $list;
	}
	public function getVendorOptions(){
		$list = array();
		$item_vendors = ItemVendor::model()->findAllByAttributes(array('item_detail_id'=>$this->item_id));
		if($item_vendors){
			foreach($item_vendors as $item_vendor){
				$vendor = Vendor::model()->findByPk($item_vendor->vendor_id);
				if($vendor){
					$list[$vendor->id] = $vendor->name;
				}
			}
		}
		return $list;
	}
	public function getMrsOptions($id = null) {
		$list = array ();
		$user = Yii::app()->user->model;
		//$user = User::model ()->findByPk ( $id );
		if ($user) {
			$role_id = $user->role_id;
			$role = UserRole::model()->findByAttributes(array('title'=>'Vendor'));
			
			if ($id != null) {
				if ($role_id == $role->id) {
					$criteria = new CDbCriteria();
					$criteria->addCondition('vendor_id ='.$id);
					$criteria->addCondition('status !='.Mrs::STATUS_DONE);
					$mrslist = Mrs::model ()->findAll($criteria);
				} else {
					$criteria = new CDbCriteria();
				
					$criteria->addCondition('status !='.Mrs::STATUS_DONE);
					$mrslist = Mrs::model ()->findAll ($criteria);
				}
				if ($mrslist) {
					foreach ( $mrslist as $mrs ) {
						$list [$mrs->id] = $mrs->id;
					}
				}
			}
		}
		return $list;
	}
	public function getAllMrsOptions($id = null) {
		$list = array ();
		$user = Yii::app()->user->model;
		if ($user) {
			$role_id = $user->role_id;
			$role = UserRole::model()->findByAttributes(array('title'=>'Vendor'));
			
			if ($id != null) {
				if ($role_id == $role->id) {
					$criteria = new CDbCriteria();
					$criteria->addCondition('vendor_id ='.$id);
					$criteria->addCondition('status !='.Mrs::STATUS_DONE);
					$mrslist = Mrs::model ()->findAll($criteria);
				}else {
					$criteria = new CDbCriteria();
					
					$criteria->addCondition('status !='.Mrs::STATUS_DONE);
					$mrslist = Mrs::model ()->findAll ($criteria);
				}
				if ($mrslist) {
					foreach ( $mrslist as $mrs ) {
						$list [] = $mrs->id;
					}
				}
			}
		}
		return $list;
	}
	public function getGstTrue($mrsid){
		$gst = true;
		if($mrsid){
			$mrs = Mrs::model()->findByAttributes(array('id'=>$mrsid));
			if($mrs){
				$outlet = Outlet::model()->findByPk($mrs->outlet_id);
				Yii::log ( CVarDumper::dumpAsString ( $outlet->state_id ), CLogger::LEVEL_WARNING, '$outlet->state_id' );
				if($outlet){
					$vendor = Vendor::model()->findByPk($mrs->vendor_id);
					Yii::log ( CVarDumper::dumpAsString ( $vendor->state_id ), CLogger::LEVEL_WARNING, '$vendor->state_id' );
					if($vendor->state_id != $outlet->state_id){
						$gst = false;
					}
				}
			}
		}
		return $gst;
	}
	public function getPurchaseAmount(){
		$mrs = Mrs::model()->findByPk($this->mrs_id);
		$amount = '0';
		$criteria1 = new CDbCriteria();
		$criteria1->addCondition('t.item_id ='.$this->item_id);
		$criteria1->select = 'sum(t.amount) as amount';
		$orderitem = PurchaseBillDetail::model()->find($criteria1);
		if($orderitem){
			$amount = $orderitem->amount;
		}
		if($amount == ''){
			$amount = '0';
		}
		//Yii::log ( CVarDumper::dumpAsString ( $amount ), CLogger::LEVEL_WARNING, '$amount' );
		return $amount;
	}
	public function getSaleAmount(){
		$amount = '0';
		$criteria1 = new CDbCriteria();
		$criteria1->addCondition('t.item_id ='.$this->item_id);
		$criteria1->select = 'sum(t.total_amt) as total_amt';
		$orderitem = OrderItem::model()->find($criteria1);
		if($orderitem){
			$amount = $orderitem->total_amt;
		}
		if($amount == ''){
			$amount = '0';
		}
		//Yii::log ( CVarDumper::dumpAsString ( $amount ), CLogger::LEVEL_WARNING, '$saleamount' );
		return $amount;
	}
	public function getPurchaseQty(){
		$mrs = Mrs::model()->findByPk($this->mrs_id);
		$qty = '0';
		$criteria1 = new CDbCriteria();
		$criteria1->addCondition('t.item_id ='.$this->item_id);
		$criteria1->select = 'sum(t.approved_qty) as approved_qty';
		$orderitem = PurchaseBillDetail::model()->find($criteria1);
		if($orderitem){
			$qty = $orderitem->approved_qty;
		}
		if($qty == ''){
			$qty = '0';
		}
		Yii::log ( CVarDumper::dumpAsString ( $this->item_id ), CLogger::LEVEL_WARNING, '$this->item_id' );
		Yii::log ( CVarDumper::dumpAsString ( $qty ), CLogger::LEVEL_WARNING, '$purqty' );
		return $qty;
	}
	public function getSaleQty(){
		$qty = '0';
		$criteria1 = new CDbCriteria();
		$criteria1->addCondition('t.item_id ='.$this->item_id);
		$criteria1->select = 'sum(t.qty) as qty';
		$orderitem = OrderItem::model()->find($criteria1);
		if($orderitem){
			$qty = $orderitem->qty;
		}
		if($qty == ''){
			$qty = '0';
		}
		Yii::log ( CVarDumper::dumpAsString ( $this->item_id ), CLogger::LEVEL_WARNING, '$this->item_id' );
		Yii::log ( CVarDumper::dumpAsString ( $qty ), CLogger::LEVEL_WARNING, '$saleqty' );
		return $qty;
	}

	/**
	 * Get reorder analysis data for items that need restocking
	 */
	public static function getReorderAnalysisData($limit = 50, $startDate = null, $endDate = null) {
		if (!$startDate) $startDate = date('Y-m-01', strtotime('-1 month'));
		if (!$endDate) $endDate = date('Y-m-t');
		
		$sql = "
		SELECT 
			v.item_id,
			lt.title,
			lt.vendor_name,
			v.daily_velocity,
			v.transaction_count,
			lt.avg_lead_time,
			lt.procurement_cycles,
			lt.receiving_date,
			lt.mrs_date,
			lt.start_date,
			lt.current_reorder_qty,
			
			-- Reorder Point = Daily Velocity × Lead Time
			ROUND(v.daily_velocity * lt.avg_lead_time, 2) AS reorder_point,

			-- Reorder Quantity = ROP × 1.5 buffer
			ROUND(v.daily_velocity * lt.avg_lead_time * 1.5, 2) AS calculated_reorder_qty,
			
			-- Current stock status
			COALESCE(stock.current_stock, 0) AS current_stock,
			
			-- Status indicators
			CASE 
				WHEN COALESCE(stock.current_stock, 0) <= (v.daily_velocity * lt.avg_lead_time) THEN 'REORDER_NOW'
				WHEN COALESCE(stock.current_stock, 0) <= (v.daily_velocity * lt.avg_lead_time * 1.2) THEN 'REORDER_SOON'
				ELSE 'SUFFICIENT'
			END AS stock_status,
			
			-- Days of stock remaining
			CASE 
				WHEN v.daily_velocity > 0 THEN ROUND(COALESCE(stock.current_stock, 0) / v.daily_velocity, 1)
				ELSE 999
			END AS days_remaining

		FROM 
		(
			-- Velocity Subquery
			SELECT 
				sl.item_id,
				ROUND(SUM(sl.qty) / (DATEDIFF(:end_date, :start_date) + 1), 2) AS daily_velocity,
				COUNT(*) AS transaction_count
			FROM tbl_stock_log sl
			WHERE sl.type_id IN (4,7)
			  AND DATE(sl.create_time) BETWEEN :start_date AND :end_date
			  AND sl.qty > 0
			GROUP BY sl.item_id
			HAVING COUNT(*) >= 2
		) v

		LEFT JOIN 
		(
			-- Lead Time Subquery
			SELECT 
				md.item_id,
				AVG(DATEDIFF(pb.start_date, m.mrs_date)) AS avg_lead_time,
				po.receiving_date,
				m.mrs_date,
				i.title,
				i.reorder_qty AS current_reorder_qty,
				vv.name AS vendor_name,
				pb.start_date,
				COUNT(*) AS procurement_cycles
			FROM tbl_mrs_detail md
			INNER JOIN tbl_mrs m ON m.id = md.mrs_id
			INNER JOIN tbl_mrn n ON n.mrs_id = m.id
			INNER JOIN tbl_purchase_order po ON po.mrn_id = n.id
			INNER JOIN tbl_purchase_bill pb ON po.id = pb.purchase_order_id
			INNER JOIN tbl_item i ON i.id = md.item_id
			INNER JOIN tbl_item_vendor iv ON i.id = iv.item_detail_id
			INNER JOIN tbl_vendor vv ON iv.vendor_id = vv.id
			WHERE pb.start_date IS NOT NULL
			  AND DATE(pb.start_date) BETWEEN DATE_SUB(:end_date, INTERVAL 90 DAY) AND :end_date
			  AND DATEDIFF(pb.start_date, m.mrs_date) > 0
			GROUP BY md.item_id, i.title, i.reorder_qty, vv.name
			HAVING COUNT(*) >= 1
		) lt ON v.item_id = lt.item_id
		
		LEFT JOIN (
			-- Current Stock Subquery
			SELECT 
				i.id as item_id,
				SUM(ist.qty) as current_stock
			FROM tbl_item i
			LEFT JOIN tbl_item_detail id ON i.id = id.item_id
			LEFT JOIN tbl_item_stock ist ON id.id = ist.item_detail_id
			WHERE i.status = 0
			GROUP BY i.id
		) stock ON v.item_id = stock.item_id
		
		WHERE lt.item_id IS NOT NULL
		  AND v.daily_velocity > 0
		  AND lt.avg_lead_time > 0
		  AND lt.mrs_date != lt.start_date
		
		ORDER BY 
			CASE stock_status 
				WHEN 'REORDER_NOW' THEN 1 
				WHEN 'REORDER_SOON' THEN 2 
				ELSE 3 
			END,
			days_remaining ASC,
			v.daily_velocity DESC
		LIMIT :limit
		";
		
		$connection = Yii::app()->db;
		$command = $connection->createCommand($sql);
		$command->bindParam(':start_date', $startDate, PDO::PARAM_STR);
		$command->bindParam(':end_date', $endDate, PDO::PARAM_STR);
		$command->bindParam(':limit', $limit, PDO::PARAM_INT);
		
		return $command->queryAll();
	}

	public function getCssClass()
	{
		$cssClass = '';
		$purchase_amount = $this->getPurchaseAmount();
		$sale_amount = $this->getSaleAmount();
		$purchase_qty = $this->getPurchaseQty();
		$per_purchase_qty = (($this->getPurchaseQty()) - (10/100) * ($this->getPurchaseQty()));
		$sale_qty = $this->getSaleQty();
		Yii::log ( CVarDumper::dumpAsString ( $this ), CLogger::LEVEL_WARNING, '$mrs' );
		if($purchase_amount > $sale_amount){
			$cssClass='mrsred';
		}else if($sale_qty > $per_purchase_qty){
		$cssClass='mrsgreen';
	   }else if($this->margin < 10){
		$cssClass='mrsorange';
	   }else{
	   	$cssClass='';
	   }
	   Yii::log ( CVarDumper::dumpAsString ( $cssClass ), CLogger::LEVEL_WARNING, '$cssClass' );
		return $cssClass;
	}
}