<?php
/**
 * Ported from protected/views/itemExpireItem/index.php.
 */

use app\models\ItemExpireItem;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	ItemExpireItem::label(2),
	'Index',
];
?>

<div class="page-header">
<h1><?php echo Html::encode(ItemExpireItem::label(2)); ?></h1>
</div>

<?php 


echo $this->render('_list', [
		'dataProvider'=>$dataProvider,
]);

