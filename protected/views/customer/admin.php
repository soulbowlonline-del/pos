<?php
$this->breadcrumbs = array (
		$model->label ( 2 ) => array (
				'index' 
		),
		Yii::t ( 'app', 'Manage' ) 
);

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
	<h1><?php echo Yii::t('app', 'Manage') . ' : ' . GxHtml::encode($model->label(2)); ?></h1>
<?php //echo CHtml::link('Delete',array('user/empty'));?>
<?php

$this->widget ( 'bootstrap.widgets.TbButtonGroup', array (
		'buttons' => $this->menu,
		'type' => 'success',
		'htmlOptions' => array (
				'class' => 'pull-right' 
		) 
) );
?>
</section>
<?php

/*
 * $this->widget ( 'bootstrap.widgets.TbMenu', array (
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
					
					$form = $this->beginWidget ( 'bootstrap.widgets.TbActiveForm', array (
							'id' => 'customer-export-form',
							'type' => 'horizontal',
							'action' => Yii::app ()->createUrl ( 'customer/admin?exportCSV=1' ),
							'enableAjaxValidation' => true,
							'htmlOptions' => array (
									'enctype' => 'multipart/form-data' 
							) 
					) );
					?>
<?php

					$cols = array (
							'name' => 'Name',
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
					);
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
		
		$this->widget ( 'bootstrap.widgets.TbButton', array (
				'buttonType' => 'submit',
				'type' => 'primary',
				'label' => 'Export',
				'htmlOptions' => array (
						'id' => 'form-export'
				)
		) );
		?>
	</div>
<?php $this->endWidget(); ?>
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
					<h3 class="box-title"><?php echo  GxHtml::encode($model->label(2));?></h3>
				</div>
				<div class="box-body">
					<div class="row">
						<div class="col-md-12">
							<div class="table-responsive customgridwidth">
								

<?php

$this->widget ( 'bootstrap.widgets.TbExtendedGridView', array (
		'id' => 'customer-grid',
		'type' => 'striped bordered condensed',
		'dataProvider' => $model->search (),
		'pager'=>true,
		'filter' => $model,
		'columns' => array (
				'id',
				'name',
				'email',
				'contact_no',
				'address:html',
				array (
						'name' => 'city_id',
						'value' => 'GxHtml::valueEx($data->city)',
						'filter' => GxHtml::listDataEx ( City::model ()->findAllAttributes ( null, true ) ) 
				),
		/*
		array(
				'name' => 'state_id',
				'value'=>'$data->getStatusOptions($data->state_id)',
				'filter'=>Customer::getStatusOptions(),
				),
		'country_id',
		'zip_code',
		'opening_balance',
		'credit_limit',
		'payment_days',
		'contact_no',
		array(
				'name' => 'status',
				'value'=>'$data->getStatusOptions($data->status)',
				'filter'=>Customer::getStatusOptions(),
				),
		array(
				'name' => 'type_id',
				'value'=>'$data->getTypeOptions($data->type_id)',
				'filter'=>Customer::getTypeOptions(),
				),
		'update_time',
		array(
			'name'=>'updated_by',
			'value'=>'GxHtml::valueEx($data->updatedBy)',
			'filter'=>GxHtml::listDataEx(User::model()->findAllAttributes(null, true)),
			),
		*/
			array (
						'header' => 'Actions',
						'class' => 'CButtonColumn',
						'template' => '{view}{update}{delete}' 
				) 
		)
		 
) );
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