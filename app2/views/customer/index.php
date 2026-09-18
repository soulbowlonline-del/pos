<?php
/**
 * Ported from protected/views/customer/index.php.
 */

use app\models\Customer;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	Customer::label(2),
	'Index',
];
?>

<div class="page-header">
<h1><?php echo Html::encode(Customer::label(2)); ?></h1>
</div>

<?php 


echo $this->render('_list', [
		'dataProvider'=>$dataProvider,
]);

