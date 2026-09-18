<?php
namespace app\models;

/**
 * Keeps column values as the strings Yii 1 handed out.
 *
 * Yii 1 runs PDO with ATTR_STRINGIFY_FETCHES, so every column arrives as a
 * string: an integer 0 is '0'. Yii 2's ActiveRecord::populateRecord() casts
 * according to the column type, so the same row gives integer 0.
 *
 * That difference is not cosmetic, because the application compares loosely.
 * The generated option helpers all begin:
 *
 *     public static function getStatusOptions($id = null) {
 *         if ($id == null) return $list;
 *
 * With Yii 1's '0' that test is false and the caller gets 'Active'. With
 * Yii 2's 0 it is true and the caller gets the entire options array, which
 * the grid then renders as the word "Array" - or, under Yii 2's error
 * handler, raises "Array to string conversion" and returns a 500.
 *
 * Skipping the typecast is what keeps the two stacks agreeing. Fixing the
 * `== null` tests instead would be the better change, but there are hundreds
 * of them and each is a behaviour the owner may be relying on.
 */
trait LegacyColumnTypes
{
    public static function populateRecord($record, $row)
    {
        \yii\db\BaseActiveRecord::populateRecord($record, $row);
    }

    /**
     * Answers null for a property the model does not have.
     *
     * The views read attributes that no longer exist - `$data->item` on a
     * model whose relations list has no `item`. Yii 1 answered null and the
     * grid rendered an empty cell; Yii 2 throws "Getting unknown property" and
     * the page is a 500. Several pages in this application are only reachable
     * because of that null.
     *
     * Reproducing it keeps those pages working. It also hides a genuine typo,
     * which is why the difference is written down in docs/web-ui-port.md
     * rather than left to be discovered.
     */
    public function __get($name)
    {
        try {
            return parent::__get($name);
        } catch (\yii\base\UnknownPropertyException $e) {
            return null;
        }
    }
}
