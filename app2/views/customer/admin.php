<?php
/**
 * Ported from protected/views/customer/admin.php.
 */

use Yii;
use app\components\Gx;
use app\components\Ui;
use app\models\City;
use app\models\Customer;
use app\models\User;
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
		$model->label ( 2 ) => [
				'index' 
		],
		Yii::t ( 'app', 'Manage' ) 
];

?>

<style>
.btn-info.export-btn {
    background-color: #00c0ef;
    border-color: #00acd6;
    margin-left: 16px;
    margin-top: 10px;
}
</style>
<section class="content-header">
	<h1><?php echo 'Manage' . ' : ' . Html::encode($model->label(2)); ?></h1>
<?php //echo Html::a('Delete',array('user/empty'));?>
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
<?php

/*
 * echo Menu::widget(array (
 * 'type' => 'pills',
 * 'stacked' => false,
 * 'items' => array (
 * array (
 * 'label' => 'Export',
 * 'url' => array (
 * 'customer/admin',
 * 'exportCSV' => '1'
 * )
 *
 * )
 *
 * )
 * ) );
 */
?>

<ul class="nav nav-pills" id="yw2">
	<li><button type="button" class="btn btn-info export-btn" data-toggle="modal"
			data-target="#myModal">Export</button></li>
</ul>

<!-- Modal -->
<div id="myModal" class="modal fade" role="dialog">
	<div class="modal-dialog">

		<!-- Modal content-->
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title">Select Columns</h4>
			</div>
			<div class="modal-body">
     <?php
					
					$form = ActiveForm::begin([
							'id' => 'customer-export-form',
							'type' => 'horizontal',
							'action' => Ui::to( 'customer/admin?exportCSV=1' ),
							'enableAjaxValidation' => true,
							'htmlOptions' => [
									'enctype' => 'multipart/form-data' 
							] 
					] );
					?>
<?php

					$cols = [
							'attribute' => 'Name',
							'opening_balance' => 'Opening Balance',
							'credit_limit' => 'Credit Limit',
							'payment_days' => 'Payment Days',
							'email' => 'Email',
							'fax' => 'Fax',
							'contact_no' => 'Contact No',
							'address' => 'Address',
							'State' => 'State',
							'City' => 'City',
							'Country' => 'Country',
							'zip_code' => 'Zip Code' 
					];
					?>
<div class="form-group ">
					<label for="ItemStock_item_id"
						class="control-label col-md-3 required"> </label>
					<div class="col-md-9">
			<?php echo $form->checkboxListRow($model,'columns',$cols); ?>
		</div>
				</div>

				<div class="form-actions">
		<?php
		
		echo Button::widget([
				'buttonType' => 'submit',
				'type' => 'primary',
				'label' => 'Export',
				'htmlOptions' => [
						'id' => 'form-export'
				]
		] );
		?>
	</div>
<?php ActiveForm::end(); ?>
      </div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal"
					id="close_modal">Close</button>
			</div>
		</div>

	</div>
</div>
<section class="content">
	<div class="row">
		<div class="col-md-12 col-xs-12">
			<div class="box">
				<div class="box-header">
					<h3 class="box-title"><?php echo  Html::encode($model->label(2));?></h3>
				</div>
				<div class="box-body">
					<div class="row">
						<div class="col-md-12">
							<div class="table-responsive customgridwidth">
								

<?php

echo GridView::widget([
		'id' => 'customer-grid',
		'type' => 'striped bordered condensed',
		'dataProvider' => $model->search (),
		'pager'=>true,
		'filter' => $model,
		'columns' => [
				'id',
				'name',
				'email',
				'contact_no',
				'address:html',
				[
						'attribute' => 'city_id',
						'value' => function ($data) { return Gx::str($data->city); },
						'filter' => Gx::listData(City::class) 
				],
		/*
		array(
				'attribute' => 'state_id',
				'value' => function ($data) { return $data->getStatusOptions($data->state_id); },
				'filter'=>Customer::getStatusOptions(),
				),
		'country_id',
		'zip_code',
		'opening_balance',
		'credit_limit',
		'payment_days',
		'contact_no',
		array(
				'attribute' => 'status',
				'value' => function ($data) { return $data->getStatusOptions($data->status); },
				'filter'=>Customer::getStatusOptions(),
				),
		array(
				'attribute' => 'type_id',
				'value' => function ($data) { return $data->getTypeOptions($data->type_id); },
				'filter'=>Customer::getTypeOptions(),
				),
		'update_time',
		array(
			'attribute' =>'updated_by',
			'value' => function ($data) { return Gx::str($data->updatedBy); },
			'filter'=>Gx::listData(User::class),
			),
		*/
			[
						'header' => 'Actions',
						'class' => ActionColumn::class,
						'template' => '{view}{update}{delete}' 
				] 
		]
		 
] );
?>

							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>

<script>
$('#form-export').click(function(){
	$('#close_modal').trigger('click');
});
</script>