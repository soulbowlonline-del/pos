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

    /** CLinkPager's labels. Yii 2 spells them ...PageLabel as well. */
    public $header;

    public $footer;

    public function init()
    {
        if (!empty($this->htmlOptions)) {
            $this->options = array_merge($this->options, $this->htmlOptions);
        }

        // Yii 1 prints a header above the links - "Go to page:" by default -
        // and Yii 2 has no equivalent. An empty one is the common case in
        // these views and means the same thing on both.
        if ($this->header !== null && $this->header !== '') {
            $this->options['data-header'] = $this->header;
        }

        parent::init();
    }
}
