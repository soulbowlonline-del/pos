<?php
/**
 * Ported from protected/views/shift/index.php.
 */

use app\models\Shift;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	Shift::label(2),
	'Index',
];
?>

<div class="page-header">
<h1><?php echo Html::encode(Shift::label(2)); ?></h1>
</div>

<?php 


echo $this->render('_list', [
		'dataProvider'=>$dataProvider,
]);

