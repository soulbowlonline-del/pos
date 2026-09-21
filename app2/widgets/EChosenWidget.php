<?php
namespace app\widgets;

use yii\base\Widget;
use yii\helpers\Json;
use yii\web\JqueryAsset;

/**
 * yii-chosen's EChosenWidget: makes a select searchable.
 *
 * It used to render nothing at all - `return '';` - so the ten views that use
 * it kept plain selects. On the reports that list every vendor or every item
 * that is a dropdown with hundreds of entries and no way to type into it.
 *
 * Yii 1 publishes chosen.jquery.js and chosen.css from the extension and calls
 * jQuery(selector).chosen(options) on ready. The same files are served here
 * from /v2 and the same call is made. Unlike the typeahead this is not the
 * difference between working and not - a plain select still selects - so it
 * was never going to be reported as a broken screen, only as an awkward one.
 */
class EChosenWidget extends Widget
{
    use IgnoresLegacyOptions;

    /** The same default the Yii 1 widget carries. */
    public $selector = '.chosen-select';
    public $options = [];

    public function run()
    {
        $view = $this->getView();
        $view->registerCssFile('/v2/css/chosen.css');
        $view->registerJsFile('/v2/js/chosen.jquery.js',
                              ['depends' => JqueryAsset::class]);
        $view->registerJs(sprintf('jQuery(%s).chosen(%s);',
                                  Json::encode($this->selector),
                                  Json::encode((object) $this->options)));

        return '';
    }
}
