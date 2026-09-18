<?php
/**
 * Ported from protected/views/itemExpire/index.php.
 */

use app\models\ItemExpire;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	ItemExpire::label(2),
	'Index',
];
?>

<div class="page-header">
<h1><?php echo Html::encode(ItemExpire::label(2)); ?></h1>
</div>

<?php 


echo $this->render('_list', [
		'dataProvider'=>$dataProvider,
]);

