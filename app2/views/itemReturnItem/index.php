<?php
/**
 * Ported from protected/views/itemReturnItem/index.php.
 */

use app\models\ItemReturnItem;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	ItemReturnItem::label(2),
	'Index',
];
?>

<div class="page-header">
<h1><?php echo Html::encode(ItemReturnItem::label(2)); ?></h1>
</div>

<?php 


echo $this->render('_list', [
		'dataProvider'=>$dataProvider,
]);

