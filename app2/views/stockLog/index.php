<?php
/**
 * Ported from protected/views/stockLog/index.php.
 */

use app\models\StockLog;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	StockLog::label(2),
	'Index',
];
?>

<div class="page-header">
<h1><?php echo Html::encode(StockLog::label(2)); ?></h1>
</div>

<?php 


echo $this->render('_list', [
		'dataProvider'=>$dataProvider,
]);

