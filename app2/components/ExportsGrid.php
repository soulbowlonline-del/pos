<?php
namespace app\components;

use Yii;
use yii\data\DataProviderInterface;
use yii\helpers\ArrayHelper;

/**
 * Port of protected/components/ExportableGridBehavior.
 *
 * Yii 1 attached this to controllers as a behavior; Yii 2 has no behaviors
 * with the same reach, so the two methods the controllers actually call are a
 * trait on the base controller instead.
 *
 * `exportCSV` writes straight to the output and ends the request, which is
 * what the Yii 1 version does - the admin action calls it before render() and
 * expects never to return.
 */
trait ExportsGrid
{
    /** The query parameter that asks for a CSV instead of the page. */
    public $exportParam = 'exportCSV';

    public $csvDelimiter = ',';
    public $csvEnclosure = '"';
    public $csvFilename = 'export.csv';

    /** Whether this request asked for the CSV. */
    public function isExportRequest()
    {
        return Yii::$app->request->get($this->exportParam, false);
    }

    /**
     * @param mixed $data       a data provider, a model, or a row of strings
     * @param array $attributes attribute names, as the Yii 1 call sites pass
     * @param bool  $end        end the request afterwards, as Yii 1 does
     * @param int   $endLineCount blank lines to append
     */
    public function exportCSV($data, $attributes = [], $end = true, $endLineCount = 0)
    {
        if (!$this->isExportRequest()) {
            return;
        }

        $response = Yii::$app->response;
        $response->format = \yii\web\Response::FORMAT_RAW;
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition',
            'attachment; filename="' . $this->csvFilename . '"');

        ob_start();
        $fh = fopen('php://output', 'w');

        if ($data instanceof DataProviderInterface) {
            $models = $data->getModels();
            if ($models) {
                $this->csvHeaders($fh, $attributes, reset($models));
            }
            $this->csvRows($fh, $models, $attributes);
        } elseif (is_array($data) && current($data) instanceof \yii\base\Model) {
            $this->csvHeaders($fh, $attributes, current($data));
            $this->csvRows($fh, $data, $attributes);
        } elseif (is_array($data) && is_string(current($data))) {
            fputcsv($fh, $data, $this->csvDelimiter, $this->csvEnclosure);
        } elseif ($data instanceof \yii\base\Model) {
            $this->csvHeaders($fh, $attributes, $data);
            $this->csvRows($fh, [$data], $attributes);
        }

        fprintf($fh, str_repeat("\n", $endLineCount));
        fclose($fh);
        $response->content = ob_get_clean();

        if ($end) {
            $response->send();
            Yii::$app->end();
        }
    }

    /**
     * The "Export" link above a grid.
     *
     * Yii 1's version echoed the link and registered a script that read the
     * grid's current URL out of its yiiGridView plugin state and reopened it
     * with the export parameter. There is no such plugin here, so the link
     * carries the current query plus that parameter, which is the same request
     * the script would have made.
     */
    public function renderExportGridButton($grid, $label = 'Export', $htmlOptions = [])
    {
        $params = Yii::$app->request->queryParams;
        $params[$this->exportParam] = 1;
        $params[0] = Yii::$app->controller->getRoute();

        echo \yii\helpers\Html::a(\yii\helpers\Html::encode($label),
            \yii\helpers\Url::to($params), $htmlOptions);
    }

    private function csvHeaders($fh, $attributes, $model)
    {
        $row = [];
        foreach ($attributes as $attr) {
            if (is_array($attr)) {
                $row[] = $attr['label']
                    ?? (isset($attr['name']) ? $model->getAttributeLabel($attr['name']) : '');
            } else {
                $row[] = $model->getAttributeLabel($attr);
            }
        }
        fputcsv($fh, $row, $this->csvDelimiter, $this->csvEnclosure);
    }

    private function csvRows($fh, $models, $attributes)
    {
        foreach ($models as $model) {
            $row = [];
            foreach ($attributes as $attr) {
                if (is_array($attr)) {
                    if (isset($attr['name'])) {
                        $row[] = ArrayHelper::getValue($model, $attr['name']);
                    } elseif (isset($attr['value'])) {
                        $row[] = $attr['value'] instanceof \Closure
                            ? call_user_func($attr['value'], $model)
                            : $attr['value'];
                    } else {
                        $row[] = '';
                    }
                } else {
                    $row[] = ArrayHelper::getValue($model, $attr);
                }
            }
            fputcsv($fh, $row, $this->csvDelimiter, $this->csvEnclosure);
        }
    }
}
