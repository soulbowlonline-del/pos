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

        $controller = $this->toYii2($parts[0]);
        // Only for controllers this application actually serves, so an unported
        // path still falls through to a 404 rather than resolving oddly.
        if (!Ui::isPorted($parts[0]) && !Ui::isPorted($this->toYii1($parts[0]))) {
            return false;
        }

        $action = isset($parts[1]) ? $this->toYii2($parts[1], false) : 'index';

        // Yii 1's path format puts GET arguments in the path as alternating
        // name and value segments, and the application's own javascript uses
        // it: purchaseBillDetail's bill-number lookup posts to
        // /purchaseBillDetail/ajaxBillNo/id/63677. This rule took exactly two
        // segments and refused anything longer, so every ajax call built that
        // way answered 404 - the page rendered and nothing on it worked.
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
