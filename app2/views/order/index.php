<?php
/**
 * Ported from protected/views/order/index.php.
 */

use app\models\Order;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	Order::label(2),
	'Index',
];
?>

<div class="page-header">
<h1><?php echo Html::encode(Order::label(2)); ?></h1>
</div>

<?php 


echo $this->render('_list', [
		'dataProvider'=>$dataProvider,
]);

