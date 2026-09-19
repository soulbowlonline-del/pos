<?php
/**
 * Ported from protected/views/mrs/admin.php.
 */

use app\components\Gx;
use app\components\Ui;
use app\models\Mrs;
use app\models\Organization;
use app\models\Outlet;
use app\models\User;
use app\models\Vendor;
use app\widgets\ActionColumn;
use app\widgets\ActiveForm;
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
        <div class="box-header"><h3 class="box-title">Mrs</h3></div>
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
<?php echo $form->dropdownListRow($model, 'vendor_id',$model->getMrsVendorOptions(),['class'=>'form-control']); ?>
<?php ActiveForm::end(); ?>
<div class="table-responsive">

<?php echo GridView::widget([
	'id' => 'mrs-grid',
	'type'=>'striped bordered condensed',
	'dataProvider' => $model->search(),
	'filter' => $model,
	'columns' => [
			[
					'class' => CheckboxColumn::class,
					'selectableRows'  => 100,
					'value' => function ($data, $key, $index) { return $data["id"]; },
					'checkBoxHtmlOptions' => ["name" =>"idList[]"],
						
			],
		'id',
			[
			'attribute' =>'outlet_id',
			'value' => function ($data, $key, $index) { return Gx::str($data->outlet); },
			'filter'=>Gx::listData(Outlet::class),
			],
			[
					'attribute' =>'vendor_id',
					'value' => function ($data, $key, $index) { return Gx::str($data->vendor); },
					'filter'=>Gx::listData(Vendor::class),
			],
		/* array(
				'attribute' => 'status',
				'value' => function ($data, $key, $index) { return $data->getStatusOptions($data->status); },
				'filter'=>Mrs::getStatusOptions(),
				), */
		/*
		array(
				'attribute' => 'type_id',
				'value' => function ($data, $key, $index) { return $data->getTypeOptions($data->type_id); },
				'filter'=>Mrs::getTypeOptions(),
				),
		'remarks:html',
		'update_time',
		array(
			'attribute' =>'updated_by',
			'value' => function ($data, $key, $index) { return Gx::str($data->updatedBy); },
			'filter'=>Gx::listData(User::class),
			),
		array(
			'attribute' =>'outlet_id',
			'value' => function ($data, $key, $index) { return Gx::str($data->outlet); },
			'filter'=>Gx::listData(Outlet::class),
			),
		array(
			'attribute' =>'organization_id',
			'value' => function ($data, $key, $index) { return Gx::str($data->organization); },
			'filter'=>Gx::listData(Organization::class),
			),
		*/
			[
			
					'header'=>'<a>Actions</a>',
					'class' => ActionColumn::class,
					'template' => '{view}', //include the standard buttons plus the new status button
					'htmlOptions'=> ['style'=>'width:80px'],
					'buttons'=>[
							'view'=>[
										
									'url' => function ($data) { return Ui::to("mrs/view", ["id" => $data->id]); },
									'label'=>'View',
									'options'=>['class'=>'view'],
										
							],
							
								
					]
			],
	],
]); ?>
</div>
<input type="button" value="Merge Mrs" onclick="act();" />
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
	var vendor_id = $('#Mrs_vendor_id').val();
	
	$('input[type=checkbox]:checked').each(function() {
		idList.push(this.value); 
		
		
	});
	console.log(idList);
	

        if($('#mrs-grid_c0_all').prop("checked") == true){

            var all_check = $('#mrs-grid_c0_all').val();
            var all_check_arr = jQuery.makeArray( all_check );
            var idList = $(idList).not(all_check_arr).get();
        }

    

  
	//var selected = item-detail-grid_c0_all
//var idList    = $("input[type=checkbox]:checked").serialize();
var url = "<?php echo Ui::to('mrs/merge') ?>";
jQuery.ajax({
    'type': 'POST',
    'url': '<?php echo Ui::to('mrs/merge') ?>',
  //  'dataType':"json",
    'data': {'idList': idList,'vendor_id':vendor_id},
    'success': function (data) {
    	   
        	location.reload();
     
     
  
    },
    'cache': false
 }
 );	
console.log(idList);
}
</script>
 