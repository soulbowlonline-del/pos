<?php
/**
 * Ported from protected/views/advancePayment/index.php.
 */

use app\models\AdvancePayment;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	AdvancePayment::label(2),
	'Index',
];
?>

<div class="page-header">
<h1><?php echo Html::encode(AdvancePayment::label(2)); ?></h1>
</div>

<?php 


echo $this->render('_list', [
		'dataProvider'=>$dataProvider,
]);

