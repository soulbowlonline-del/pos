<?php
/**
 * Ported from protected/views/designation/index.php.
 */

use app\models\Designation;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	Designation::label(2),
	'Index',
];
?>

<div class="page-header">
<h1><?php echo Html::encode(Designation::label(2)); ?></h1>
</div>

<?php 


echo $this->render('_list', [
		'dataProvider'=>$dataProvider,
]);

