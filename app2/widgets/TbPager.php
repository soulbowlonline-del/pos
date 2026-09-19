<?php
namespace app\widgets;

/**
 * Stand-in for bootstrap.widgets.TbPager.
 *
 * TbGridView's own default pager, which is not CLinkPager: no "Go to page:"
 * header, arrows instead of words, and `displayFirstAndLast = false`, so no
 * First or Last button at all.
 *
 * Both pagers are in use, and which one a page gets turns on a detail of
 * CGridView::renderPager(): it reads a `pager` that is not an array as "the
 * framework default", and the framework default is CLinkPager. So the 57
 * views that write `'pager' => true` get CLinkPager *even though they are
 * TbGridViews*, and the views that say nothing keep TbPager. Giving every
 * grid the same pager is wrong either way round.
 */
class TbPager extends LinkPager
{
    protected function applyDefaults()
    {
        // TbPager::$displayFirstAndLast is false, and createPageButtons()
        // skips both buttons entirely rather than rendering them disabled.
        // Yii 2 spells that as a label of false.
        if ($this->prevPageLabel === '&laquo;') {
            $this->prevPageLabel = '&larr;';
        }
        if ($this->nextPageLabel === '&raquo;') {
            $this->nextPageLabel = '&rarr;';
        }
        if ($this->header === null) {
            $this->header = '';
        }
    }
}
