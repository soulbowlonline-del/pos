<?php
/**
 * Ported from protected/views/itemDetail/create.php.
 */

use app\components\Ui;
?>
<?php
$this->params['breadcrumbs'] = [
	$model->label(2) => ['index'],
	'Create',
];
?>
<section class="content-header">
<div class="row">
<div class="col-md-12 margin10">
<a href="<?php echo Ui::to('item/create',array('id'=>$id));?>" class="btn btn-primary">Product Info</a>
<a href="<?php echo Ui::to('item/extra',array('id'=>$id));?>" class="btn btn-primary">Extra Info</a>
<a href="<?php echo Ui::to('item/vendor',array('id'=>$id));?>" class="btn btn-primary">Vendor Information</a>
<a href="<?php echo Ui::to('stockLog/admin',array('id'=>$id));?>" class="btn btn-primary">Movement History</a>
<a href="<?php echo Ui::to('orderItem/index',array('id'=>$id));?>" class="btn btn-primary">Order History</a>
<a href="<?php echo Ui::to('itemDetail/create',array('id'=>$id));?>" class="btn btn-primary">Add Subitem</a>
<a href="<?php echo Ui::to('vendorSchemes/add',array('id'=>$id));?>" class="btn btn-primary">Add Vendor Scheme</a>

</div>
</div>
<h1 class="pull-left"><?php echo 'Create SubItem'; ?>
	<a href="<?php echo Ui::to('itemDetail/admin',array('id'=>$id));?>" class="btn btn-info pull-right" >List</a>
</h1>
</section>
<?php
echo $this->render('_form', [
		'model' => $model,'id'=>$id,
		'buttons' => 'create']);
?>
