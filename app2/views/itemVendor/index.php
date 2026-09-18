<?php
/**
 * Ported from protected/views/itemVendor/index.php.
 */

use app\models\ItemVendor;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	ItemVendor::label(2),
	'Index',
];
?>

<div class="page-header">
<h1><?php echo Html::encode(ItemVendor::label(2)); ?></h1>
</div>

<?php 


echo $this->render('_list', [
		'dataProvider'=>$dataProvider,
]);

