<?php
/**
 * Ported from protected/views/bill/index.php.
 */

use app\models\Bill;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	Bill::label(2),
	'Index',
];
?>

<div class="page-header">
<h1><?php echo Html::encode(Bill::label(2)); ?></h1>
</div>

<?php 


echo $this->render('_list', [
		'dataProvider'=>$dataProvider,
]);

