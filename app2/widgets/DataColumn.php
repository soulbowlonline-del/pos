<?php
namespace app\widgets;

use yii\helpers\ArrayHelper;

/**
 * A grid column that accepts Yii 1's option names.
 *
 * CDataColumn calls the data cell's tag attributes `htmlOptions`; Yii 2 calls
 * them `contentOptions`. The views are full of the former, and a column is not
 * a widget, so IgnoresLegacyOptions does not apply - Yii 2 configures columns
 * with Yii::createObject and throws on the unknown name.
 */
class DataColumn extends \yii\grid\DataColumn
{
    use IgnoresLegacyOptions;

    /** @var array CDataColumn's name for the data cell's tag attributes. */
    public $htmlOptions = [];

    public function init()
    {
        if (!empty($this->htmlOptions)) {
            $this->contentOptions = ArrayHelper::merge($this->htmlOptions, $this->contentOptions);
        }
        parent::init();
    }
}
