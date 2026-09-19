<?php
/**
 * Ported from protected/views/onlineOrder/admin.php.
 */

use app\components\Ui;
use app\models\OnlineOrder;
use app\widgets\ActionColumn;
use app\widgets\ActiveForm;
use app\widgets\Button;
use app\widgets\ButtonGroup;
use app\widgets\GridView;
use app\widgets\Menu;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	$model->label(2) => ['index'],
	'Manage',
];


$this->registerJs("
$('.search-button').click(function(){
	$('.search-form').toggle();
	return false;
});
$('.search-form form').submit(function(){
	$.fn.yiiGridView.update('online-order-grid', {
		data: $(this).serialize()
	});
	return false;
});
");
?>
<style>
.btn-info.export-btn {
	background-color: #00c0ef;
	border-color: #00acd6;
	margin-left: 16px;
	margin-top: 10px;
}
</style>
<?php 	if(empty($model->start_date) && empty($model->end_date ))
		{
			
				Yii::$app->session['onlineorder_start_date'] ='';
				Yii::$app->session['onlineorder_end_date'] ='';
				
			
		}
		?>
<section class="content-header">
<h1><?php echo 'Manage' . ' : ' . Html::encode($model->label(2)); ?></h1>
<?php //echo Html::a('Delete',array('user/empty'));?>
<?php echo ButtonGroup::widget([
	'buttons'=>$this->context->menu,
	'type'=>'success',
	'htmlOptions'=>['class'=> 'pull-right'],
]);
?>
</section>

<section class="content">
  <div class="row">
    <div class="col-md-12 col-xs-12">
      <div class="box">
         <div class="box-header"><h3 class="box-title"><?php echo  Html::encode($model->label(2));?></h3></div>
        <div class="box-body">
        <?php    echo Menu::widget([
       'type' => 'pills',
        	//	'htmlOptions'=>array('class'=>'abc'),
       'stacked' => false,
       'items' => [
        		['label' => 'Export',
        			
        				'url' => ['onlineOrder/admin' ,'exportCSV'=>'1',
        						
        		
        		],
       		
       		],
       ],
   ]);  ?>
             <?php $form = ActiveForm::begin([
	'id' => 'stock-adjust-log-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>['enctype'=>'multipart/form-data'],
]);
?>

<div class="col-md-6">

<?php echo $form->datepickerRow($model, 'start_date',
					['hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>',
						'options'=>['format'=>'yyyy-mm-dd']])

; ?>
</div>
<div class="col-md-6">
<?php echo $form->datepickerRow($model, 'end_date',
					['hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>',
								'options'=>['format'=>'yyyy-mm-dd']])

; ?>
</div>

	<div class="form-actions pull-left">
		<?php echo Button::widget([
			'buttonType'=>'submit',
			'type'=>'primary',
			'label'=>'Search',
		]); ?>
	</div>

<?php ActiveForm::end(); ?>
          <div class="row">
            <div class="col-md-12">
<div class="table-responsive customgridwidth">


<?php echo GridView::widget([
	'id' => 'online-order-grid',
		'pager' => true,
	'type'=>'striped bordered condensed',
	'dataProvider' => $model->search(),
	'filter' => $model,
	'columns' => [
		//'id',
		
		'order_id',
			[
					'attribute' => 'first_name',
					'value' => function ($data, $key, $index) { return $data->getCustomerName(); },
					
			],
			'mobile',
			'telephone',
			'order_date',
			'grand_total',
			
			'payment_method',
			'delivery_method',
		
			[
				'attribute' => 'status',
				'value' => function ($data, $key, $index) { return $data->getStatusOptions($data->status); },
				'filter'=>OnlineOrder::getStatusOptions(),
				],
			'street',
			'delivery_boy',
			'delivery_telephone',
			'order_from',
			'comment',
		/*
		'street',
		'city',
		'telephone',
		'zip_code',
		'country',
		'delivery_slot',
		'ship_name',
		array(
				'attribute' => 'type_id',
				'value' => function ($data, $key, $index) { return $data->getTypeOptions($data->type_id); },
				'filter'=>OnlineOrder::getTypeOptions(),
				),
		array(
				'attribute' => 'status',
				'value' => function ($data, $key, $index) { return $data->getStatusOptions($data->status); },
				'filter'=>OnlineOrder::getStatusOptions(),
				),
		'update_time',
		'updated_by',
		*/
			[
			
					'header'=>'<a>PDF</a>',
					'class' => ActionColumn::class,
					'template' => '{pdf}',
					//'template' => '{view}{update}', //include the standard buttons plus the new status button
					'htmlOptions'=> ['style'=>'width:80px'],
					'buttons'=>[
							'pdf'=>[
										
									'url' => function ($data) { return Ui::to("onlineOrder/pdf", ["id" => $data->id]); },
									'label'=>'Pdf',
									'options'=>['class'=>'pdf'],
										
							],
							/* 'update'=>array(
							 'visible'=>'$data->status =='."'".OnlineOrder::STATUS_PENDING."'",
									'url' => function ($data) { return Ui::to("onlineOrder/hold", ["id" => $data->id]); },
									'label'=>'Hold',
									'options'=>array('class'=>'update'),
										
							)  */
								
					]
			],
			[
						
					'header'=>'<a>Actions</a>',
					'class' => ActionColumn::class,
					'template' => '{view}',
					//'template' => '{view}{update}', //include the standard buttons plus the new status button
					'htmlOptions'=> ['style'=>'width:80px'],
					'buttons'=>[
							'view'=>[
									
									'url' => function ($data) { return Ui::to("onlineOrder/view", ["id" => $data->id]); },
									'label'=>'View',
									'options'=>['class'=>'view'],
			
							],
							 /* 'update'=>array(
									'visible'=>'$data->status =='."'".OnlineOrder::STATUS_PENDING."'",
									'url' => function ($data) { return Ui::to("onlineOrder/hold", ["id" => $data->id]); },
									'label'=>'Hold',
									'options'=>array('class'=>'update'),
							
							)  */
							
					]
			],
	],
]); ?>
</div>
</div>
</div>
</div>
</div>
</div>
</div>
</section>