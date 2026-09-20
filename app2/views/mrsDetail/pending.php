<?php
/**
 * Ported from protected/views/mrsDetail/pending.php.
 */

use app\components\Access;
use app\components\Gx;
use app\components\Ui;
use app\models\Item;
use app\models\ItemDetail;
use app\models\Mrs;
use app\models\Outlet;
use app\widgets\ActionColumn;
use app\widgets\ActiveForm;
use app\widgets\Button;
use app\widgets\CheckboxColumn;
use app\widgets\GridView;
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
	$.fn.yiiGridView.update('mrs-detail-grid', {
		data: $(this).serialize()
	});
	return false;
});
");
?>
<section class="content-header">
  <h1> <?php echo 'Manage' ;?> <?php echo Html::encode($model->label(2))?> </h1>
 
</section>
<section class="content">
  <div class="row">
    <div class="col-md-12 col-xs-12">
      <div class="box">
        <div class="box-header"><h3 class="box-title">MrsDetails</h3></div>
        <div class="box-body">
          <div class="row">
            <div class="col-md-12">

<?php $form = ActiveForm::begin([
	'id' => 'mrs-detail-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'action'=>Ui::to('mrsDetail/pending'),
	'htmlOptions'=>['enctype'=>'multipart/form-data'],
]);
?>


<?php //echo $form->dropDownListRow($model, 'mrs_id', Gx::listData(Mrs::class)); ?>

<div class="box-body">
<?php echo $form->datepickerRow($model, 'mrs_req_date',
					['hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>',
					'class'=>'form-control'])
; ?>
<?php echo $form->dropdownListRow($model, 'outlet_id', Gx::listData(Outlet::find()->where(['status'=>Outlet::STATUS_ACTIVE])->orderBy(['id' => SORT_DESC])->all()),['class'=>'form-control']); ?>


</div>


	<div class="form-actions box-footer">
		<?php echo Button::widget([
			'buttonType'=>'submit',
			'type'=>'primary',
			'label'=>'Save',
		]); ?>
	</div>

<?php ActiveForm::end(); ?>
<div class="clearfix"></div>
			  <br/>
 <div class="table-responsive">
                <div class="table table-bordered table-hover dataTable">
<?php $form = ActiveForm::begin([
    'enableAjaxValidation'=>true,
]); ?>
 
<?php 
    echo GridView::widget([
    'id'=>'menu-grid',
    'dataProvider'=>$model->pendingsearch(),
    'filter'=>$model,
    'columns'=>[
     /*    array(
            'id'=>'mrsId',
            'class' => CheckboxColumn::class,
            'selectableRows' => '50',   
        ), */
        [
			'attribute' =>'item_id',
			'value' => function ($data, $key, $index) { return Gx::str($data->item); },
			'filter'=>Gx::listData(Item::class),
	],
			[
					'attribute' =>'item_detail_id',
					'value' => function ($data, $key, $index) { return Gx::str($data->itemDetail); },
					'filter'=>Gx::listData(ItemDetail::class),
			],
			[
					'attribute' =>'outlet_id',
					'value' => function ($data, $key, $index) { return Gx::str($data->outlet); },
					'filter'=>Gx::listData(Outlet::class),
			],
			[
					'header'=>'vendor',
					'value' => function ($data, $key, $index) { return Gx::str($data->mrs->vendor); },
					
			],
			'req_qty',
			
    		[
    		
    				'header'=>'<a>Assign</a>',
    				'class' => ActionColumn::class,
    				'template' => '{Assign}', //include the standard buttons plus the new status button
    				'htmlOptions'=> ['style'=>'width:80px'],
    				'buttons'=>[
    						'Assign'=>[
    								//'visible' => function ($data) { return Access::check("mrsDetail/admin")=="true"; },
    								'url' => function ($data) { return Ui::to("mrsDetail/assign", ["id" => $data->id]); },
    								'label'=>'Assign',
    								'options'=>['class'=>'view'],
    									
    						],
    							
    				]
    		],

        
    ],
]); ?>
<script>
function reloadGrid(data) {
    $.fn.yiiGridView.update('menu-grid');
}
</script>

<?php //echo CHtml::ajaxSubmitButton('Assign',array('mrs/assign','act'=>'Insert'), array('success'=>'reloadGrid')); ?>

<?php ActiveForm::end(); ?>
                </div>
              </div>
            </div>
          </div>
    
        </div>
      </div>
    </div>
  </div>
</section>