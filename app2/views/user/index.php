<?php
/**
 * Ported from protected/views/user/index.php.
 */

use app\models\User;
use app\widgets\ButtonGroup;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	User::label(2),
	'Index',
];
?>

<div class="page-header">
<h1 class="pull-left">
<?php echo Html::encode(User::label(2)); ?>
</h1>
<?php   echo ButtonGroup::widget([
	'buttons'=>$this->context->menu,
	'type'=>'success',
	'htmlOptions'=>['class'=> 'pull-right btns'],
	]);
?>
<div class="clearfix"></div>
</div>

<?php 
echo $this->render('_list', [
		'dataProvider'=>$dataProvider,
]);

