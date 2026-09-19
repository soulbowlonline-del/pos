<?php
/**
 * Ported from protected/views/purchaseBill/list.php.
 */

use app\components\Gx;
use app\components\Ui;
use app\models\Outlet;
use app\models\User;
use app\models\Vendor;
use app\widgets\ActionColumn;
use app\widgets\ActiveForm;
use app\widgets\CJuiDatePicker;
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
	$.fn.yiiGridView.update('mrs-grid', {
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
        <div class="box-header"><h3 class="box-title">Grn</h3></div>
        <div class="box-body">
          <div class="row">
          <!--  form code start here -->

            <div class="col-md-12">
<?php $form = ActiveForm::begin([
	'id' => 'item-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>['enctype'=>'multipart/form-data'],
]);
?>
<?php echo $form->dropdownListRow($model, 'vendor_id',Gx::listData(Vendor::find()->where(['status'=>Vendor::STATUS_ACTIVE])->orderBy(['id' => SORT_DESC])->all()),['class'=>'form-control']); ?>
<?php ActiveForm::end(); ?>
<div class="table-responsive">
<?php echo GridView::widget([
	'id' => 'purchase-bill-grid',
	'type'=>'striped bordered condensed',
	'dataProvider' => $model->listsearch($val = true),
	'filter' => $model,
		'pager'=>true,
		'afterAjaxUpdate'=>"function(){
                                                       $.datepicker.setDefaults($.datepicker.regional['en']);
                                                        $('#Projects_projStart').datepicker({'dateFormat': 'yy-mm-dd'});
		
                                                }",
	'columns' => [
			[
					'class' => CheckboxColumn::class,
					'selectableRows'  => 100,
					'value' => function ($data, $key, $index) { return $data["id"]; },
					'checkBoxHtmlOptions' => ["name" =>"idList[]"],
			
			],
		'id',
		//	'bill_no',
			//'start_date',
			['attribute' =>'start_date',
					'value' => function ($data, $key, $index) { return $data->start_date; },
					'filter' => CJuiDatePicker::widget([
									'model' => $model,
									'attribute' => 'start_date',
									'language' => 'en',
									'htmlOptions' => [
											'id' => 'Projects_projStart',
											'dateFormat' => 'yy-mm-dd',
									],
									'options' => [  // (#3)
											'showOn' => 'focus',
											'dateFormat' => 'yy-mm-dd',
											'showOtherMonths' => true,
											'selectOtherMonths' => false,
											'changeMonth' => false,
											'changeYear' => false,
									]
							],
							true),
								
],
			'end_date',
			/* array(
					'attribute' => 'total_amount',
					'value' => function ($data, $key, $index) { return isset($data->total_amount)?$data->total_amount:""; },
			
			), */
			[
					'attribute' =>'vendor_id',
					'value' => function ($data, $key, $index) { return Gx::str($data->vendor); },
					'filter'=>Gx::listData(Vendor::class),
			],
			[
					'attribute' =>'outlet_id',
					'value' => function ($data, $key, $index) { return Gx::str($data->outlet); },
					'filter'=>Gx::listData(Outlet::class),
			],
			[
						
					'header'=>'<a>Status</a>',
					'class' => ActionColumn::class,
					'template' => '{view}',  //include the standard buttons plus the new status button
					'htmlOptions'=> ['style'=>'width:80px'],
					'buttons'=>[
							'view'=>[
								//	'visible' => function ($data) { return $data->state_id==User::STATUS_INACTIVE; },
									'url' => function ($data) { return Ui::to("purchaseBill/view", ["id" => $data->id]); },
									'label'=>'view',
									'options'=>['class'=>'view'],
			
							],
							
					]
			],
		
		
	],
]); ?>

</div>
<input type="button" value="Merge Grn" onclick="act();" />
<br>
  </div>
            </div>
          </div>
    
        </div>
      </div>
    </div>

</section>
<script>
function act()
{
	
	var idList = [];
	var vendor_id = $('#PurchaseBill_vendor_id').val();
	
	$('input[type=checkbox]:checked').each(function() {
		idList.push(this.value); 
		
		
	});
	console.log('idList'+idList);
	

        if($('#purchase-bill-grid_c0_all').prop("checked") == true){

            var all_check = $('#purchase-bill-grid_c0_all').val();
            var all_check_arr = jQuery.makeArray( all_check );
            var idList = $(idList).not(all_check_arr).get();
        }

    

  
	//var selected = item-detail-grid_c0_all
//var idList    = $("input[type=checkbox]:checked").serialize();
var url = "<?php echo Ui::to('purchaseBill/merge') ?>";
jQuery.ajax({
    'type': 'POST',
    'url': '<?php echo Ui::to('purchaseBill/merge') ?>',
  //  'dataType':"json",
    'data': {'idList': idList,'vendor_id':vendor_id},
    'success': function (data) {
    	   console.log('data'+data);
        location.reload();
     
     
  
    },
    'cache': false
 }
 );	
console.log(idList);
}
</script>
 