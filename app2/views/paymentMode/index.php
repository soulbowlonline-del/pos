<?php
/**
 * Ported from protected/views/paymentMode/index.php.
 */

use app\models\PaymentMode;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	PaymentMode::label(2),
	'Index',
];
?>

<div class="page-header">
<h1><?php echo Html::encode(PaymentMode::label(2)); ?></h1>
</div>

<?php 


echo $this->render('_list', [
		'dataProvider'=>$dataProvider,
]);

