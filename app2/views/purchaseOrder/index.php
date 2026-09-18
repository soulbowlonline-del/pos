<?php
/**
 * Ported from protected/views/purchaseOrder/index.php.
 */

use app\models\PurchaseOrder;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	PurchaseOrder::label(2),
	'Index',
];
?>

<div class="page-header">
<h1><?php echo Html::encode(PurchaseOrder::label(2)); ?></h1>
</div>

<?php 


echo $this->render('_list', [
		'dataProvider'=>$dataProvider,
]);

