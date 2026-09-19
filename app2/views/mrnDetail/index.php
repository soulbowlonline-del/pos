<?php
/**
 * Ported from protected/views/mrnDetail/index.php.
 */

use app\components\Gx;
use app\components\Ui;
use app\models\Outlet;
use app\models\UserRole;
use app\models\Vendor;
use app\widgets\ActiveForm;
use app\widgets\Button;
use app\widgets\ButtonGroup;
use app\widgets\CheckboxColumn;
use app\widgets\GridView;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
		$model->label ( 2 ) => [
				'index'
		],
		Yii::t ( 'app', 'Manage' )
];

$this->registerJs("
$('.search-button').click(function(){
	$('.search-form').toggle();
	return false;
});
$('.search-form form').submit(function(){
	$.fn.yiiGridView.update('mrn-detail-grid', {
		data: $(this).serialize()
	});
	return false;
});
" );
?>
<section class="content-header">
	<h1> <?php echo 'Manage' ;?> <?php echo Html::encode($model->label(2))?> </h1>

</section>
<?php $vendor_id = null;
$role = UserRole::find()->where(['title'=>'Vendor'])->orderBy(['id' => SORT_DESC])->one();
			$loggedinuser = Yii::$app->user->model;
			if($loggedinuser->role_id == $role->id){
				$user = Vendor::find()->where([
						'create_user_id' => $loggedinuser->id 
				])->orderBy(['id' => SORT_DESC])->one();
				if($user){
					$vendor_id = $user->id;
				}
			} ?>
<section class="content">
	<div class="row">
		<div class="col-md-12 col-xs-12">
			<div class="box">
				<div class="box-header">
					<h3 class="box-title">MRN Details</h3>
				</div>
				<div class="box-body">
					<div class="row">
						<div class="col-md-12 item-wrap">

<?php

$form = ActiveForm::begin([
		'id' => 'mrn-detail-form',
		'type' => 'horizontal',
		'enableAjaxValidation' => true,
		'action' => Ui::to( 'mrnDetail/index', [
				'id' => $user->id 
		] ),
		'htmlOptions' => [
				'enctype' => 'multipart/form-data' 
		] 
] );
?>

<div class="box-body">
<?php echo $form->dropDownListRow($model, 'mrn_id',$model->getMrnOptions($user->id),['class'=>'form-control']); ?>

<?php

echo $form->datepickerRow ( $model, 'mrs_req_date', [
		'hint' => 'Click inside! to select a date.',
		'prepend' => '<i class="icon-calendar"></i>',
		'class' => 'form-control' 
] );
?>

<?php echo $form->dropdownListRow($model, 'outlet_id', Gx::listData(Outlet::class),['class'=>'form-control']); ?>

</div>



							<div class="form-actions box-footer">
		<?php
		
echo Button::widget([
				'buttonType' => 'submit',
				'type' => 'primary',
				'label' => 'Save' 
		] );
		?>
	</div>

<?php ActiveForm::end(); ?>
									




<?php 
/*
       * echo ButtonGroup::widget(array(
       * 'buttons'=>$this->context->menu,
       * 'type'=>'success',
       * 'htmlOptions'=>array('class'=> 'pull-right'),
       * ));
       */
?>
<div class="clearfix"></div>

<hr/>

 <div class="col-md-12 item-list-table">
 <div class="table-responsive customgridwidth">
               
<?php $form = ActiveForm::begin([
    'enableAjaxValidation'=>true,
	'id' => 'mrs-qty',
]); ?>
 
<?php 
    echo GridView::widget([
    'id'=>'purchase-order-detail-grid',
    'dataProvider'=>$model->search(),
    'filter'=>$model,
    'columns'=>[
        // array(
            // 'id'=>'mrsId',
            // 'class' => CheckboxColumn::class,
            // 'selectableRows' => '50',   
        // ),
        [
			'attribute' =>'item_id',
			'value' => function ($data, $key, $index) { return Gx::str($data->item); },
			'filter'=>$model->getItemOptions($vendor_id), 
	],
			[
					'attribute' =>'item_detail_id',
					'value' => function ($data, $key, $index) { return Gx::str($data->itemDetail); },
					'filter'=>$model->getItemOptionbarcodes(),
			],
			[
					'attribute' =>'outlet_id',
					'value' => function ($data, $key, $index) { return Gx::str($data->outlet); },
					'filter'=>Gx::listData(Outlet::class),
			],
			[
					'header'=>'vendor',
					'value' => function ($data, $key, $index) { return Gx::str($data->mrn->vendor); },
					
			],
			[
				'attribute' =>'req_qty',
				'value' => function ($data, $key, $index) { return $data->req_qty; },
				'filter'=>false,
			],
    		'approved_qty',
    		'mrp',
    		'price',
    		'sale_rate',
    		'discount',
    		'discount_amt',
    	//	'vat',
    		[
    				'header'=>'Tax',
    				'value' => function ($data, $key, $index) { return Gx::str($data->tax); },
    					
    		],
    		'cgst_per',
    		'sgst_per',
    		'cess_per',
    		'cgst_amt',
    		'sgst_amt',
    		'cess_amt',
    		'other_charge',
    		'amount'
    		
        
    ],
]); ?>

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
<?php

$user = Yii::$app->user->model;
$role = UserRole::find()->where([
		'title' => 'Admin' 
])->orderBy(['id' => SORT_DESC])->one();
?>
<script>
$('#MrnDetail_mrn_id').change(function(){
	var vendor_id = <?php echo $user->id?>;
	var mrsid = $('#MrnDetail_mrn_id').val();
	var url = '<?php echo Ui::to('mrnDetail/index')?>/id/'+vendor_id+'/mrnid/'+mrsid;
	window.location.href = url;
});


$('.mrp_input').change(function(){
	var mrp = $(this).val();
	var str = $(this).attr('id');
	 var arr = str.split('input');
	 
	 if(mrp != ''){
			$('#sale_rate'+arr['1']).val(mrp);
		}
		<?php if($user->role_id != $role->id){?>
		$('#sale_rate'+arr['1']).attr('readonly', true);
		<?php }?>
	
});
$('.price_input').change(function(){
	var mrp = $(this).val();
	var str = $(this).attr('id');
	 var arr = str.split('input');
	 var id = arr['1'];
	 gridcalculation(id);
	
	
});
$('.discount_input').change(function(){
	var mrp = $(this).val();
	var str = $(this).attr('id');
	 var arr = str.split('input');
	 var id = arr['1'];
	 gridcalculation(id);
	
	
});

$('.other_charge_input').change(function(){
	var mrp = $(this).val();
	var str = $(this).attr('id');
	 var arr = str.split('input');
	 var id = arr['1'];
	 gridcalculation(id);
	
	
});
function gridcalculation(id){
	var CGST_amt = '0.00';
	var SGST_amt = '0.00';
	var CESS_amt = '0.00';
	var CGST_per = '0.00';
	var SGST_per = '0.00';
	var CESS_per = '0.00';
	var price = $('#price_input'+id).val();
	var discount = $('#discount_input'+id).val();
	 var CGST_per = $('#CGST_per_input'+id).val();
    var  SGST_per = $('#SGST_per_input'+id).val();
     var CESS_per = $('#CESS_per_input'+id).val(); 
	if(price != '' &&  discount != ''){
		console.log('price'+price);
		console.log('discount'+discount);
		var discount_amt = price * discount/100;
		 CGST_amt = discount_amt * CGST_per/100;
		 SGST_amt = discount_amt * SGST_per/100;
		 CESS_amt = discount_amt * CESS_per/100; 
			$('#discount_amt_input'+id).val(discount_amt);
			$('#CGST_amt_input'+id).val(CGST_amt.toFixed(2));
			$('#SGST_amt_input'+id).val(SGST_amt.toFixed(2));
			$('#CESS_amt_input'+id).val(CESS_amt.toFixed(2));
		var other_charge = $('#other_charge_input'+id).val();
		console.log('other_charge'+other_charge);
		if (other_charge == ''){
			other_charge = '0.00';
		}
		var qty = $('#approve_input_qty'+id).val();
		var discounted_amount = parseFloat(price) - parseFloat(discount_amt);
		var total_amount =  qty * (parseFloat(discounted_amount) + parseFloat(CGST_amt) + parseFloat(SGST_amt) + parseFloat(CESS_amt)+ parseFloat(other_charge));
		console.log('total_amount'+total_amount);
		$('#total_amount_input'+id).val(total_amount.toFixed(2));
		}
}
$(document).ready(function () {
	checkBarcodes();
	
    $('#MrnDetail_item_id').change(function () {  
    	checkBarcodes();
    	
    });
   

 });

function checkBarcodes(){
	 var item_id = $('#MrnDetail_item_id').val();
	    jQuery.ajax({
	       'type': 'POST',
	       'url': '<?php echo Ui::to('mrnDetail/ajaxItems') ?>',
	       'data': {'item_id': item_id},
	       'success': function (data) {
	           $('#item_detail_data').html('');
	           $('#item_detail_data').html(data);
	         
	       },
	       'cache': false
	    }
	    );	
}
function checkTaxes(){
	 var item_id = $('#MrnDetail_item_id').val();
	
	 var item_detail_id = $('#MrnDetail_item_detaill_id').val();
	    jQuery.ajax({
	       'type': 'POST',
	       'url': '<?php echo Ui::to('mrnDetail/ajaxTax') ?>',
	       'data': {'item_id': item_id,'item_detail_id':item_detail_id},
	       dataType: 'json',
	       'success': function (data) {
	           $('#item_tax_data').html('');
	           $('#item_tax_data').html(data.options);
	           $('#CGST_per').val(data.cgst);
	           $('#SGST_per').val(data.sgst);
	           $('#CESS_per').val(data.cess);
	           calculation();
	       },
	       'cache': false
	    }
	    );	
}
</script>
<script>
$('#MrnDetail_mrn_id').change(function(){
	var vendor_id = <?php echo $user->id?>;
	var mrnid = $('#MrsDetail_mrn_id').val();
	var url = '<?php echo Ui::to('mrnDetail/admin')?>/id/'+vendor_id+'/mrnid/'+mrnid;
	window.location.href = url;
});

$('#yw2').click(function(){
	var mrnid = $('#MrnDetail_mrn_id').val();
var req_qty = $('#MrnDetail_req_qty').val();
	if(req_qty != ''){

 jQuery.ajax({
     'type': 'POST',
     'url': '<?php echo Ui::to('mrnDetail/ajaxCreate') ?>/id/'+mrnid,
     data: $("#mrn-detail-add-form").serialize(),
     'success': function (data) {
         
         alert(data);
         location.reload();
    /*      $('#item_detail_data').html('');
         $('#item_detail_data').html(data); */
       
     },
     'cache': false
  }
  );
//console.log(form_values);
	}
	
});
$('#MrnDetail_mrp').change(function(){
	var mrp = $('#MrnDetail_mrp').val();
	if(mrp != ''){
		$('#MrnDetail_sale_rate').val(mrp);
	}
	<?php if($user->role_id != $role->id){?>
	$('#MrnDetail_sale_rate').attr('readonly', true);
	<?php }?>
	
});
$('#MrnDetail_discount').change(function(){
	calculation();
	
});
$('#MrnDetail_price').change(function(){
	calculation();
	
});
$('#MrnDetail_other_charge').change(function(){
	calculation();
	
});
function calculation(){
	var CGST_amt = '0.00';
	var SGST_amt = '0.00';
	var CESS_amt = '0.00';
	var price = $('#MrnDetail_price').val();
	var discount = $('#MrnDetail_discount').val();
	 var CGST_per = $('#CGST_per').val();
    var  SGST_per = $('#SGST_per').val();
     var CESS_per = $('#CESS_per').val();
	if(price != '' &&  discount != ''){
		var discount_amt = price * discount/100;
		 CGST_amt = discount_amt * CGST_per/100;
		 SGST_amt = discount_amt * SGST_per/100;
		 CESS_amt = discount_amt * CESS_per/100;
			$('#MrnDetail_discount_amt').val(discount_amt);
			$('#CGST_amt').val(CGST_amt.toFixed(2));
			$('#SGST_amt').val(SGST_amt.toFixed(2));
			$('#CESS_amt').val(CESS_amt.toFixed(2));
		var other_charge = $('#MrnDetail_other_charge').val();
		console.log(other_charge);
		if (other_charge == ''){
			other_charge = '0.00';
		}
		var qty = $('#MrnDetail_approved_qty').val();
		var discounted_amount = parseFloat(price) - parseFloat(discount_amt);
		var total_amount =  qty * (parseFloat(discounted_amount) + parseFloat(CGST_amt) + parseFloat(SGST_amt) + parseFloat(CESS_amt) + parseFloat(other_charge));
		$('#MrnDetail_amount').val(total_amount.toFixed(2));
		}
}
</script>