<?php
/**
 * Ported from protected/views/itemReturn/index.php.
 */

use app\models\ItemReturn;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	ItemReturn::label(2),
	'Index',
];
?>

<div class="page-header">
<h1><?php echo Html::encode(ItemReturn::label(2)); ?></h1>
</div>

<?php 


echo $this->render('_list', [
		'dataProvider'=>$dataProvider,
]);

