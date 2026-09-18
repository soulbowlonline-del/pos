<?php
/**
 * Ported from protected/views/itemDetail/index.php.
 */

use app\models\ItemDetail;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	ItemDetail::label(2),
	'Index',
];
?>

<div class="page-header">
<h1><?php echo Html::encode(ItemDetail::label(2)); ?></h1>
</div>

<?php 


echo $this->render('_list', [
		'dataProvider'=>$dataProvider,
]);

