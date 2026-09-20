<?php
namespace app\widgets;

/**
 * CLinkPager's configuration, on Yii 2's pager.
 *
 * The grids configure their pager the Yii 1 way - `htmlOptions` for the
 * container, and CLinkPager's own label properties. Yii 2 calls the first
 * `options` and rejects anything it does not know, so user/admin died on
 * "Setting unknown property: yii\widgets\LinkPager::htmlOptions".
 *
 * Only the names the views actually pass are translated. Anything else still
 * reaches the parent and still fails loudly, which is the point: a silently
 * ignored option is a pager that renders differently and says nothing.
 */
class LinkPager extends \yii\widgets\LinkPager
{
    /** CLinkPager's name for the container's HTML attributes. */
    public $htmlOptions = [];

    /**
     * @var \yii\data\Pagination CLinkPager's name for the pagination object.
     *
     * A view that renders a pager by hand passes it as `pages`;
     * item/printBarcode does. Yii 2 calls the property `pagination` and
     * refuses the other name outright.
     */
    public $pages;

    /** CLinkPager's labels. Yii 2 spells them ...PageLabel as well. */
    public $header;

    public $footer = '';

    public function init()
    {
        if (!empty($this->htmlOptions)) {
            $this->options = array_merge($this->options, $this->htmlOptions);
        }
        if ($this->pages !== null && $this->pagination === null) {
            $this->pagination = $this->pages;
        }

        // CLinkPager's own defaults, which the two frameworks do not share:
        // Yii 2 shows `« 1 2 3 »` and hides the first and last buttons
        // altogether, where Yii 1 writes "Go to page: << First < Previous 1 2
        // 3 Next > Last >>". 57 ported views ask for the default pager, so
        // every paginated grid in the port had the wrong one - the UI suite
        // compares the rows of a grid, not the pager under it, which is why
        // this survived 319 green comparisons.
        //
        // The entities are written as Yii 1 writes them, and rendered raw by
        // both, so the reader sees `<< First` on either stack.
        $this->applyDefaults();

        parent::init();
    }

    /**
     * CLinkPager's defaults, for a subclass to replace.
     *
     * The sentinels are Yii 2's own defaults for these properties, so a value
     * the view set explicitly is left alone.
     */
    protected function applyDefaults()
    {
        if ($this->firstPageLabel === false) {
            $this->firstPageLabel = '&lt;&lt; First';
        }
        if ($this->lastPageLabel === false) {
            $this->lastPageLabel = 'Last &gt;&gt;';
        }
        if ($this->prevPageLabel === '&laquo;') {
            $this->prevPageLabel = '&lt; Previous';
        }
        if ($this->nextPageLabel === '&raquo;') {
            $this->nextPageLabel = 'Next &gt;';
        }
        if ($this->header === null) {
            $this->header = 'Go to page: ';
        }
    }

    /**
     * CLinkPager prints a header before the links and a footer after them,
     * and prints neither when there is only one page. Yii 2 has no such
     * properties, so the header was being dropped.
     */
    public function run()
    {
        $buttons = $this->renderPageButtons();
        if ($buttons === '') {
            return;
        }
        if ($this->registerLinkTags) {
            $this->registerLinkTags();
        }
        echo $this->header . $buttons . $this->footer;
    }
}
