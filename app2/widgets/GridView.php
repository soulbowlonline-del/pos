<?php
namespace app\widgets;

use yii\helpers\ArrayHelper;

/**
 * Stand-in for bootstrap.widgets.TbGridView.
 *
 * Yii 2's own grid does the work; this only translates TbGridView's `type`
 * string - 'striped bordered condensed' - into the table classes the theme's
 * CSS expects, and turns off the summary line and the "no results" caption
 * that Yii 2 adds and Yii 1 does not.
 */
class GridView extends \yii\grid\GridView
{
    use IgnoresLegacyOptions;

    /** @var string space-separated TbGridView table flavours */
    public $type = '';

    /** @var array TbGridView's name for the container's tag attributes. */
    public $htmlOptions = [];

    /** @var \yii\base\Model TbGridView calls the filter model `filter`. */
    public $filter;

    /** @var string CBaseListView's name for the summary line. */
    public $summaryText;

    /**
     * @var array TbGridView's default pager. Yii 2's GridView defaults this
     *            to [], which would be indistinguishable from a view that
     *            asked for the framework default with `'pager' => true`.
     */
    public $pager = ['class' => TbPager::class];

    public function init()
    {
        // Plain columns go through the shim too, so a column written with
        // Yii 1's htmlOptions does not fail to construct.
        if ($this->dataColumnClass === null) {
            $this->dataColumnClass = DataColumn::class;
        }

        if ($this->filter !== null && $this->filterModel === null) {
            $this->filterModel = $this->filter;
        }
        if (!empty($this->htmlOptions)) {
            $this->options = ArrayHelper::merge($this->htmlOptions, $this->options);
        }
        $classes = ['table'];
        foreach (preg_split('/\s+/', trim($this->type)) as $t) {
            if ($t !== '') {
                $classes[] = 'table-' . $t;
            }
        }
        $this->tableOptions = ArrayHelper::merge(
            ['class' => implode(' ', $classes)],
            $this->tableOptions
        );

        // Which pager, and it is not one answer for every grid.
        //
        // CGridView::renderPager() reads a `pager` that is not an array as
        // "the framework default", and that default is CLinkPager - so the 57
        // views writing `'pager' => true` get CLinkPager's "Go to page: <<
        // First" even though they are TbGridViews. A view that says nothing
        // keeps TbGridView's own default, TbPager, which has no header and
        // uses arrows. Yii 2's pager is neither.
        if (!is_array($this->pager)) {
            $this->pager = $this->pager
                ? ['class' => LinkPager::class]
                : ['class' => \yii\widgets\LinkPager::class, 'options' => ['style' => 'display:none']];
        } elseif (!isset($this->pager['class'])) {
            $this->pager['class'] = TbPager::class;
        }

        // CGridColumn::$visible. Yii 2 has no such property, so a column
        // Yii 1 hides was rendered anyway - purchaseOrderDetail's grid shows
        // six tax columns for a GST order and two for an IGST one, and the
        // port showed all eight. Dropped here rather than passed on, because
        // Yii 2's DataColumn would reject the key.
        $visible = [];
        foreach ($this->columns as $column) {
            if (is_array($column) && array_key_exists('visible', $column)) {
                if (!$column['visible']) {
                    continue;
                }
                unset($column['visible']);
            }
            $visible[] = $column;
        }
        $this->columns = $visible;

        // A column carrying a 'footer' means the grid has a totals row. Yii 1
        // renders it whenever any column defines one; Yii 2 needs to be told,
        // and without it the table is one row shorter than the original.
        foreach ($this->columns as $column) {
            if (is_array($column) && isset($column['footer'])) {
                $this->showFooter = true;
                break;
            }
        }

        // CBaseListView::$summaryText, which the trait above was quietly
        // swallowing - loyaltyAdmin/customers prints "Showing 1-20 of 5141
        // customers" and the port printed nothing at all.
        //
        // The placeholders are not the same. Yii 1's {start} is Yii 2's
        // {begin}, its {pages} is {pageCount}, and - the one that matters -
        // its {count} is the total number of rows, which Yii 2 calls
        // {totalCount}; Yii 2's own {count} is how many rows this page shows.
        // Passing the string through unchanged would have read "of 20".
        if ($this->summaryText !== null && $this->summary === null) {
            $this->summary = strtr($this->summaryText, [
                '{start}' => '{begin}',
                '{count}' => '{totalCount}',
                '{pages}' => '{pageCount}',
            ]);
        }

        if ($this->summary === null) {
            $this->summary = '';
        }
        // CGridView's own default, which the views do not override. An earlier
        // guess that Yii 1 rendered nothing here was wrong: it prints this.
        if ($this->emptyText === null) {
            $this->emptyText = 'No results found.';
        }

        parent::init();
    }
}
