<?php
namespace app\widgets;

use Closure;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/**
 * Stand-in for the CxButtonColumn / FaButtonColumn the grids use.
 *
 * Those are CButtonColumn subclasses, so their configuration is Yii 1's: a
 * `template` of button names, a `buttons` map giving each one a url, a label,
 * a visibility test and its tag attributes, and `htmlOptions` for the cell.
 * Yii 2's ActionColumn spells all of that differently, so the translation
 * happens here rather than in each of the views that configure one.
 */
class ActionColumn extends \yii\grid\ActionColumn
{
    use IgnoresLegacyOptions;

    /** @var array Yii 1's per-button configuration. */
    public $buttons = [];

    /** @var array Yii 1's name for the data cell's tag attributes. */
    public $htmlOptions = [];

    /** @var string Yii 1's name for the header cell's contents. */
    public $header;

    /** FaButtonColumn's icons for the three standard buttons. */
    private const ICONS = [
        'view' => 'fa fa-eye',
        'update' => 'fa fa-pencil-square-o',
        'delete' => 'fa fa-trash',
    ];

    public function init()
    {
        if (!empty($this->htmlOptions)) {
            $this->contentOptions = ArrayHelper::merge($this->htmlOptions, $this->contentOptions);
        }

        // Yii 1's buttons are {name} entries in the template; Yii 2 wants a
        // renderer per name. Anything the view did not configure falls through
        // to Yii 2's own default button of that name.
        $config = $this->buttons;
        $this->buttons = [];
        $this->visibleButtons = [];
        foreach (array_keys(self::ICONS) as $name) {
            if (!isset($config[$name])) {
                $config[$name] = [];
            }
        }
        foreach ($config as $name => $b) {
            $this->buttons[$name] = function ($url, $model, $key) use ($b, $name) {
                $label = ArrayHelper::getValue($b, 'label', $name);
                $options = ArrayHelper::getValue($b, 'options', []);
                if (!isset($options['title'])) {
                    $options['title'] = $label;
                }
                $href = isset($b['url']) ? $this->resolve($b['url'], $model, $key) : $url;

                // FaButtonColumn renders the icon in place of the label for the
                // three standard buttons, so the cell shows an icon and a title
                // attribute and no text. The trailing space inside the link is
                // the original's too.
                $icon = ArrayHelper::getValue($b, 'icon', ArrayHelper::getValue(self::ICONS, $name));
                $body = $icon !== null
                    ? Html::tag('i', '', ['class' => $icon]) . ' '
                    : Html::encode($label);

                return Html::a($body, $href, $options);
            };
            if (array_key_exists('visible', $b)) {
                $this->visibleButtons[$name] = $b['visible'] instanceof Closure
                    ? $b['visible']
                    : (bool) $b['visible'];
            }
        }

        parent::init();
    }

    /**
     * Yii 2 substitutes `{name}` placeholders matching [\w\-\/] only, so a
     * button whose name has a space in it is never replaced and the template
     * is printed as written: vendor's admin grid showed the literal
     * `{Mrs Details}` in every row where Yii 1 shows the button.
     *
     * Yii 1's CButtonColumn puts no such restriction on a button name, so the
     * pattern is widened rather than the application's names changed.
     */
    protected function renderDataCellContent($model, $key, $index)
    {
        return preg_replace_callback('/\\{([\\w\\-\\/ ]+)\\}/', function ($matches)
            use ($model, $key, $index) {
            $name = $matches[1];

            if (isset($this->visibleButtons[$name])) {
                $isVisible = $this->visibleButtons[$name] instanceof \Closure
                    ? call_user_func($this->visibleButtons[$name], $model, $key, $index)
                    : $this->visibleButtons[$name];
            } else {
                $isVisible = true;
            }

            if ($isVisible && isset($this->buttons[$name])) {
                $url = $this->createUrl($name, $model, $key, $index);

                return call_user_func($this->buttons[$name], $url, $model, $key);
            }

            return '';
        }, $this->template);
    }

    private function resolve($url, $model, $key)
    {
        return $url instanceof Closure ? call_user_func($url, $model, $key) : $url;
    }
}
