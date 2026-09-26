<?php

namespace app\components;
/**
 * Integer ids read from $_POST for SQL conditions.
 *
 * These ids used to be concatenated into the condition, so a missing, empty or
 * non-numeric value broke the query and the action answered HTTP 500. Page
 * scripts depend on that: the GRN and MRS screens call ajaxItems and the other
 * ajax lookups on load with an empty id, and only act when the call succeeds.
 * An empty 200 made them check an empty barcode, alert "Scanned Item is not of
 * Active" and reload forever. So a value that is not an integer still fails
 * the request (400 now, not 500), and a valid one is bound, never concatenated.
 */
class PostId
{
	public static function get($key, $index = null)
	{
		$value = isset($_POST[$key]) ? $_POST[$key] : null;
		if ($index !== null) {
			$value = (is_array($value) && isset($value[$index])) ? $value[$index] : null;
		}
		if (is_int($value) || (is_string($value) && preg_match("/^\s*-?\d+\s*$/", $value))) {
			return (int) $value;
		}
		throw new \yii\web\BadRequestHttpException("Invalid " . $key . ".");
	}
}
