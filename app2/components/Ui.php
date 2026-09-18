<?php
namespace app\components;

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
        'item',
    ];

    /**
     * @param string $route 'controller/action', as Yii 1 spells it
     * @param array  $params query parameters
     */
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
    public static function toYii2Id($id)
    {
        $hyphenated = strtolower(preg_replace('/([a-z0-9])([A-Z])/', '$1-$2', $id));

        return self::needsUiSuffix($id) ? $hyphenated . '-ui' : $hyphenated;
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
