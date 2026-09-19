<?php
/**
 * Ported from protected/views/item/barcode.php.
 */

use app\components\Gx;
use app\components\Ui;
use app\models\ItemDetail;
use app\models\Tax;
use app\models\User;
use app\widgets\ActionColumn;
use app\widgets\ActiveForm;
use app\widgets\Button;
use app\widgets\ButtonGroup;
use app\widgets\CheckboxColumn;
use app\widgets\EditableColumn;
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
	$.fn.yiiGridView.update('item-detail-grid', {
		data: $(this).serialize()
	});
	return false;
});
");
?>
<section class="content">
<div class="page-header">
<h1 class="pull-left"><?php echo 'Print Barcodes'; ?></h1>
<?php //echo Html::a('Delete',array('user/empty'));?>
<?php echo ButtonGroup::widget([
	'buttons'=>$this->context->menu,
	'type'=>'success',
	'htmlOptions'=>['class'=> 'pull-right'],
]);
?>
<div class="clearfix"></div>
</div>
<?php $form = ActiveForm::begin([
	'id' => 'item-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>['enctype'=>'multipart/form-data'],
]);
?>
	

	<?php //echo $form->errorSummary($model); ?>


<?php echo $form->dropDownListRow($model, 'company_id', $model->getParentCompanys(),['class'=>'form-control']); ?>






	<div class="form-actions">
		<?php echo Button::widget([
			'buttonType'=>'submit',
			'type'=>'primary',
			'label'=>'Search',
		]); ?>
	</div>

<?php ActiveForm::end(); ?>
<br>

<input type="button" value="Print Barcode" onclick="act();" />
<br>
 <select id="check_expiry">
  <option value="2">Without Expiry</option>  
  <option value="1">With Expiry</option>
 
</select> 

<div class="table-responsive customgridwidth">
<?php echo GridView::widget([
	'id' => 'item-detail-grid',
	'type'=>'striped bordered condensed',
		'pager'=>true,
	'dataProvider' => $model->search(),
	'filter' => $model,
	'columns' => [
	//	'id',
			
			[
					'class' => CheckboxColumn::class,
					'selectableRows'  => 100,
					'value' => function ($data, $key, $index) { return $data["id"]; },
					'checkBoxHtmlOptions' => ["name" =>"idList[]"],
			
			],
			
		
				
	[
					'attribute' =>'item_id',
					'value' => function ($data, $key, $index) { return Gx::str($data->item); },
				//	'filter'=>$model->getItemOptions(),
			],
		'bar_code',
			[
					'header'=>'MRP',
					'attribute' =>'mrp',
					'value' => function ($data, $key, $index) { return $data->getItemDetailMrp(); },
					
			],
			[
					'header'=>'Purchase Price',
					'attribute' =>'purchase_price',
					'value' => function ($data, $key, $index) { return isset($data->item)?$data->item->purchase_price:""; },
					'filterInputOptions' =>['class'=>'item_purchase_price_field'],
						
			],
			[
					'header'=>'HSN Code',
					'attribute' =>'hsn_code',
					'value' => function ($data, $key, $index) { return isset($data->item)?$data->item->hsn_code:""; },
					'filterInputOptions' =>['class'=>'item_hsn_code_field'],
						
			],
			
			[
					'header'=>'Product Code',
					'attribute' =>'product_code',
					'value' => function ($data, $key, $index) { return isset($data->item)?$data->item->item_code:""; },
					'filterInputOptions' =>['class'=>'item_item_code_field'],
			
			],
			[
					'class' => EditableColumn::class,
					'attribute' => 'expiry_date',
					//  'data_demanded_quantity' => '$data->demanded_quantity',
					//  'data-state_id' => '$data->state_id',
					//  'visible' => function ($data) { return $data->getStateValue($model->id) == 0; },
				 'value' => "date('Y-m-d')",
					'headerOptions' => ['style' => 'width: 110px'],
					'editable' => [
							//'url'     => Ui::to('demandVoucherItem/updated'),
							'placement'  => 'left',
							'inputclass' => 'span3',
							'attribute' =>'expiry_date',
							'type'     => 'text',
							//  'apply' => '$data->getStateValue('.$model->id.') == "0"'
			
							/*        'validate' => 'js: function(value) {
							 if($.trim(value) > "$data->demanded_quantity") return "Approved Quantity can not be greater than Demanded Quantity";
			}' */
					]
			
			],
			[
					'class' => EditableColumn::class,
					'attribute' => 'packing_date',
					//  'data_demanded_quantity' => '$data->demanded_quantity',
					//  'data-state_id' => '$data->state_id',
					//  'visible' => function ($data) { return $data->getStateValue($model->id) == 0; },
					'value' => "",
					'headerOptions' => ['style' => 'width: 110px'],
					'editable' => [
							//'url'     => Ui::to('demandVoucherItem/updated'),
							'placement'  => 'left',
							'inputclass' => 'span3',
							
							'attribute' =>'packing_date',
							'type'     => 'text',
							//  'apply' => '$data->getStateValue('.$model->id.') == "0"'
								
							/*        'validate' => 'js: function(value) {
							 if($.trim(value) > "$data->demanded_quantity") return "Approved Quantity can not be greater than Demanded Quantity";
			}' */
					]
						
			],
		[
				'attribute' => 'status',
				'value' => function ($data, $key, $index) { return $data->getStatusOptions($data->status); },
				'filter'=>ItemDetail::getStatusOptions(),
				],
			[
					'attribute' =>'tax_id',
					'value' => function ($data, $key, $index) { return Gx::str($data->tax); },
					'filter'=>Gx::listData(Tax::class),
			],
			
		/* 	array(
					'attribute' =>'item_id',
					'value' => function ($data, $key, $index) { return Gx::str($data->item); },
					'filter'=>$model->getItemOptions(),
			), */
		/*'reorder_qty',
		array(
				'attribute' => 'type_id',
				'value' => function ($data, $key, $index) { return $data->getTypeOptions($data->type_id); },
				'filter'=>ItemDetail::getTypeOptions(),
				),
		array(
			'attribute' =>'tax_id',
			'value' => function ($data, $key, $index) { return Gx::str($data->tax); },
			'filter'=>Gx::listData(Tax::class),
			),
		array(
			'attribute' =>'updated_by',
			'value' => function ($data, $key, $index) { return Gx::str($data->updatedBy); },
			'filter'=>Gx::listData(User::class),
			),
		*/
		/* 	array(
			
					'header'=>'<a>Status</a>',
					'class' => ActionColumn::class,
					'template' => '{view}{update}', //include the standard buttons plus the new status button
					'htmlOptions'=> array('style'=>'width:80px'),
					'buttons'=>array(
							'view'=>array(
									'visible' => function ($data) { return $data->checkPermission ("itemDetail/view")=="true"; },
									'url' => function ($data) { return Ui::to("itemDetail/view", ["id" => $data->id]); },
									'label'=>'View',
									'options'=>array('class'=>'view'),
										
							),
							'update'=>array(
									'visible' => function ($data) { return $data->checkPermission ("itemDetail/update")=="true"; },
									'url' => function ($data) { return Ui::to("itemDetail/update", ["id" => $data->id]); },
									'label'=>'Update',
									'options'=>array('class'=>'update'),
			
							)
					)
			), */
	],
]); ?>


