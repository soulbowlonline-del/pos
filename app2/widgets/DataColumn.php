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

    /**
     * Renders the filter whenever the column defines one.
     *
     * Yii 2 only builds a filter input for an attribute that is safe in the
     * model's current scenario. Yii 1 has no such rule: CGridView renders a
     * filter for any column that names one, and the generated `safe` list does
     * not always cover them - StockAdjustLog's omits create_user_id, whose
     * dropdown Yii 1 shows and Yii 2 silently left out.
     *
     * Only the safety check is bypassed. What the filter does with the value
     * is unchanged: search() compares the attributes it knows about, exactly
     * as Yii 1's does.
     */
    protected function renderFilterCellContent()
    {
        if ($this->filter === false || $this->grid->filterModel === null
                || $this->attribute === null) {
            return parent::renderFilterCellContent();
        }

        $model = $this->grid->filterModel;
        // Yii 2 suppresses the id on a filter input - its own
        // DataColumn::$filterInputOptions carries 'id' => null. Yii 1's
        // CGridView gives each one an id: Item_bar_code, Customer_city_id,
        // Tax_columns_0. The application's javascript addresses them by those
        // ids, so a suppressed id is a filter box that nothing can drive.
        // Keeping the id lets Html::getInputId - which this port has replaced
        // with Yii 1's scheme - name it the way the scripts expect.
        $options = $this->filterInputOptions;
        unset($options['id']);

        if (is_array($this->filter)) {
            $options['prompt'] = $this->filterInputOptions['prompt'] ?? '';
            return \yii\helpers\Html::activeDropDownList(
                $model, $this->attribute, $this->filter, $options);
        }

        return \yii\helpers\Html::activeTextInput($model, $this->attribute, $options);
    }

    public function init()
    {
        if (!empty($this->htmlOptions)) {
            $this->contentOptions = ArrayHelper::merge($this->htmlOptions, $this->contentOptions);
        }
        parent::init();
    }
}
