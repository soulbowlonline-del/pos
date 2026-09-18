<?php
namespace app\widgets;

/**
 * A checkbox column that accepts Yii 1's options.
 *
 * CCheckBoxColumn has settings Yii 2's CheckboxColumn does not - selectableRows
 * among them, which chose between none, one and many and is decided in Yii 2 by
 * multiple() and the header checkbox. The column is configured through
 * Yii::createObject, which throws on an unknown name, so the shim absorbs them.
 */
class CheckboxColumn extends \yii\grid\CheckboxColumn
{
    use IgnoresLegacyOptions;
}
