<?php
namespace app\components;

use yii\helpers\ArrayHelper;

/**
 * The GxHtml / GxActiveRecord helpers the views call.
 *
 * giix generated these into every view, so they appear a few hundred times
 * across the 652 files being ported. Reimplementing them here is what lets a
 * view be transformed rather than rewritten.
 */
class Gx
{
    /**
     * GxHtml::valueEx($model): the model's string form, or null.
     *
     * Yii 1 returned null for a null model rather than failing, and several
     * views rely on that to render an empty cell for a missing relation.
     */
    public static function str($model)
    {
        return $model === null ? null : (string) $model;
    }

    /**
     * GxHtml::listDataEx(Model::model()->findAllAttributes(null, true)):
     * id => label for every row, for a dropdown or a grid filter.
     */
    public static function listData($class)
    {
        $out = [];

        // findAllAttributes(null, true) selects the primary key and the
        // representing column and nothing else, under whatever
        // defaultScope() the model declares. Both details matter: the model
        // decides the order - several override the application-wide
        // `id DESC` to no ordering at all - and the narrow select is what
        // decides the order when there is none, because MySQL answers it from
        // an index rather than the table.
        $query = $class::find();
        if (method_exists($class, 'representingColumn')) {
            $query->select(['id', $class::representingColumn()]);
        }
        // A model that does not declare one inherits GxActiveRecord's
        // `id DESC`; only an explicit defaultOrder() of null means none.
        $order = method_exists($class, 'defaultOrder')
            ? $class::defaultOrder()
            : ['id' => SORT_DESC];
        if ($order) {
            $query->orderBy($order);
        }

        foreach ($query->all() as $row) {
            $out[$row->primaryKey] = (string) $row;
        }
        return $out;
    }

    /** GxActiveRecord::extractPkValue($model, true) */
    public static function pk($model)
    {
        return $model === null ? null : $model->primaryKey;
    }

    /**
     * A Yii 1 url specification - either a route string or
     * ['route', 'k' => v] - resolved through Ui so it reaches whichever stack
     * currently serves that controller.
     */
    public static function url($spec)
    {
        if (is_string($spec)) {
            return Ui::to($spec);
        }
        if (!is_array($spec) || !isset($spec[0])) {
            return $spec;
        }
        $route = $spec[0];
        unset($spec[0]);

        return Ui::to($route, $spec);
    }
}
