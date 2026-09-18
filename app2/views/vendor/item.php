<?php
/**
 * Ported from protected/views/vendor/item.php.
 */

use Yii;
use app\components\Ui;
use app\models\Vendor;
use app\widgets\ActiveForm;
use app\widgets\Button;
use app\widgets\ButtonGroup;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
		$model->label ( 2 ) => [
				'index' 
		],
		Yii::t ( 'app', 'Create' ) 
];
?>
<section class="content-header">
<div class="row">
<div class="col-md-12 margin10">
<a href="<?php echo Ui::to('vendor/create',array('id'=>$id));?>" class="btn btn-primary">Vendor Info</a>
<a href="<?php echo Ui::to('vendor/item',array('id'=>$id));?>" class="btn btn-primary">Vendor Product Info</a>

</div>
</div>

<h1><?php echo 'Create' . ' ' . Html::encode($model->label()); ?></h1>
<?php


echo ButtonGroup::widget([
		'buttons' => $this->context->menu,
		'type' => 'success',
		'htmlOptions' => [
				'class' => 'pull-right' 
		] 
] );
?>
</section>


	<section class="content">
	<div class="row">
		<div class="col-md-12 col-xs-12">
        	<div class="box">
            
            <div class="box-header"><h3 class="box-title">Vendor Products</h3></div>
            
            
            <div class="box-body">
          <div class="row">
            <div class="col-md-12">


<?php

$form = ActiveForm::begin([
		'id' => 'item-vendor-form',
		'type' => 'horizontal',
		//'enableAjaxValidation' => true,
		'htmlOptions' => [
				'enctype' => 'multipart/form-data' 
		] 
] );
?>
<?php if(Yii::$app->user->hasFlash('success')){ ?>

<div class="alert alert-success"><?php echo Yii::$app->user->getFlash('success'); ?>
</div>
<?php } ?>
<?php if(Yii::$app->user->hasFlash('error')){ ?>

<div class="alert alert-danger"><?php echo Yii::$app->user->getFlash('error'); ?>
</div>
<?php } ?>
	<p class="help-block">
			Fields with <span class="required">*</span> are required.
		</p>

	<?php echo $form->errorSummary($vendor); ?>
	<div class="col-md-6">
	<?php echo $form->dropDownListRow ( $vendor, 'item_detail_id', Vendor::getAllItems($id), ['class' => 'form-control' 	] );
	?>
				
<?php echo $form->textFieldRow($vendor,'item_code',['class'=>'form-control','maxlength'=>255]); ?>
<?php echo $form->textFieldRow($vendor,'vendor_price',['class'=>'form-control','maxlength'=>255]); ?>
<div class="form-actions">
		<?php
		
echo Button::widget([
				'buttonType' => 'submit',
				'type' => 'primary',
				'label' => 'Save' 
		] );
		?>
	</div>
		</div>







	

<?php ActiveForm::end(); ?>


</div>
</div>
</div>
            
            
            </div>
        </div>    
     </div>
<?php
if($id != null){
 $this->context->StartPanel(); ?>
<?php  $this->context->AddPanel($model->getRelationLabel('itemVendors'), $model->getRelatedDataProvider('itemVendors'),	'itemVendors','itemVendor');?>

<?php  $this->context->EndPanel();} ?>

</section>