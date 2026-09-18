<?php
/**
 * Ported from protected/views/orderRefund/index.php.
 */

use app\models\OrderRefund;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	OrderRefund::label(2),
	'Index',
];
?>

<div class="page-header">
<h1><?php echo Html::encode(OrderRefund::label(2)); ?></h1>
</div>

<?php 


echo $this->render('_list', [
		'dataProvider'=>$dataProvider,
]);

