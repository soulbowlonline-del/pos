<?php
/**
 * Ported from protected/views/empShift/index.php.
 */

use app\models\EmpShift;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	EmpShift::label(2),
	'Index',
];
?>

<div class="page-header">
<h1><?php echo Html::encode(EmpShift::label(2)); ?></h1>
</div>

<?php 


echo $this->render('_list', [
		'dataProvider'=>$dataProvider,
]);

