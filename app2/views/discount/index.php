<?php
/**
 * Ported from protected/views/discount/index.php.
 */

use app\models\Discount;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	Discount::label(2),
	'Index',
];
?>

<div class="page-header">
<h1><?php echo Html::encode(Discount::label(2)); ?></h1>
</div>

<?php 


echo $this->render('_list', [
		'dataProvider'=>$dataProvider,
]);

