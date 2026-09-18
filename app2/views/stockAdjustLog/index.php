<?php
/**
 * Ported from protected/views/stockAdjustLog/index.php.
 */

use app\models\StockAdjustLog;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	StockAdjustLog::label(2),
	'Index',
];
?>

<div class="page-header">
<h1><?php echo Html::encode(StockAdjustLog::label(2)); ?></h1>
</div>

<?php 


echo $this->render('_list', [
		'dataProvider'=>$dataProvider,
]);

