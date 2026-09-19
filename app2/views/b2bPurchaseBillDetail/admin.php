<?php
/**
 * Ported from protected/views/b2bPurchaseBillDetail/admin.php.
 */

use app\components\Gx;
use app\components\Ui;
use app\models\Outlet;
use app\models\UserRole;
use app\models\Vendor;
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
        <div class="box-header"><h3 class="box-title">PurchaseBillDetails</h3></div>
        <div class="box-body">
          <div class="row">
            <div class="col-md-12">

<?php $form = ActiveForm::begin([
	'id' => 'mrs-detail-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'action'=>Ui::to('purchaseBillDetail/admin'),
	'htmlOptions'=>['enctype'=>'multipart/form-data'],
]);
?>

<div class="box-body">
<?php //echo $form->dropDownListRow($model, 'purchase_bill_id', $model->getPOBillOptions($user->id),array('class'=>'form-control')); ?>


<?php echo $form->datepickerRow($model, 'start_date',
					['hint'=>'Click inside! to select a date.',
					'prepend'=>'<i class="icon-calendar"></i>',
							'options'=>['format'=>'yyyy-mm-dd']])
; ?>
<?php echo $form->dropdownListRow($model, 'outlet_id', Gx::listData(Outlet::find()->where(['status'=>Outlet::STATUS_ACTIVE])->all()),['class'=>'form-control']); ?>
<?php $user = Yii::$app->user->model;
if($user->role_id != 6){?>
<?php echo $form->dropdownListRow($model, 'vendor_id',$model->getPBillVendorOptions(),['class'=>'form-control']); ?>
<?php }?>
<div class="form-group">
											<label class="control-label col-md-3" for="">Purchase Bill No</label>
											<div id="po_detail_data" class="col-md-9"></div>
										</div>
</div>


	<div class="form-actions box-footer">
		<?php echo Button::widget([
			'buttonType'=>'submit',
			'type'=>'primary',
			'label'=>'Search',
		]); ?>
	</div>

<?php ActiveForm::end(); ?>

<?php $vendor_id = null;
$role = UserRole::find()->where(['title'=>'Vendor'])->one();
			$loggedinuser = Yii::$app->user->model;
			if($loggedinuser->role_id == $role->id){
				$user = Vendor::find()->where([
						'create_user_id' => $loggedinuser->id 
				])->one();
				if($user){
					$vendor_id = $user->id;
				}
			} ?>
<div class="clearfix"></div>
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
				//'filter'=>$model->getItemOptions($vendor_id), 
        		'filter'=> false,
				
	],
			[
					'header'=>'<a>Bar Code</a>',
					'attribute' =>'item_detail_id',
					'value' => function ($data, $key, $index) { return Gx::str($data->itemDetail); },
					//	'filter'=>$model->getItemOptionbarcodes(),
					'filter'=> false,
			],
			[
					'attribute' =>'outlet_id',
					'value' => function ($data, $key, $index) { return Gx::str($data->outlet); },
					'filter'=>Gx::listData(Outlet::class),
			],
			[
					'header'=>'vendor',
					'value' => function ($data, $key, $index) { return Gx::str($data->purchaseBill->vendor); },
					
			],
			[
				'attribute' =>'req_qty',
				'value' => function ($data, $key, $index) { return $data->req_qty; },
				'filter'=>false,
			],
    		'approved_qty',
    		'mrp',
    		'sale_rate',
    		'price',
    		
    		'discount',
    		'discount_amt',
    		'discount1',
    		'discount_amt1',
    	//	'vat',
    		[
    				'header'=>'Tax',
    				'value' => function ($data, $key, $index) { return Gx::str($data->tax); },
    					
    		],
    		
    		[
    				'visible'=>$model->getGSTTrue($poid) == true,
    				'attribute' =>'cgst_per',
    				'value' => function ($data, $key, $index) { return $data->cgst_per; },
    					
    		],
    		[
    				'visible'=>$model->getGSTTrue($poid) == true,
    				'attribute' =>'sgst_per',
    				'value' => function ($data, $key, $index) { return $data->sgst_per; },
    					
    		],
    		[
    				'visible'=>$model->getGSTTrue($poid) == true,
    				'attribute' =>'sgst_per',
    				'value' => function ($data, $key, $index) { return $data->cess_per; },
    					
    		],
    		[
    				'visible'=>$model->getGSTTrue($poid) == false,
    				'attribute' =>'igst_per',
    				'value' => function ($data, $key, $index) { return $data->igst_per; },
    					
    		],
    		[
    				'visible'=>$model->getGSTTrue($poid) == true,
    				'attribute' =>'sgst_per',
    				'value' => function ($data, $key, $index) { return $data->cgst_amt; },
    					
    		],
    		[
    				'visible'=>$model->getGSTTrue($poid) == true,
    				'attribute' =>'sgst_amt',
    				'value' => function ($data, $key, $index) { return $data->sgst_amt; },
    					
    		],
    		[
    				'visible'=>$model->getGSTTrue($poid) == true,
    				'attribute' =>'cess_amt',
    				'value' => function ($data, $key, $index) { return $data->cess_amt; },
    					
    		],
    		[
    				'visible'=>$model->getGSTTrue($poid) == false,
    				'attribute' =>'igst_amt',
    				'value' => function ($data, $key, $index) { return $data->igst_amt; },
    					
    		],
    		
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
</section>

<script>

<?php /*?>
$('#PurchaseBillDetail_purchase_bill_id').change(function(){
	
	var poid = $('#PurchaseBillDetail_purchase_bill_id').val();
	var url = '<?php echo Ui::to('purchaseBillDetail/admin')?>/id/'+vendor_id+'/poid/'+poid;
	window.location.href = url;
});*/ ?>
$(document).ready(function () {

	var vendor_id = $('#PurchaseBillDetail_vendor_id').val();
	checkPONo(vendor_id);
   
   

 });
$('#PurchaseBillDetail_vendor_id').change(function(){
	var vendor_id = $('#PurchaseBillDetail_vendor_id').val();
	checkPONo(vendor_id);
 
});

function checkPONo(vendor_id){
	<?php if($poid != ''){?>
	var poid = <?php echo $poid; ?>;
	<?php }else{?>
	var poid =   $("#PurchaseBillDetail_purchase_bill_id").val();
	<?php }?>
	   jQuery.ajax({
	       'type': 'POST',
	       'url': '<?php echo Ui::to('purchaseBillDetail/ajaxPONo') ?>',
	       'data': {'vendor_id': vendor_id},
	       'success': function (data) {
	           $('#po_detail_data').html('');
	           $('#po_detail_data').html(data);
	           $('#PurchaseBillDetail_purchase_bill_id').val(poid);
	       },
	       'cache': false
	    });
}
</script>