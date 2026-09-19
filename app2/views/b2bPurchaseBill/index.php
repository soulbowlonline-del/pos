<?php
/**
 * Ported from protected/views/b2bpurchaseBill/index.php.
 */

use app\models\PurchaseBill;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	PurchaseBill::label(2),
	'Index',
];
?>

<div class="page-header">
<h1><?php echo Html::encode(PurchaseBill::label(2)); ?></h1>
</div>

<?php 


echo $this->render('_list', [
		'dataProvider'=>$dataProvider,
]);

