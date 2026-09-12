<?php

$this->breadcrumbs = array(
	$model->label(2) => array('index'),
	GxHtml::valueEx($model),
);


?>
<section class="content">
<div class="page-header">
<h1 class="pull-left"><?php echo GxHtml::encode(GxHtml::valueEx($model)); ?></h1>


<?php   $this->widget('bootstrap.widgets.TbButtonGroup', array(
	'buttons'=>$this->menu,
	'type'=>'success',
	'htmlOptions'=>array('class'=> 'pull-right'),
	));

	?>
<div class="clearfix"></div>


</div>
<?php $this->widget('bootstrap.widgets.TbDetailView', array(
	'data' => $model,
	'attributes' => array(
'id',
'full_name',
'email',
array(
				'name' => 'gender',
				'type' => 'raw',
				'value'=>isset($model->gender)?$model->getGenderOptions($model->gender):'',
				),
'contact_no',
			array(
					'name' => 'role_id',
					'type' => 'raw',
					'value'=>isset($model->role)?$model->role:'',
			),
			
		/* 	array(
					'visible'=>$model->role_id == User::ROLE_MERCHANT,
					'name' => 'store',
					'type' => 'raw',
					'value'=>$model->getStoreName(),
			), */
/*'date_of_birth',
'about_me:html',
'address',
'postal_code',
'country',
'city',
array(
				'name' => 'state',
				'type' => 'raw',
				'value'=>$model->getStatusOptions($model->state),
				),*/

//'image_file',
'is_active:boolean',

	
	),
)); ?>

</section>



