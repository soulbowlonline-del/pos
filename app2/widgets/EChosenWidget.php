<?php
namespace app\widgets;

use yii\base\Widget;

/**
 * Port of the EChosen extension, which renders nothing.
 *
 * EChosenWidget attaches the Chosen plugin to selects that already exist on
 * the page - the views render those with activeListBox and then call this to
 * enhance them. All it contributes is JavaScript and a stylesheet, neither of
 * which is part of this port, so the markup it produces is empty either way.
 *
 * The selects themselves still render, with their `chosen` class intact; they
 * are plain multi-selects rather than the Chosen widget. Listed in
 * docs/web-ui-port.md with the other JavaScript that did not come across.
 */
class EChosenWidget extends Widget
{
    use IgnoresLegacyOptions;

    public function run()
    {
        return '';
    }
}
