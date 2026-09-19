<?php
/**
 * Ported from protected/views/itemCategory/index.php.
 */

use app\models\ItemCategory;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	ItemCategory::label(2),
	'Index',
];
?>

<div class="page-header">
<h1><?php echo Html::encode(ItemCategory::label(2)); ?></h1>
</div>

<?php 


echo $this->render('_list', [
		'dataProvider'=>$dataProvider,
]);

