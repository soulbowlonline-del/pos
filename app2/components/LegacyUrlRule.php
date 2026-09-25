<?php
namespace app\components;

use yii\web\UrlRuleInterface;
use yii\base\BaseObject;

/**
 * Keeps the Yii 1 URL shape working under /v2.
 *
 * Yii 1 spells controller and action ids in camelCase - /paymentMode/admin,
 * /order/getOnlineOrder. Yii 2 requires lowercase-and-hyphens for a controller
 * id and rejects anything else outright, so /v2/paymentMode/admin is a 404 no
 * matter which controller exists.
 *
 * This translates between the two in both directions, so a page ported to
 * Yii 2 answers at the same path it had under Yii 1, only prefixed with /v2.
 * That matters while both are serving: the two URLs differ by the prefix and
 * nothing else, which is what makes them comparable.
 *
 * The alternative was a hand-written rule per controller. With 59 to port,
 * this is one rule instead of 59, and nothing to forget when one lands.
 */
class LegacyUrlRule extends BaseObject implements UrlRuleInterface
{
    private function toYii2($id, $isController = true)
    {
        return Ui::toYii2Id($id, $isController);
    }

    private function toYii1($id)
    {
        return Ui::toYii1Id($id);
    }

    public function parseRequest($manager, $request)
    {
        $path = trim($request->getPathInfo(), '/');
        if ($path === '') {
            return false;
        }

        $parts = explode('/', $path);
        if (count($parts) < 1) {
            return false;
        }

        // Only for controllers this application actually serves, so an unported
        // path still falls through to a 404 rather than resolving oddly. Matched
        // without regard to case, as Yii 1 matches it - /mrsdetail/admin is a
        // page there and was a 404 here.
        $known = Ui::resolveControllerId($parts[0]) ?: Ui::resolveControllerId($this->toYii1($parts[0]));
        if ($known === null) {
            return false;
        }
        $controller = $this->toYii2($known);

        // Yii 1's own rules, in its own order (config/main.php):
        //
        //   <controller>/<id:\d+>              -> <controller>/view
        //   <controller>/<action>/<id:\d+>     -> <controller>/<action>
        //   <controller>/<action>
        //
        // and, failing those, the path format's alternating name and value
        // segments - which is what the application's javascript uses:
        // purchaseBillDetail's bill-number lookup posts to
        // /purchaseBillDetail/ajaxBillNo/id/63677.
        //
        // The numeric forms have to come first. Reading /customer/view/7385
        // as a name and value gives the parameter "7385" with no value and no
        // id at all, and Yii 2 answers 400 "Missing required parameters: id"
        // for every view link in the application.
        if (count($parts) === 2 && ctype_digit($parts[1])) {
            return [$controller . '/view', ['id' => $parts[1]]];
        }

        // resolveActionId() rather than toYii2(): Yii 1 reaches actionItemWise()
        // from /order/itemwise, /order/itemWise and /order/ITEMWISE alike,
        // because PHP does not mind the case of a method name. Hyphenating the
        // caller's own spelling does mind - /order/itemwise became order/itemwise
        // and 404'd where Yii 1 answered 200.
        $action = isset($parts[1]) ? Ui::resolveActionId($controller, $parts[1]) : 'index';

        if (count($parts) === 3 && ctype_digit($parts[2])) {
            return [$controller . '/' . $action, ['id' => $parts[2]]];
        }

        // A trailing name with no value is an empty value, as in Yii 1.
        $params = [];
        $rest = array_slice($parts, 2);
        for ($i = 0; $i < count($rest); $i += 2) {
            $name = urldecode($rest[$i]);
            if ($name !== '') {
                $params[$name] = isset($rest[$i + 1]) ? urldecode($rest[$i + 1]) : '';
            }
        }

        return [$controller . '/' . $action, $params];
    }

    public function createUrl($manager, $route, $params)
    {
        $parts = explode('/', $route);
        if (count($parts) !== 2) {
            return false;
        }
        if (!Ui::isPorted($this->toYii1($parts[0]))) {
            return false;
        }

        $url = $this->toYii1($parts[0]) . '/' . $this->toYii1($parts[1]);
        $query = http_build_query($params);
        return $url . ($query ? '?' . $query : '');
    }
}
