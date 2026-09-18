<?php
/**
 * Ported from protected/views/orderRefundItem/index.php.
 */

use app\models\OrderRefundItem;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	OrderRefundItem::label(2),
	'Index',
];
?>

<div class="page-header">
<h1><?php echo Html::encode(OrderRefundItem::label(2)); ?></h1>
</div>

<?php 


echo $this->render('_list', [
		'dataProvider'=>$dataProvider,
]);

