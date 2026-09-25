<?php
namespace app\components;

use Yii;

use yii\helpers\Url;

/**
 * Link building across the two stacks, for as long as both are serving.
 *
 * The web UI is being ported controller by controller. Until it is finished,
 * most of the sidebar and most of the grid buttons have to keep pointing at the
 * Yii 1 pages, or navigating away from a ported screen would 404. So every link
 * in the Yii 2 UI goes through here: PORTED lists the controllers this
 * application serves, and everything else is handed back to Yii 1 at the site
 * root.
 *
 * Adding a controller to PORTED is what switches its links over. That is the
 * only edit needed when one lands.
 */
class Ui
{
    /** Controller ids the Yii 2 UI serves. Everything else stays with Yii 1. */
    public const PORTED = [
        'paymentMode',
        'userRole',
        'advanceLogs',
        'empShift',
        'question',
        'shift',
        'advancePayment',
        'itemExpireItem',
        'paymentReport',
        'itemCompanyCategory',
        'bill',
        'session',
        'itemVendor',
        'notification',
        'creditNote',
        'state',
        'city',
        'stockLog',
        'permission',
        'country',
        'designation',
        'outlet',
        'freeItem',
        'mrs',
        'organization',
        'rolePermission',
        'itemTax',
        'tax',
        'customer',
        'emp',
        'orderRefund',
        'stockAdjustLog',
        'item',
        'itemCompany',
        'discount',
        'itemCategory',
        'itemExpire',
        'itemReturn',
        'itemReturnItem',
        'itemStock',
        'mrn',
        'b2bPurchaseBill',
        'itemDetail',
        'mrnDetail',
        'mrsDetail',
        'vendorSchemes',
        'orderRefundItem',
        'vendor',
        'user',
        'purchaseOrder',
        'purchaseBillDetail',
        'onlineOrder',
        'purchaseOrderDetail',
        'site',
        'b2BPurchaseBillDetail',
        'loyaltyAdmin',
        'orderItem',
        'purchaseBill',
        'order',
    ];

    /**
     * @param string $route 'controller/action', as Yii 1 spells it
     * @param array  $params query parameters
     */
    /**
     * Yii 1's `Yii::$app->errorHandler->error`.
     *
     * Yii 1 hands the error view an array - code, message, type, file, line;
     * Yii 2's handler holds the exception object instead. site/error reads the
     * array, so it is rebuilt here rather than the view being rewritten, and
     * the view stays the shape Yii 1 wrote it.
     *
     * Null when nothing failed, as in Yii 1, so `if ($error = ...)` still
     * reads the same.
     */
    public static function errorArray()
    {
        $e = Yii::$app->errorHandler->exception ?? null;
        if ($e === null) {
            return null;
        }

        return [
            'code' => $e instanceof \yii\web\HttpException ? $e->statusCode : 500,
            'type' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ];
    }

    public static function to($route, $params = [])
    {
        // Some call sites put the query in the route itself -
        // createUrl('itemTax/admin?exportCSV=1') - which Yii 1 accepted.
        // Split it off, or the whole string is taken for a route name.
        if (($q = strpos($route, '?')) !== false) {
            parse_str(substr($route, $q + 1), $inline);
            $params = array_merge($inline, $params);
            $route = substr($route, 0, $q);
        }

        // The route may arrive in either spelling. A view asking the
        // controller for its own route - the search forms all post to
        // `$this->context->route` - gets Yii 2's, `advance-logs/search`, and
        // that controller id is not what PORTED lists. Normalising here fixes
        // every such call site at once, and leaves a route already written the
        // Yii 1 way untouched: toYii1Id() is idempotent on camelCase.
        $parts = explode('/', ltrim($route, '/'));
        $parts = array_map([self::class, 'toYii1Id'], $parts);
        $route = implode('/', $parts);

        $controller = strtok($route, '/');

        if (in_array($controller, self::PORTED, true)) {
            return Url::to(array_merge(['/' . $route], $params));
        }

        // Yii 1, at the site root rather than under /v2
        $url = '/' . ltrim($route, '/');
        return $params ? $url . '?' . http_build_query($params) : $url;
    }

    /**
     * Rows per page in a listing.
     *
     * Yii 1's CPagination defaults to 10 and Yii 2's to 20, so a provider that
     * names no page size lists a different number of rows on each stack. The
     * pages do not set one, so the framework default is the behaviour being
     * reproduced.
     */
    public const PAGE_SIZE = 10;

    /** True when the Yii 2 UI serves this controller. */
    public static function isPorted($controller)
    {
        return in_array($controller, self::PORTED, true);
    }

