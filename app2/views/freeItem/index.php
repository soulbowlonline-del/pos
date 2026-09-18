<?php
/**
 * Ported from protected/views/freeItem/index.php.
 */

use app\models\FreeItem;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	FreeItem::label(2),
	'Index',
];
?>

<div class="page-header">
<h1><?php echo Html::encode(FreeItem::label(2)); ?></h1>
</div>

<?php 


echo $this->render('_list', [
		'dataProvider'=>$dataProvider,
]);

