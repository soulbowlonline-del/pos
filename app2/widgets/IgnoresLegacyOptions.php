<?php
namespace app\widgets;

/**
 * Accepts, and ignores, YiiBooster options that have no counterpart here.
 *
 * The views configure their widgets for Yii 1: afterAjaxUpdate, ajaxUrl,
 * enableHistory, the jQuery UI plugin settings. Those drive JavaScript that is
 * not part of this port. Yii 2 throws on an unknown property, which would make
 * a 500 of every page that sets one.
 *
 * Ignoring them is safe only because of the differential suite: if an option
 * did affect what the page says, the comparison against Yii 1 fails. Without
 * that check this would be a way to make pages render while being wrong.
 *
 * The assignment goes through the parent first and is only discarded when the
 * parent itself rejects it. A first version tested property_exists() instead
 * and swallowed 'id' - which Yii 2 takes through setId(), not a property - so
 * every grid rendered without the id its page and its tests refer to.
 */
trait IgnoresLegacyOptions
{
    public function __set($name, $value)
    {
        try {
            parent::__set($name, $value);
        } catch (\yii\base\UnknownPropertyException $e) {
            // a Yii 1 option this port has no use for
        }
    }
}