    /**
     * The canonical PORTED spelling of a controller named any which way.
     *
     * The same indifference to case that resolveActionId() restores for the
     * action, for the segment before it: Yii 1 answers /mrsdetail/admin and
     * /api/Customer/... as readily as the spellings its own links use, and the
     * port matched the id exactly, so those 404'd. Compared on letters and
     * digits alone; the 59 ported controllers do not collide under it.
     *
     * @return string|null the PORTED spelling, or null if it is not one
     */
    public static function resolveControllerId($controller)
    {
        if (in_array($controller, self::PORTED, true)) {
            return $controller;
        }

        $wanted = preg_replace('/[^a-z0-9]/', '', strtolower((string) $controller));
        if ($wanted === '') {
            return null;
        }

        foreach (self::PORTED as $known) {
            if (preg_replace('/[^a-z0-9]/', '', strtolower($known)) === $wanted) {
                return $known;
            }
        }

        return null;
    }

    /**
     * Controllers whose name the API port already uses.
     *
     * Emp, Customer, Item and Order are each an API controller in
     * app2/controllers *and* a CRUD controller in the web UI. One class name
     * cannot be both, and the API port is the one that is finished and
     * verified, so the UI controller takes a suffixed id instead:
     * /v2/item/admin is served by ItemUiController, while /v2/api/item/* keeps
     * ItemController.
     *
     * The URL does not change - only which class answers it.
     */
    public const API_NAMES = ['emp', 'customer', 'item', 'order', 'loyalty', 'tally', 'site'];

    /** True when the UI controller for this name needs the suffix. */
    public static function needsUiSuffix($controller)
    {
        return in_array($controller, self::API_NAMES, true);
    }

    /**
     * paymentMode -> payment-mode.
     *
     * Yii 1 spells controller and action ids in camelCase; Yii 2 requires
     * lowercase and hyphens and rejects anything else. Both spellings are in
     * play at once - the URL and the permission table use Yii 1's, the
     * controller and action objects use Yii 2's - so the conversion lives here
     * rather than being repeated wherever the two meet.
     */
    public static function toYii2Id($id, $isController = true)
    {
        // A run of capitals first: B2BPurchaseBillDetail's Yii 1 id is
        // b2BPurchaseBillDetail, and splitting only on lower-then-upper gave
        // b2-bpurchase-bill-detail, which Yii 2 resolves to a class that does
        // not exist. The controller was unreachable under /v2 - and its seven
        // comparisons passed, because a 404 on both stacks used to count as
        // agreement.
        $id = preg_replace('/([A-Z]+)([A-Z][a-z])/', '$1-$2', $id);
        $hyphenated = strtolower(preg_replace('/([a-z0-9])([A-Z])/', '$1-$2', $id));

        // The suffix distinguishes a *controller* from the API one of the
        // same name. An action called `item` is not that controller, and
        // adding it there asked for the route vendor/item-ui, which does not
        // exist: vendor/item answered 404 on the port and 200 on Yii 1.
        return $isController && self::needsUiSuffix($id) ? $hyphenated . '-ui' : $hyphenated;
    }

    /**
     * The Yii 2 action id for an action spelled the way a client spelled it.
     *
     * PHP matches method names without regard to case, so Yii 1 answers
     * api/customer/holdorderList, holdOrderList, holdorderlist and
     * HoldOrderList alike - every one of them reaches actionHoldOrderList().
     * Yii 2 matches the hyphenated id exactly, and toYii2Id() hyphenates from
     * whatever capitalisation the caller used, so holdOrderList becomes
     * hold-order-list and answers while holdorderList becomes holdorder-list
     * and 404s. The .NET client and the APK send the spelling they always
     * sent, and the port is the only thing that minds.
     *
     * So the id is resolved against the controller's own actions, compared on
     * letters and digits alone. Across the 59 controllers that is 698 actions
     * and no two of them collide under that comparison, so the match is never
     * ambiguous.
     *
     * Falls back to toYii2Id() whenever the controller cannot be found or has
     * no such action, which leaves an unknown route 404ing as it did before.
     *
     * @param string $controllerId the Yii 2 controller id, `customer-ui` and all
     * @param string $action       the action as the URL spells it
     */
    public static function resolveActionId($controllerId, $action)
    {
        $fallback = self::toYii2Id($action, false);

        $class = 'app\\controllers\\'
            . str_replace(' ', '', ucwords(str_replace('-', ' ', $controllerId)))
            . 'Controller';
        if (!class_exists($class)) {
            return $fallback;
        }

        $wanted = preg_replace('/[^a-z0-9]/', '', strtolower($action));
        if ($wanted === '') {
            return $fallback;
        }

        foreach (get_class_methods($class) as $method) {
            if (strncmp($method, 'action', 6) !== 0 || $method === 'actions') {
                continue;
            }
            $name = substr($method, 6);
            if (preg_replace('/[^a-z0-9]/', '', strtolower($name)) === $wanted) {
                return self::toYii2Id($name, false);
            }
        }

        return $fallback;
    }

    /** payment-mode -> paymentMode */
    public static function toYii1Id($id)
    {
        if (substr($id, -3) === '-ui') {
            $id = substr($id, 0, -3);
        }

        return lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $id))));
    }

    /**
     * The route as tbl_permission spells it, from Yii 2's controller and
     * action ids. This is what the permission rows are keyed by.
     */
    public static function legacyRoute($controllerId, $actionId)
    {
        return self::toYii1Id($controllerId) . '/' . self::toYii1Id($actionId);
    }
}
