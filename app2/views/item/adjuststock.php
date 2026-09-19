<?php
/**
 * Ported from protected/views/item/adjuststock.php.
 */

use app\components\Gx;
use app\components\Ui;
use app\models\Item;
use app\models\Vendor;
use app\widgets\ActiveForm;
use app\widgets\Button;
use app\widgets\ButtonGroup;
use app\widgets\EditableColumn;
use app\widgets\GridView;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	$model->label(2) => ['index'],
	'Manage',
];
?>
<section class="content-header">


  <h1> <?php echo 'Manage' . ' : ' . Html::encode($model->label(2)); ?> </h1>
  <?php /* echo ButtonGroup::widget(array(
	'buttons'=>$this->context->menu,
	'type'=>'success',
	'htmlOptions'=>array('class'=> 'pull-right bttn-box'),
)); */
?>
</section>
<?php if($model->name != ''){
	 Yii::$app->session['item_name'] = $model->name;
 }else{
 	Yii::$app->session['item_name'] = '';
 }?>
<section class="content">
  <div class="row">
    <div class="col-md-12 col-xs-12">
	
	 <div class="box">
        <div class="box-header"><h3 class="box-title">Select Company</h3></div>
          <div class="box-body">
          
	<div class="search-form">
<?php 	$form = ActiveForm::begin([
	//'action' => Ui::to($this->route),
	//'method' => 'get',
	'id' => 'item-form',
	'type'=>'horizontal',		
]); 
?>
<div class="col-md-9 col-xs-12">
		<?php echo $form->textFieldRow($model,'name',['class'=>'form-control','maxlength'=>255]); ?>
</div>	<div class="col-md-3 col-xs-12">
		<div class="form-actions">
		<?php echo Button::widget([
			'buttonType'=>'submit',
			'type'=>'primary',
			'label'=>'Search',
		]); ?>
	</div>
	</div>
<?php ActiveForm::end(); ?>
</div>
</div></div>
	
	
     <div class="box">
        <div class="box-header"><h3 class="box-title">Items</h3></div>
          <div class="box-body">
                  <div class="row">
            <div class="col-md-12">
<div class="table-responsive customgridwidth">
  <div class="">
<?php $form = ActiveForm::begin([
    'enableAjaxValidation'=>true,
	'id' => 'mrs-qty',
]); ?>
 
<?php 
    echo GridView::widget([
    'id'=>'menu-grid',
    		'type'=>'striped bordered condensed',
    		'dataProvider'=>$model->adjust(),
    		'rowCssClassExpression' => '$data->getCssClass()',
    		'pager'=>true,
    'filter'=>$model,
    'columns'=>[
    		'title',
    	
    		
    		[
    				'header' => 'Bar Code',
    				'value' => function($model){
    				$list = $model->getBarcodeList();
    				echo Html::dropDownList('bar',$list,$list,['id'=>'row'.$model->id,'Onchange'=>'getRemainingQty(this)','data-id'=>$model->id]);
    		
    				},
    				'htmlOptions'=>['class'=>'batch'],
    				],
    				'mrp',
    				[
    						'header'=>'Remaining Quantity',
							'attribute' =>'remaining_quan',
    						'format' => 'raw',
    						'value' => function ($data, $key, $index) { return Html::activeTextInput($data,'qty',["id"=>"remain_input$data->id","class"=>"remain_input","value"=>$data->getAscBarCodeTotalRemainingQuantity(),"readOnly"=>"readOnly"]); },
    					//	'value' => function ($data, $key, $index) { return $data->getBarCodeTotalRemainingQuantity(); },
    	                 'htmlOptions'=>['class'=>'remain'],
    				],
    				[
    						'header'=>'Remarks',
    						'format' => 'raw',
    						'value' => function ($data, $key, $index) { return Html::activeTextInput($data,'remarks',["id"=>"remarks_input$data->id","class"=>"remarks_input","value"=>""]); },
    						//	'value' => function ($data, $key, $index) { return $data->getBarCodeTotalRemainingQuantity(); },
    						'htmlOptions'=>['class'=>'remarks'],
    				],
    				/* array(
    						'header' => 'Outlet',
    						'value' => function($model){
    						$list = Item:: getAllOutlets();
    						echo Html::dropDownList('outlet',$list,$list,array('id'=>'outlet_input'.$model->id));
    				
    						},
    						'htmlOptions'=>array('class'=>'outlet_input'),
    						), 
    				array(
    						'header' => 'Type',
    						'value' => function($model){
    						$list = array('Add','Substract');
    						echo Html::dropDownList('type',$model->getItemAdjustedType(),$list,array('id'=>'type_input'.$model->id,"value"=>$model->getItemAdjustedType()));
    				
    						},
    						'htmlOptions'=>array('class'=>'type_input'),
    						),*/
    				[
    							
    						'header'=>'Shelf Stock',
    						'value' => function ($data, $key, $index) { return Html::activeTextInput($data,'qty',["id"=>"qty_input$data->id","class"=>"qty_input","value"=>$data->getItemAdjustedStock()]); },
    						'format' => 'raw',
    						'htmlOptions'=>['class'=>'qty'],
    							
    				],
    				[
    						//	'class' => EditableColumn::class,
    						'attribute' => 'vendor_id',
    						//  'data_demanded_quantity' => '$data->demanded_quantity',
    						//  'data-state_id' => '$data->state_id',
    						//  'visible'=>'$data->getStateValue('.$model->id.') == 0',
    						'value' => function ($data, $key, $index) { return $data->getLatestVendorName(); },
    						'headerOptions' => ['style' => 'width: 110px'],
    				
    						'filter'=>Gx::listData(Vendor::class),
    				
    				],
    				[
    						'attribute' =>'company_id',
    						'value' => function ($data, $key, $index) { return isset($data->company)?$data->company:""; },
    						'filter'=>$model->getParentCompanys(),
    				],
        
    ],
]); ?>
<script>
// function reloadGrid(data) {
    // $.fn.yiiGridView.update('menu-grid');