</div>
</section>
<script>
function act()
{
	
	var idList = [];
	var date_list = [];
	var packing_date_list = [];
	var expiry_val = 1;
	$('input[type=checkbox]:checked').each(function() {
		idList.push(this.value); 
		date_list.push($('#ItemDetail_expiry_date_'+this.value).text()); 
		packing_date_list.push($('#ItemDetail_packing_date_'+this.value).text()); 
	});
	console.log(idList);
	console.log(date_list);

        if($('#item-detail-grid_c0_all').prop("checked") == true){

            var all_check = $('#item-detail-grid_c0_all').val();
            var all_check_arr = jQuery.makeArray( all_check );
            var idList = $(idList).not(all_check_arr).get();
           var all_checkk = $('#ItemDetail_expiry_date_'+all_check).text();
            var all_checkk_arr = jQuery.makeArray( all_checkk );
            var date_list = $(date_list).not(all_checkk_arr).get();
            var all_checkkk = $('#ItemDetail_packing_date_'+all_check).text();
            var all_checkkk_arr = jQuery.makeArray( all_checkkk );
            var packing_date_list = $(packing_date_list).not(all_checkkk_arr).get();
        }

        expiry_val = $('#check_expiry').val();

       // console.log(expiry_date_list);
	//var selected = item-detail-grid_c0_all
//var idList    = $("input[type=checkbox]:checked").serialize();
var url = "<?php echo Ui::to('item/printBarcode') ?>";
jQuery.ajax({
    'type': 'POST',
    'url': '<?php echo Ui::to('itemDetail/print') ?>',
  //  'dataType':"json",
    'data': {'idList': idList,'date_list':date_list,'expiry_val':expiry_val,'packing_date_list':packing_date_list},
    'success': function (data) {
    	   console.log(data);
           console.log(url);
      if(data == 'success'){
        	window.open(url,'_blank');
     
        }else{
        	alert('Select Items');
        } 
  
    },
    'cache': false
 }
 );	
console.log(idList);
}
</script>