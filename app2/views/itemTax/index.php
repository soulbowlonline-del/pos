<?php
/**
 * Ported from protected/views/itemTax/index.php.
 */

use app\models\ItemTax;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	ItemTax::label(2),
	'Index',
];
?>

<div class="page-header">
<h1><?php echo Html::encode(ItemTax::label(2)); ?></h1>
</div>

<?php 


echo $this->render('_list', [
		'dataProvider'=>$dataProvider,
]);

