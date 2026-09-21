<?php
namespace app\components;

use yii\base\BaseObject;
use yii\web\UrlRuleInterface;

/**
 * Keeps the Yii 1 API URL shape working under /v2.
 *
 * The web UI has LegacyUrlRule for this. The API did not: its routes were
 * plain string rules that passed `<action>` through untouched, so the port
 * answered only the hyphenated spelling Yii 2 prefers -
 * /v2/api/customer/country-list - while Yii 1, and every client built against
 * it, sends /api/customer/countryList.
 *
 * Every multi-word action in the API was therefore a 404 on the port:
 * countryList, stateList, cityList, getLatestBill, getLastOrder, orderList,
 * getOnlineOrder, preRedeemPoints and the rest. The .NET application and the
 * Android app could not call the port at all, and the suites did not notice
 * because they were written against the port's own spelling rather than the
 * one the clients use.
 *
 * Both spellings resolve here: toYii2Id() leaves an already-hyphenated id
 * alone, so nothing that worked before stops working.
 *
 * The controllers are listed rather than matched with a pattern, because the
 * API's names and the ported web UI's overlap - `item`, `order`, `customer`
 * are both - and only these six belong to the Yii 1 api module. loyalty and
 * emp answer POST alone, as their string rules did.
 */
class ApiUrlRule extends BaseObject implements UrlRuleInterface
{
    /** Yii 1's api module, and which of them are POST-only. */
    private const CONTROLLERS = [
        'customer' => null, 'order' => null, 'tally' => null, 'item' => null,
        'loyalty'  => 'POST', 'emp' => 'POST',
    ];

    public function parseRequest($manager, $request)
    {
        $parts = explode('/', trim($request->getPathInfo(), '/'));
        if (count($parts) < 3 || $parts[0] !== 'api') {
            return false;
        }

        $controller = $parts[1];
        $action = $parts[2];
        if (!array_key_exists($controller, self::CONTROLLERS)) {
            return false;
        }

        $method = self::CONTROLLERS[$controller];
        if ($method !== null && strtoupper($request->getMethod()) !== $method) {
            return false;
        }

        if ($action === '' || !preg_match('/^[\w-]+$/', $action)) {
            return false;
        }

        return [$controller . '/' . Ui::toYii2Id($action, false),
                self::pathParams(array_slice($parts, 3))];
    }

    /**
     * Yii 1's path-format parameters: /name//rate//title/pepsi.
     *
     * CUrlManager appends GET arguments to the route as alternating name and
     * value segments, and that is what the .NET application sends - the
     * billing screen's item search asks for
     * /api/item/search/name//rate//title/pepsi on every keystroke. An empty
     * value is an empty segment, which is why the doubled slashes.
     *
     * This rule used to require exactly three segments, so every one of those
     * requests was a 404 and no item ever reached the till. The suites ask
     * with a query string, which both stacks have always accepted, so nothing
     * had ever sent the other form at the port.
     *
     * A trailing name with no value is an empty value, as in Yii 1.
     */
    private static function pathParams(array $rest)
    {
        $params = [];
        for ($i = 0; $i < count($rest); $i += 2) {
            $name = urldecode($rest[$i]);
            if ($name === '') {
                continue;
            }
            $params[$name] = isset($rest[$i + 1]) ? urldecode($rest[$i + 1]) : '';
        }

        return $params;
    }

    /**
     * Never generates a URL.
     *
     * The API and the web UI share controller names - order, item, customer,
     * emp are both - and a rule that matched `order/<action>` when *building*
     * a link claimed every web link to an order page as well. The sidebar's
     * 24 report links came out as /v2/api/order/userWise, /v2/api/item/admin
     * and so on, which route to the API controller and answer nothing a
     * browser can use.
     *
     * The six string rules this class replaced did the same thing, for the
     * same reason: a Yii 2 string rule matches createUrl() on its route, and
     * `api/order/<action> => order/<action>` matches the route `order/admin`
     * wherever it comes from. It went unnoticed because the sidebar's menu
     * did not open - jQuery was loading after the script that wires it up -
     * so nobody had ever clicked one of those links.
     *
     * Nothing in the port needs to generate an API URL: the API answers with
     * data, and the clients build their own addresses. Declining here leaves
     * every link to LegacyUrlRule, which knows about the -ui controllers.
     */
    public function createUrl($manager, $route, $params)
    {
        return false;
    }
}