// }
$(document).ready(function(){
	
var company_id = $('#Item_company_id').val();
console.log('company_id'+ company_id);
if(company_id == ''){
	<?php Yii::$app->session['company_id'] = '';?>
}
	//$('#mrs-qty input').addClass('approved_qty');
	//var mrsid = ("#MrsDetail_mrs_id").val();
	
	
	$("#approve").click(function (event) {
	event.preventDefault();
	var formData = {};
	var remarksData = {};
	var qtyData = {};
	var outlet = {};
	var remainData = {};
	

	$("td.batch select").each(function(key,value){
		 var val = $(this).val(); 
		var str = $(this).attr('id');
		 var approved_qty = str.split('row');
	
	   formData[approved_qty['1']] = val;
}); 
	$("td.qty input:text").each(function(key,value){
		 var val = $(this).val(); 
		 var str = $(this).attr('id');
		 var qty = str.split('input');
		 qtyData[qty['1']] = val;
}); 
$("td.remain input:text").each(function(key,value){
		 var val = $(this).val(); 
		 var str = $(this).attr('id');
		 var remainqty = str.split('input');
		 remainData[remainqty['1']] = val;
}); 

	$("td.remarks input:text").each(function(key,value){
		 var val = $(this).val(); 
		 var str = $(this).attr('id');
		 var remarks = str.split('input');
		 remarksData[remarks['1']] = val;
}); 
	/*$("td.type_input select").each(function(key,value){
		 var val = $(this).val(); 
		 var str = $(this).attr('id');
		 var selecttype = str.split('type_input');
		 type[selecttype['1']] = val;
}); */

	
	$.ajax({
	       url: '<?php echo Ui::to('item/adjust'); ?>',
	       type: 'post',

	       data: {
	    	   formData: formData,
	    	   qtyData: qtyData,
	    	   remainData: remainData,
	    	   remarksData: remarksData
	              },
	       success: function (data) {
	    	$.fn.yiiGridView.update('menu-grid');
	    	alert('Data is saved successfully');
	       }
		 
	  }); 
    });

	
})

function getRemainingQty(t){
	var id = t.id;
	var arr = id.split('row');
	var val = $('#'+ t.id).val();
	console.log('id'+id);
	console.log('arr'+arr['1']);
	console.log('val'+val);
	jQuery.ajax({
	       'type': 'POST',
	       'url': '<?php echo Ui::to('item/getBarCodeStock') ?>',
	       'data': {'item_detail_id': val},
	       'success': function (data) {
	    	   $('#remain_input'+arr['1']).val(data);
	    	  console.log(data);
	          <?php
											/*
											 * if($model->section_id != ''){?>
											 * var selected_class_id = <?php echo $model->class_id;?>;
											 * var section_id = <?php echo $model->section_id;?>;
											 * if(selected_class_id == class_id)
											 * $('input[type=checkbox][value='+section_id+']').attr('checked',true);
											 * <?php }
											 */
											?>
	       },
	       'cache': false
	    }
	    );
	
}
</script>
<button class="btn btn-primary" id="approve" name="approve">Save</button>

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