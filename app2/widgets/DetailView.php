<?php
namespace app\widgets;

use yii\helpers\ArrayHelper;

/**
 * Stand-in for bootstrap.widgets.TbDetailView.
 *
 * Takes `data` where Yii 2 wants `model`, so the ported view keeps the
 * original's key, and applies the same table classes.
 */
class DetailView extends \yii\widgets\DetailView
{
    /** @var object alias for TbDetailView's `data` */
    public $data;

    /** @var array TbDetailView's name for the table's tag attributes. */
    public $htmlOptions = [];

    public function init()
    {
        if ($this->data !== null && $this->model === null) {
            $this->model = $this->data;
        }
        // CDetailView prints "Not set" for a null attribute, where CGridView
        // prints an empty cell. The application's formatter is configured for
        // the grid, so the detail view carries its own.
        if ($this->formatter === null) {
            $this->formatter = ['class' => \yii\i18n\Formatter::class, 'nullDisplay' => 'Not set'];
        }
        $this->options = ArrayHelper::merge(
            ['class' => 'table table-striped table-bordered detail-view'],
            $this->htmlOptions,
            $this->options
        );
        parent::init();
    }
}
