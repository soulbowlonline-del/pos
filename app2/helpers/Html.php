<?php
namespace yii\helpers;

/**
 * Yii 2's Html helper, generating Yii 1's element ids.
 *
 * Yii 2 names an input `purchaseorderdetail-item_id`: the input name
 * lowercased with its brackets turned into hyphens. Yii 1 names the same input
 * `PurchaseOrderDetail_item_id` - the case kept, the brackets turned into
 * underscores.
 *
 * Every line of javascript in this application is Yii 1's, served unchanged
 * from the same theme, and it addresses fields by Yii 1's id. There are 198
 * such references across the ported views. Under Yii 2's naming, not one of
 * them found its element: choosing a vendor did not load that vendor's items,
 * a tax change recalculated nothing, adding a row did nothing. The page
 * rendered correctly and did not work, which is what made it so hard to see -
 * every page comparison passed, because the markup carries the same data
 * either way.
 *
 * Yii 2 is built for this. Its helpers are a thin `Html extends BaseHtml` so
 * that an application can replace the outer class, which config/web.php does
 * through Yii::$classMap. Everything else - labels' `for`, error containers,
 * the client-validation scripts - keeps pointing at the same ids, because
 * they all resolve through here.
 *
 * Adopting Yii 2's scheme instead would mean rewriting that javascript, in
 * both trees at once so the two keep agreeing. This is the smaller change and
 * the one that matches what the port is for.
 */
class Html extends BaseHtml
{
    /**
     * CHtml::getIdByName(): the name with its brackets replaced, case intact.
     */
    public static function getInputIdByName($name)
    {
        return str_replace(['[]', '][', '[', ']', ' '],
                           ['',   '_',  '_',  '',  '_'], $name);
    }
}
