<?php
/**
 * Ported from protected/views/vendorSchemes/add.php.
 */

use app\components\Ui;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	$model->label(2) => ['index'],
	'Create',
];
?>
<section class="content">
<a href="<?php echo Ui::to('item/create',array('id'=>$id));?>" class="btn btn-primary">Product Info</a>
<a href="<?php echo Ui::to('item/extra',array('id'=>$id));?>" class="btn btn-primary">Extra Info</a>
<a href="<?php echo Ui::to('item/vendor',array('id'=>$id));?>" class="btn btn-primary">Vendor Information</a>
<a href="<?php echo Ui::to('stockLog/admin',array('id'=>$id));?>" class="btn btn-primary">Movement History</a>
<a href="<?php echo Ui::to('orderItem/index',array('id'=>$id));?>" class="btn btn-primary">Order History</a>
<a href="<?php echo Ui::to('itemDetail/create',array('id'=>$id));?>" class="btn btn-primary">Add Subitem</a>
<a href="<?php echo Ui::to('vendorSchemes/add',array('id'=>$id));?>" class="btn btn-primary">Add Vendor Scheme</a>
<div class="page-header">
<h1><?php echo 'Create' . ' ' . Html::encode($model->label()); ?></h1>
</div>
<?php
echo $this->render('_form', [
		'model' => $model,
		'buttons' => 'create']);
?></section>