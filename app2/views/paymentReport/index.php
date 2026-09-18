<?php
/**
 * Ported from protected/views/paymentReport/index.php.
 */

use app\models\PaymentReport;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	PaymentReport::label(2),
	'Index',
];
?>

<div class="page-header">
<h1><?php echo Html::encode(PaymentReport::label(2)); ?></h1>
</div>

<?php 


echo $this->render('_list', [
		'dataProvider'=>$dataProvider,
]);

