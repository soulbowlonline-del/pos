<?php
$this->breadcrumbs = array (
		$model->label ( 2 ) => array (
				'index' 
		),
		Yii::t ( 'app', 'Manage' ) 
);

Yii::app ()->clientScript->registerScript ( 'search', "
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
" );
?>


<?php

$gst = true;
$mrs = null;
if ($poid) {
	$mrs = PurchaseBill::model ()->findByAttributes ( array (
			'id' => $poid 
	) );
	if ($mrs) {
		$outlet = Outlet::model ()->findByPk ( $mrs->outlet_id );
		if ($outlet) {
			$vendor = Vendor::model ()->findByPk ( $mrs->vendor_id );
			if ($vendor->state_id != $outlet->state_id) {
				$gst = false;
			}
		}
	}
}
?>
<section class="content-header">
	<h1> <?php echo Yii::t('app', 'Manage') ;?> <?php echo GxHtml::encode($model->label(2))?> </h1>
	<a href="<?php echo Yii::app()->createUrl('purchaseBill/list');?>"
		class="btn btn-info export-btn" target="_blank">Merge</a>

</section>
<section class="content">

	<div class="row">
		<div class="col-md-12 col-xs-12">
			<div class="box">
				<div class="box-header">
					<h3 class="box-title">PurchaseBillDetails</h3>
				</div>
				<div class="box-body">
					<div class="row">
						<div class="col-md-12">

<?php

$form = $this->beginWidget ( 'bootstrap.widgets.TbActiveForm', array (
		'id' => 'mrs-detail-form',
		'type' => 'horizontal',
		'enableAjaxValidation' => true,
		'action' => Yii::app ()->createUrl ( 'purchaseBillDetail/index' ),
		'htmlOptions' => array (
				'enctype' => 'multipart/form-data' 
		) 
) );
?>

<div class="box-body">

<?php //echo $form->dropDownListRow($model, 'purchase_bill_id', $model->getPOBillOptions($user->id),array('class'=>'form-control')); ?>


<?php

echo $form->datepickerRow ( $model, 'start_date', array (
		'hint' => 'Click inside! to select a date.',
		'prepend' => '<i class="icon-calendar"></i>',
		'options' => array (
				'format' => 'yyyy-mm-dd' 
		) 
) );
?>
<?php echo $form->dropdownListRow($model, 'outlet_id', GxHtml::listDataEx(Outlet::model()->findAllAttributes(null, true)),array('class'=>'form-control')); ?>

<?php

$user = Yii::app ()->user->model;
if ($user->role_id != 6) {
	?>
<?php /*?>	<div class="form-group ">
									<label class="control-label col-md-3"
										for="PurchaseBillDetail_vendor_id">Vendor</label>
									<div class="col-md-9">
										<select class="form-control"
											name="PurchaseBillDetail[vendor_id]"
											id="PurchaseBillDetail_vendor_id" onchange="this.className=this.options[this.selectedIndex].className" 
   >
											<?php $vendors = $model->getPBillVendorOptions();
											?>
											
											<?php if($vendors){
											foreach($vendors as $key=>$vendor){
												$criteria = new CDbCriteria();
												$criteria->addCondition('vendor_id ='.$key);
												$criteria->addCondition('status !='.PurchaseBill::STATUS_APPROVED);
												$bills = PurchaseBill::model()->findAll($criteria);
												$class = "form-control";
												if($bills){
													foreach($bills as $bill)
													{
														$current_date = date('Y-m-d');
														$create_time = date('Y-m-d',strtotime($bill->create_time));
														$last_date = date('Y-m-d',(strtotime ( '+5 day' , strtotime ( $create_time) ) ));
														
														if($current_date >= $last_date){
															$class="redText form-control";
														}
													}
												}
												$selected = '';
											if($vendor_id == $key){
												$selected = 'selected';
}?>
											<option value="<?php echo $key;?>" class="<?php echo $class;?>" <?php echo $selected;?>><?php echo $vendor;?></option>
										<?php }}?>
										</select>
										
									</div>
								</div>*/?>
<?php echo $form->dropdownListRow($model, 'vendor_id',$model->getPBillVendorOptions(),array('class'=>'form-control')); ?>
<?php }?>
<div class="form-group">
									<label class="control-label col-md-3" for="">Purchase Bill No</label>
									<div id="po_detail_data" class="col-md-9"></div>
								</div>
							</div>


							<div class="form-actions box-footer">
		<?php
		
		$this->widget ( 'bootstrap.widgets.TbButton', array (
				'buttonType' => 'submit',
				'type' => 'primary',
				'label' => 'Search' 
		) );
		?>
	</div>

<?php $this->endWidget(); ?>
<div class="col-md-12">
<?php

$form = $this->beginWidget ( 'bootstrap.widgets.TbActiveForm', array (
		'id' => 'po-detail-add-form',
		'type' => 'horizontal',
		// 'action'=> Yii::app()->createUrl('mrsDetail/create',array('id'=>$mrsid)),
		'enableAjaxValidation' => true,
		'htmlOptions' => array (
				'enctype' => 'multipart/form-data' 
		) 
) );
?>
	<div class="add-item">
									<div class="col-md-10 col-sm-12 col-xs-12 margin10">

										<div class="row">


											<div class="col-md-2 col-xs-12 padding2px">
												<!-- <label class="control-label" for="">Bar Code</label>
											<div id="item_detail_data"></div> -->
												<input type="hidden"
													name="PurchaseBillDetail[item_detail_id]"
													id="PurchaseBillDetail_item_detaill_id"> <label
													class="control-label" for="">Bar Code</label>
                  <?php echo $form->textField($model,'bar_code',array('class'=>'form-control','id'=>'PurchaseBillDetail_bar_code')); ?>
										</div>
											<div class="col-md-2 col-xs-12 padding2px">

												<label class="control-label" for="">Item</label> <input
													type="hidden" name="PurchaseBillDetail[item_id]"
													id="item_hsn_code">


   <?php
			
			$this->widget ( 'ext.typeahead.TbTypeAhead', array (
					'model' => $model,
					'attribute' => 'item_val_id',
					'enableHogan' => true,
					'htmlOptions' => array (
							'class' => 'form-control' 
					),
					'options' => array (
							array (
									'limit' => 100,
									'name' => 'item_val_id',
									'valueKey' => 'name',
									'remote' => array (
											'url' => Yii::app ()->createUrl ( '/item/getAllItems' ) . '?vendor_id=' . $vendor_id . '&&term=%QUERY' 
									),
									'template' => '<p>{{name}}     <strong> [ {{mrp}} ] </strong></p>',
									// 'template' => '<p>{{name}}<strong> [ {{username}} ] </strong> - {{user_id}}</p>',
									'engine' => new CJavaScriptExpression ( 'Hogan' ) 
							) 
					),
					
					'events' => array (
							'selected' => new CJavascriptExpression ( "function(obj, datum, name) {
                    var    uid = datum.item_id;
                           $('#item_hsn_code').val(uid);
                           		checkBarcodes();
          
         }" ) 
					) 
			) );
			
			?>
   <?php echo $form->error($model,'item_id');?>
  </div>
											<div class="col-md-1 col-xs-12 padding2px">
												<label class="control-label" for="">Max Qty</label>
                  <?php echo $form->textField($model,'req_qty',array('class'=>'form-control')); ?>
                </div>

											<div class="col-md-1 col-xs-12 padding2px">
												<label class="control-label" for="">Appr Qty</label>
                 <?php echo $form->textField($model,'approved_qty',array('class'=>'form-control')); ?>
                </div>

											<div class="col-md-1 col-xs-12 padding2px">
												<label class="control-label" for="">Is free</label>
                  <?php echo $form->dropDownList($model, 'is_free',$model->getFreeItemOptions(),array('class'=>'form-control')); ?>
               
                </div>

											<div class="col-md-1 col-xs-12 padding2px">
												<label class="control-label" for="">Mrp</label>
                  <?php  echo $form->textField($model,'mrp',  array('class'=>'form-control')); ?>
                </div>


											<div class="col-md-1 col-xs-12 padding2px">
												<label class="control-label" for="">Price</label>
                  <?php  echo $form->textField($model,'price',  array('class'=>'form-control')); ?>
                </div>

											<div class="col-md-1 col-xs-12 padding2px">
												<label class="control-label" for="">Sale Rate</label>
                 <?php  echo $form->textField($model,'sale_rate',  array('class'=>'form-control')); ?>
                </div>
											<div class="col-md-1 col-xs-12 padding2px">
												<label class="control-label" for="">Margin</label>
                 <?php  echo $form->textField($model,'margin',  array('class'=>'form-control', 'rows'=>1)); ?>
                </div>
											<div class="col-md-1 col-xs-12 padding2px">
												<label class="control-label" for="">Disc %</label>
                  <?php  echo $form->textField($model,'discount',  array('class'=>'form-control')); ?>
                </div>



										</div>

										<div class="row">


											<div class="col-md-1 col-xs-12 padding2px">
												<label class="control-label" for="">Disc Amt</label>
                 <?php  echo $form->textField($model,'discount_amt',  array('class'=>'form-control')); ?>
                </div>
											<div class="col-md-1 col-xs-12 padding2px">
												<label class="control-label" for=""> Disc1 %</label>
                  <?php  echo $form->textField($model,'discount1',  array('class'=>'form-control')); ?>
                </div>

											<div class="col-md-1 col-xs-12 padding2px">
												<label class="control-label" for=""> Dis1 Amt</label>
                 <?php  echo $form->textField($model,'discount_amt1',  array('class'=>'form-control')); ?>
                </div>
											<div class="col-md-2 col-xs-12 padding2px">
												<label class="control-label" for="">Tax</label>
												<div id="item_tax_data"><?php  echo $form->textField($model,'tax_id',  array('class'=>'form-control')); ?></div>
                 <?php echo $form->error($model, 'tax_id');?>
                 
                </div>

<?php if($gst == true){?>
										<div class="col-md-1 col-xs-12 padding2px">
												<label class="control-label" for="">CGST %</label> <input
													type="text" name="PurchaseBillDetail[cgst_per]"
													id="CGST_per" class="form-control" placeholder="%">
											</div>

											<div class="col-md-1 col-xs-12 padding2px">
												<label class="control-label" for="">CGST Amt</label> <input
													type="text" name="PurchaseBillDetail[cgst_amt]"
													id="CGST_amt" class="form-control" placeholder="Amount">
											</div>


											<div class="col-md-1 col-xs-12 padding2px">
												<label class="control-label" for="">SGST %</label> <input
													type="text" name="PurchaseBillDetail[sgst_per]"
													id="SGST_per" class="form-control" placeholder="%">
											</div>

											<div class="col-md-1 col-xs-12 padding2px">
												<label class="control-label" for="">SGST Amt</label> <input
													type="text" name="PurchaseBillDetail[sgst_amt]"
													id="SGST_amt" class="form-control" placeholder="Amount">
											</div>

											<div class="col-md-1 col-xs-12 padding2px">
												<label class="control-label" for="">CESS %</label> <input
													type="text" name="PurchaseBillDetail[cess_per]"
													id="CESS_per" class="form-control" placeholder="%">
											</div>

											<div class="col-md-1 col-xs-12 padding2px">
												<label class="control-label" for="">CESS Amt</label> <input
													type="text" name="PurchaseBillDetail[cess_amt]"
													id="CESS_amt" class="form-control" placeholder="Amount">
											</div>
<?php }else{?>
	<div class="col-md-1 col-xs-12 padding2px">
												<label class="control-label" for="">CESS %</label> <input
													type="text" name="PurchaseBillDetail[cess_per]"
													id="CESS_per" class="form-control" placeholder="%">
											</div>
												<div class="col-md-1 col-xs-12 padding2px">
												<label class="control-label" for="">CESS Amt</label> <input
													type="text" name="PurchaseBillDetail[cess_amt]"
													id="CESS_amt" class="form-control" placeholder="Amount">
											</div>
<div class="col-md-1 col-xs-12 padding2px">
												<label class="control-label" for="">IGST %</label> <input
													type="text" name="PurchaseBillDetail[igst_per]"
													id="IGST_per" class="form-control" placeholder="%">
											</div>

											<div class="col-md-1 col-xs-12 padding2px">
												<label class="control-label" for="">IGST Amt</label> <input
													type="text" name="PurchaseBillDetail[igst_amt]"
													id="IGST_amt" class="form-control" placeholder="Amount">
											</div>
<?php }?>
<?php

/*
 * ?>
 * <div class="col-md-1 col-xs-12 padding2px">
 * <label class="control-label" for="">Charge</label>
 * <?php echo $form->textField($model,'other_charge', array('class'=>'form-control')); ?>
 * </div>
 */
?>

										<div class="col-md-1 col-xs-12 padding2px">
												<input type="hidden" id="grid_changed" class="form-control"
													value="0">
										<?php  echo $form->hiddenField($model,'other_charge',  array('class'=>'form-control')); ?>
											<label class="control-label" for="">Amount</label>
                  <?php  echo $form->textField($model,'amount',  array('class'=>'form-control')); ?>
                </div>



										</div>



									</div>

									<div class="col-md-2 col-sm-12 col-xs-12 add-bttn-box">
                
                <?php
																
																$this->widget ( 'bootstrap.widgets.TbButton', array (
																		'buttonType' => 'button',
																		'type' => 'primary',
																		'label' => 'Add Item',
																		'htmlOptions' => array (
																				'id' => 'addItem' 
																		) 
																) );
																?>
	


                
                </div>

								</div>
	

<?php $this->endWidget(); ?>

</div>


							<div class="clearfix"></div>
							<hr />
							<div class="clearfix"></div>
							<div class="table-responsive customsmallgridwidth"> 
               
<?php

$form = $this->beginWidget ( 'bootstrap.widgets.TbActiveForm', array (
		'enableAjaxValidation' => true,
		'type' => 'horizontal',
		'id' => 'grn-qty' 
) );
?>
 <div class="col-md-12">
<?php
$model->bill_date = date ( 'Y-m-d' );
echo $form->datepickerRow ( $model, 'bill_date', array (
		'hint' => 'Click inside! to select a date.',
		'prepend' => '<i class="icon-calendar"></i>',
		'options' => array (
				'format' => 'yyyy-mm-dd',
				'class' => 'billl' 
		) 
) );
?>
</div>
								<div class="col-md-12">
								<?php
								
if ($mrs != null) {
									$model->bill_no = $mrs->bill_no;
								}
								?>
<?php echo $form->textFieldRow($model,'bill_no',array('class'=>'form-control PurchaseBill_billno','maxlength'=>255)); ?>
</div>
								<div class="col-md-2"></div>
								<div class="col-md-10">
									<div class="checkbox">
										<label> <input type="checkbox" class="" id="is_consignment">Is
											Consignment
										</label>
									</div>
								</div>
 
<?php
$this->widget ( 'bootstrap.widgets.TbGridView', array (
		'id' => 'purchase-bill-detail-grid',
		'dataProvider' => $model->search (),
		
		'filter' => $model,
		'itemsCssClass' => 'table table-bordered table-striped dataTable',
		'columns' => array (
				// array(
				// 'id'=>'mrsId',
				// 'class'=>'CCheckBoxColumn',
				// 'selectableRows' => '50',
				// ),
				array (
						'header' => 'SN.',
						'value' => '++$row' 
				),
				array (
						'name' => 'item_id',
						'value' => 'GxHtml::valueEx($data->item)',
						// 'filter' => GxHtml::listDataEx ( Item::model ()->findAllAttributes ( null, true ) )
						'filter' => false 
				),
				array (
						'header' => '<a>Bar Code</a>',
						'name' => 'item_detail_id',
						'value' => 'GxHtml::valueEx($data->itemDetail)',
						// 'filter' => GxHtml::listDataEx ( ItemDetail::model ()->findAllAttributes ( null, true ) )
						'filter' => false 
				),
				array (
						
						'name' => 'hsn_code',
						
						'value' => 'GxHtml::activeTextField($data,\'hsn_code\', array("id"=>"hsn_code_input$data->id","value"=>$data->gethsncode(),"class"=>"hsn_code_input"))',
						'type' => 'raw',
						'htmlOptions' => array (
								'class' => 'hsn_code_input' 
						),
						'filter' => false 
				)
				,
		/* 	array(
					'name'=>'outlet_id',
					'value'=>'GxHtml::valueEx($data->outlet)',
					'filter'=>GxHtml::listDataEx(Outlet::model()->findAllAttributes(null, true)),
			),
			array(
					'header'=>'vendor',
					'value'=>'GxHtml::valueEx($data->purchaseBill->vendor)',
					
			), */
				/* array (
						'header' => 'Free',
						'value' => '$data->getFreeItemOptions($data->is_free)',
						'filter' => false,
						'htmlOptions' => array (
								'id' => 'free_field'
						)
				), */
				array (
						
						'header' => 'Free Val',
						'value' => 'GxHtml::activeTextField($data,\'is_free\', array("id"=>"free_val$data->id","class"=>"free_val_qty","value"=>$data->getFreeItemOptions($data->is_free),"readOnly"=>"readOnly"))',
						'type' => 'raw',
						'htmlOptions' => array (
								'class' => 'free_val_field' 
						) 
				),
				array (
						'header' => 'Ttl Rmn Qty',
						'value' => 'isset($data->item)?$data->item->getTotalRemainingQuantity():""',
						'htmlOptions' => array (
								'class' => 'item_qty_field' 
						) 
				),
				array (
						
						'name' => 'req_qty',
						
						'value' => 'GxHtml::activeTextField($data,\'req_qty\', array("id"=>"req_input_qty$data->id","class"=>"req_input_qty","readOnly"=>true))',
						'type' => 'raw',
						'htmlOptions' => array (
								'class' => 'req_qty' 
						),
						'filter' => false 
				)
				,
				/* array (
						'header' => 'Max Qty',
						'value' => '$data->req_qty',
						'filter' => false 
				), */
				array (
						
						'header' => 'App Qty',
						'value' => 'GxHtml::activeTextField($data,\'approved_qty\', array("id"=>"approve_input_qty$data->id","class"=>"approve_input_qty"))',
						'type' => 'raw',
						'htmlOptions' => array (
								'class' => 'approve_qty' 
						) 
				),
				array (
						
						'header' => 'Mrp',
						'value' => 'GxHtml::activeTextField($data,\'mrp\', array("id"=>"mrp_input$data->id","class"=>"mrp_input"))',
						'type' => 'raw',
						'htmlOptions' => array (
								'class' => 'mrp' 
						) 
				),
				array (
						'header' => 'Sale Rate',
						'value' => 'GxHtml::activeTextField($data,\'sale_rate\', array("id"=>"sale_rate$data->id","class"=>"sale_rate_input","disabled"=>$data->getCompanyBarcode($data->item_detail_id)))',
						'type' => 'raw',
						'htmlOptions' => array (
								'class' => 'sale_rate' 
						) 
				),
				
				array (
						
						'header' => 'Price',
						'value' => 'GxHtml::activeTextField($data,\'price\', array("id"=>"price_input$data->id","class"=>"price_inpput"))',
						'type' => 'raw',
						'htmlOptions' => array (
								'class' => 'price' 
						) 
				),
				array (
						
						'header' => 'Margin',
						'value' => 'GxHtml::activeTextField($data,\'margin\', array("id"=>"margin_input$data->id","class"=>"margin_inpput","readOnly"=>true))',
						'type' => 'raw',
						'htmlOptions' => array (
								'class' => 'margin' 
						) 
				),
				array (
						
						'header' => 'Amt',
						'value' => 'GxHtml::activeTextField($data,\'amount\',array("id"=>"total_amount_input$data->id","class"=>"total_amount_input"))',
						'type' => 'raw',
						'htmlOptions' => array (
								'class' => 'amount' 
						) 
				),
				array (
						
						'header' => 'Dis%',
						'value' => 'GxHtml::activeTextField($data,\'discount\',array("id"=>"discount_input$data->id","class"=>"discount_input"))',
						'type' => 'raw',
						'htmlOptions' => array (
								'class' => 'discount' 
						) 
				),
				array (
						
						'header' => 'Disc Amt',
						'value' => 'GxHtml::activeTextField($data,\'discount_amt\',array("id"=>"discount_amt_input$data->id","class"=>"discount_amt_input"))',
						'type' => 'raw',
						'htmlOptions' => array (
								'class' => 'discount_amt' 
						) 
				),
				array (
						
						'header' => 'Other Disc%',
						'value' => 'GxHtml::activeTextField($data,\'discount1\',array("id"=>"discount1_input$data->id","class"=>"discount1_input"))',
						'type' => 'raw',
						'htmlOptions' => array (
								'class' => 'discount1' 
						) 
				),
				array (
						
						'header' => 'Other Disc Amt',
						'value' => 'GxHtml::activeTextField($data,\'discount_amt1\',array("id"=>"discount_amt1_input$data->id","class"=>"discount_amt1_input"))',
						'type' => 'raw',
						'htmlOptions' => array (
								'class' => 'discount_amt1' 
						) 
				),
				array (
						'header' => 'Tax',
						'value' => 'GxHtml::dropDownList("PurchaseBillDetail[tax_id]",$data->tax_id,$data->getAllTaxOptions($data->tax_id, $data->purchase_bill_id),array("id"=>"select_tax$data->id","class"=>"select_tax"))',
						'type' => 'raw',
						'htmlOptions' => array (
								'class' => 'select_tax_val' 
						) 
				),
				array (
						'visible' => $model->getGSTTrue ( $poid ) == true,
						'header' => 'CGST (%age)',
						'value' => 'GxHtml::activeTextField($data,\'cgst_per\',array("id"=>"CGST_per_input$data->id","class"=>"CGST_per_input","ReadOnly"=>"ReadOnly"))',
						'type' => 'raw',
						'htmlOptions' => array (
								'class' => 'CGST_per_input' 
						) 
				),
				array (
						'visible' => $model->getGSTTrue ( $poid ) == true,
						'header' => 'SGST (%age)',
						'value' => 'GxHtml::activeTextField($data,\'sgst_per\',array("id"=>"SGST_per_input$data->id","class"=>"SGST_per_input","ReadOnly"=>"ReadOnly"))',
						'type' => 'raw',
						'htmlOptions' => array (
								'class' => 'SGST_per_input' 
						) 
				),
				array (
						// 'visible' => $model->getGSTTrue ( $poid ) == true,
						'header' => 'CESS (%age)',
						'value' => 'GxHtml::activeTextField($data,\'cess_per\',array("id"=>"CESS_per_input$data->id","class"=>"CESS_per_input","ReadOnly"=>"ReadOnly"))',
						'type' => 'raw',
						'htmlOptions' => array (
								'class' => 'CESS_per_input' 
						) 
				),
				array (
						'visible' => $model->getGSTTrue ( $poid ) == true,
						'header' => 'CGST Amt',
						'value' => 'GxHtml::activeTextField($data,\'cgst_amt\',array("id"=>"CGST_amt_input$data->id","class"=>"CGST_amount_input","ReadOnly"=>"ReadOnly"))',
						'type' => 'raw',
						'htmlOptions' => array (
								'class' => 'CGST_amt_input' 
						) 
				),
				array (
						'visible' => $model->getGSTTrue ( $poid ) == true,
						'header' => 'SGST Amt',
						'value' => 'GxHtml::activeTextField($data,\'sgst_amt\',array("id"=>"SGST_amt_input$data->id","class"=>"SGST_amount_input","ReadOnly"=>"ReadOnly"))',
						'type' => 'raw',
						'htmlOptions' => array (
								'class' => 'SGST_amt_input' 
						) 
				),
				array (
						// 'visible' => $model->getGSTTrue ( $poid ) == true,
						'header' => 'CESS Amt',
						'value' => 'GxHtml::activeTextField($data,\'cess_amt\',array("id"=>"CESS_amt_input$data->id","class"=>"CESS_amount_input","ReadOnly"=>"ReadOnly"))',
						'type' => 'raw',
						'htmlOptions' => array (
								'class' => 'CESS_amt_input' 
						) 
				),
				array (
						// 'visible' => $model->getGSTTrue ( $poid ) == false,
						'header' => 'IGST (%age)',
						'value' => 'GxHtml::activeTextField($data,\'igst_per\',array("id"=>"IGST_per_input$data->id","class"=>"IGST_per_input","ReadOnly"=>"ReadOnly"))',
						'type' => 'raw',
						'htmlOptions' => array (
								'class' => 'CGST_per_input' 
						) 
				),
				array (
						// 'visible' => $model->getGSTTrue ( $poid ) == false,
						'header' => 'IGST Amt',
						'value' => 'GxHtml::activeTextField($data,\'igst_amt\',array("id"=>"IGST_amt_input$data->id","class"=>"IGST_amount_input","ReadOnly"=>"ReadOnly"))',
						'type' => 'raw',
						'htmlOptions' => array (
								'class' => 'IGST_amt_input' 
						) 
				)
				,
				array (
					'header' => 'Sale Tax',
					'value' => 'GxHtml::dropDownList("PurchaseBillDetailSale[tax_id]",$data->sale_tax_id,$data->getAllTaxOptions($data->tax_id),array("id"=>"select_sale_tax$data->id","class"=>"select_sale_tax", "prompt" => "Please Select"))',
					'type' => 'raw',
					'htmlOptions' => array (
							'class' => 'select_sale_tax_val' 
					) 
			),
			array (
					'visible' => $model->getGSTTrue ( $poid ) == true,
					'header' => 'Sale CGST (%age)',
					'value' => 'GxHtml::activeTextField($data,\'sale_cgst_per\',array("id"=>"sale_CGST_per_input$data->id","class"=>"sale_CGST_per_input","ReadOnly"=>"ReadOnly"))',
					'type' => 'raw',
					'htmlOptions' => array (
							'class' => 'sale_CGST_per_input' 
					) 
			),
			array (
					'visible' => $model->getGSTTrue ( $poid ) == true,
					'header' => 'Sale SGST (%age)',
					'value' => 'GxHtml::activeTextField($data,\'sale_sgst_per\',array("id"=>"sale_SGST_per_input$data->id","class"=>"sale_SGST_per_input","ReadOnly"=>"ReadOnly"))',
					'type' => 'raw',
					'htmlOptions' => array (
							'class' => 'sale_SGST_per_input' 
					) 
			),
			array (
					// 'visible' => $model->getGSTTrue ( $poid ) == true,
					'header' => 'Sale CESS (%age)',
					'value' => 'GxHtml::activeTextField($data,\'sale_cess_per\',array("id"=>"sale_CESS_per_input$data->id","class"=>"sale_CESS_per_input","ReadOnly"=>"ReadOnly"))',
					'type' => 'raw',
					'htmlOptions' => array (
							'class' => 'sale_CESS_per_input' 
					) 
			),
			array (
					'visible' => $model->getGSTTrue ( $poid ) == true,
					'header' => 'Sale CGST Amt',
					'value' => 'GxHtml::activeTextField($data,\'sale_cgst_amt\',array("id"=>"sale_CGST_amt_input$data->id","class"=>"sale_CGST_amount_input","ReadOnly"=>"ReadOnly"))',
					'type' => 'raw',
					'htmlOptions' => array (
							'class' => 'sale_CGST_amt_input' 
					) 
			),
			array (
					'visible' => $model->getGSTTrue ( $poid ) == true,
					'header' => 'Sale SGST Amt',
					'value' => 'GxHtml::activeTextField($data,\'sale_sgst_amt\',array("id"=>"sale_SGST_amt_input$data->id","class"=>"sale_SGST_amount_input","ReadOnly"=>"ReadOnly"))',
					'type' => 'raw',
					'htmlOptions' => array (
							'class' => 'sale_SGST_amt_input' 
					) 
			),
			array (
					// 'visible' => $model->getGSTTrue ( $poid ) == true,
					'header' => 'Sale CESS Amt',
					'value' => 'GxHtml::activeTextField($data,\'sale_cess_amt\',array("id"=>"sale_CESS_amt_input$data->id","class"=>"sale_CESS_amount_input","ReadOnly"=>"ReadOnly"))',
					'type' => 'raw',
					'htmlOptions' => array (
							'class' => 'sale_CESS_amt_input' 
					) 
			),
			array (
					// 'visible' => $model->getGSTTrue ( $poid ) == false,
					'header' => 'Sale IGST (%age)',
					'value' => 'GxHtml::activeTextField($data,\'sale_igst_per\',array("id"=>"sale_IGST_per_input$data->id","class"=>"sale_IGST_per_input","ReadOnly"=>"ReadOnly"))',
					'type' => 'raw',
					'htmlOptions' => array (
							'class' => 'sale_CGST_per_input' 
					) 
			),
			array (
					// 'visible' => $model->getGSTTrue ( $poid ) == false,
					'header' => 'Sale IGST Amt',
					'value' => 'GxHtml::activeTextField($data,\'sale_igst_amt\',array("id"=>"sale_IGST_amt_input$data->id","class"=>"sale_IGST_amount_input","ReadOnly"=>"ReadOnly"))',
					'type' => 'raw',
					'htmlOptions' => array (
							'class' => 'sale_IGST_amt_input' 
					) 
			)
			,
    		
    		 /* 
    		array(
    					
    				'header'=>'Other Charge',
    				'value'=>'GxHtml::activeTextField($data,\'other_charge\',array("id"=>"other_charge_input$data->id","class"=>"other_charge_input"))',
    				'type'=>'raw',
    				'htmlOptions'=>array('class'=>'other_charge'),
    					
    		), */
    		
				 
		) 
)
 );
?>
<script>
// function reloadGrid(data) {
    // $.fn.yiiGridView.update('menu-grid');
// }
$('#Bill_vendor_id').change(function(){
	var vendor = $('#Bill_vendor_id').val();
	var url = "<?php echo Yii::app()->createUrl('purchaseBillDetail/index'); ?>/vid/"+vendor;
	location.href = url; 
});
<?php if($vendor_id != null){?>
var select_vendor = "<?php echo $vid;?>";
$('#Bill_vendor_id').val(select_vendor);
<?php }?>
$(document).ready(function(){
	//$('#mrs-qty input').addClass('approved_qty');
	//var mrsid = ("#MrsDetail_mrs_id").val();
	<?php if($poid != ''){?>
	var poid = <?php echo $poid; ?>;
	<?php }else{?>
	var poid =   $("#PurchaseBillDetail_purchase_bill_id").val();
	<?php }?>
	
	$("#update").click(function (event) {
		if (confirm("Do you really want to update?") == true) {
			event.preventDefault();
			var formData = {};
			var mrpData = {};
			var hsncode = {};
			var priceData = {};
			var salerateData = {};
			var discountData = {};
			var discount_amtData = {};
			var discountData1 = {};
			var discount_amtData1 = {};
			var vatData = {};
			var other_chargeData = {};
			var amountData = {};
			var cgstData = {};
			var sgstData = {};
			var cessData = {};
			var cgstamtData = {};
			var sgstamtData = {};
			var cessamtData = {};
			var igstamtData = {};
			var igstData = {};
			var marginData = {};
			var taxselectData = {};
			//sale
			var salecgstData = {};
			var salesgstData = {};
			var salecessData = {};
			var salecgstamtData = {};
			var salesgstamtData = {};
			var salecessamtData = {};
			var saleigstamtData = {};
			var saleigstData = {};
			var saletaxselectData = {};
			var status = 1;
			var gross_amt = $('#gross_amount').val();
			var total_discount =   $('#total_discount').val();
			
			var tax_amount =   $('#tax_amount').val();
			var bill_amount =   $('#bill_amount').val();
			var bill_no =   $('#PurchaseBillDetail_bill_no').val();
			var bill_date =   $('#PurchaseBillDetail_bill_date').val();
			var net_bill_amount =   $('#net_bill_amount').val();
			var bill_other_discount =   $('#bill_other_discount').val();
			var credit_note_id =   $('#credit_note_id').val();
			var credit_note_disc =   $('#credit_note_disc').val();

			var grossNetBillAmount = parseFloat(parseFloat(+(+gross_amt).toFixed() + +(+tax_amount).toFixed()) - parseFloat(+(+total_discount).toFixed() + +(+bill_other_discount).toFixed())).toFixed();
			// if (grossNetBillAmount != net_bill_amount) {
			// 	alert('Net Bill Amount not matching with (Gross Amount + Tax Amount - Total Discount - Other Discount)');
			// 	return;
			// }
			
					
			var  is_consignment = 0;
			var link = '<?php echo Yii::app()->createUrl('purchaseBillDetail/list'); ?>';
				if($('#is_consignment').prop("checked")) {
							is_consignment = 1;
					} 
				if(bill_no != ''){
			$("td.approve_qty input:text").each(function(key,value){
				var val = $(this).val(); 
				var str = $(this).attr('id');
				var approved_qty = str.split('qty');
			
				formData[approved_qty['1']] = val;
			}); 
				$("td.mrp input:text").each(function(key,value){
					var val = $(this).val(); 
					var str = $(this).attr('id');
					var mrp = str.split('input');
					mrpData[mrp['1']] = val;
			}); 
			$("td.hsn_code_input input:text").each(function(key,value){
					var val = $(this).val(); 
					var str = $(this).attr('id');
					var hsn = str.split('input');
					hsncode[hsn['1']] = val;
			}); 
			
				$("td.price input:text").each(function(key,value){
					var val = $(this).val(); 
					var str = $(this).attr('id');
					var arr = str.split('input');
					var price = arr['1'];
					priceData[price] = val;
			}); 
				$("td.sale_rate input:text").each(function(key,value){
					var val = $(this).val(); 
					var str = $(this).attr('id');
					var salerate = str.split('rate');
						salerateData[salerate['1']] = val;
			}); 
				$("td.discount input:text").each(function(key,value){
				var val = $(this).val(); 
				var str = $(this).attr('id');
				var arr = str.split('input');
				var discount = arr['1'];
				discountData[discount] = val;
			}); 
			$("td.discount_amt input:text").each(function(key,value){
				var val = $(this).val(); 
				var str = $(this).attr('id');
				var arr = str.split('input');
				var discount_amt = arr['1'];
			
				discount_amtData[discount_amt] = val;
			}); 
			$("td.discount1 input:text").each(function(key,value){
				var val = $(this).val(); 
				var str = $(this).attr('id');
				var arr = str.split('input');
				var discount = arr['1'];
				discountData1[discount] = val;
			}); 
				$("td.discount_amt1 input:text").each(function(key,value){
					var val = $(this).val(); 
					var str = $(this).attr('id');
					var arr = str.split('input');
					var discount_amt = arr['1'];
				
					discount_amtData1[discount_amt] = val;
				});
			$("td.CGST_per_input input:text").each(function(key,value){
				var val = $(this).val(); 
				var str = $(this).attr('id');
				var arr = str.split('input');
				var vat = arr['1'];
				
				cgstData[vat] = val;
				
			}); 
			$("td.SGST_per_input input:text").each(function(key,value){
				var val = $(this).val(); 
				var str = $(this).attr('id');
				var arr = str.split('input');
				var vat = arr['1'];
				
				sgstData[vat] = val;
			});
			$("td.CESS_per_input input:text").each(function(key,value){
				var val = $(this).val(); 
				var str = $(this).attr('id');
				var arr = str.split('input');
				var vat = arr['1'];
				
				cessData[vat] = val;
				
			});
			$("td.CGST_amt_input input:text").each(function(key,value){
				var val = $(this).val(); 
				var str = $(this).attr('id');
				var arr = str.split('input');
				var vat = arr['1'];
				
				cgstamtData[vat] = val;
			});
			$("td.SGST_amt_input input:text").each(function(key,value){
				var val = $(this).val(); 
				var str = $(this).attr('id');
				var arr = str.split('input');
				var vat = arr['1'];
				
				sgstamtData[vat] = val;
			});
			$("td.CESS_amt_input input:text").each(function(key,value){
				var val = $(this).val(); 
				var str = $(this).attr('id');
				var arr = str.split('input');
				var vat = arr['1'];
				
				cessamtData[vat] = val;
			});
			// sale
			$("td.sale_CGST_per_input input:text").each(function(key,value){
				var val = $(this).val(); 
				var str = $(this).attr('id');
				var arr = str.split('input');
				var vat = arr['1'];
				
				salecgstData[vat] = val;
				
			}); 
			$("td.sale_SGST_per_input input:text").each(function(key,value){
				var val = $(this).val(); 
				var str = $(this).attr('id');
				var arr = str.split('input');
				var vat = arr['1'];
				
				salesgstData[vat] = val;
			});
			$("td.sale_CESS_per_input input:text").each(function(key,value){
				var val = $(this).val(); 
				var str = $(this).attr('id');
				var arr = str.split('input');
				var vat = arr['1'];
				
				salecessData[vat] = val;
				
			});
			$("td.sale_CGST_amt_input input:text").each(function(key,value){
				var val = $(this).val(); 
				var str = $(this).attr('id');
				var arr = str.split('input');
				var vat = arr['1'];
				
				salecgstamtData[vat] = val;
			});
			$("td.sale_SGST_amt_input input:text").each(function(key,value){
				var val = $(this).val(); 
				var str = $(this).attr('id');
				var arr = str.split('input');
				var vat = arr['1'];
				
				salesgstamtData[vat] = val;
			});
			$("td.sale_CESS_amt_input input:text").each(function(key,value){
				var val = $(this).val(); 
				var str = $(this).attr('id');
				var arr = str.split('input');
				var vat = arr['1'];
				
				salecessamtData[vat] = val;
			});
			$("td.other_charge input:text").each(function(key,value){
				var val = $(this).val(); 
				var str = $(this).attr('id');
				var arr = str.split('input');
				var other_charge = arr['1'];
				other_chargeData[other_charge] = val;
			}); 
			$("td.amount input:text").each(function(key,value){
				var val = $(this).val(); 
				var str = $(this).attr('id');
				var arr = str.split('input');
				var amount = arr['1'];
				
				amountData[amount] = val;
			}); 
			
			
			$("td.IGST_per_input input:text").each(function(key,value){
				var val = $(this).val(); 
				var str = $(this).attr('id');
				var arr = str.split('input');
				var vat = arr['1'];
				
				igstData[vat] = val;
			});
			$("td.IGST_amt_input input:text").each(function(key,value){
				var val = $(this).val(); 
				var str = $(this).attr('id');
				var arr = str.split('input');
				var vat = arr['1'];
				
				igstamtData[vat] = val;
			});
			// sale
			$("td.sale_IGST_per_input input:text").each(function(key,value){
				var val = $(this).val(); 
				var str = $(this).attr('id');
				var arr = str.split('input');
				var vat = arr['1'];
				
				saleigstData[vat] = val;
			});
			$("td.sale_IGST_amt_input input:text").each(function(key,value){
				var val = $(this).val(); 
				var str = $(this).attr('id');
				var arr = str.split('input');
				var vat = arr['1'];
				
				saleigstamtData[vat] = val;
			});
			$("td.select_tax_val select").each(function(key,value){
				var str = $(this).attr('id');
				console.log('str'+str);
				var arr = str.split('tax');
				var vat = arr['1'];
				var val = $('#'+str).val(); 
				taxselectData[vat] = val;
			});
			// sale
			$("td.select_sale_tax_val select").each(function(key,value){
				var str = $(this).attr('id');
				console.log('str'+str);
				var arr = str.split('tax');
				var vat = arr['1'];
				var val = $('#'+str).val(); 
				saletaxselectData[vat] = val;
			});
			$("td.margin_input input:text").each(function(key,value){
				var val = $(this).val(); 
				var str = $(this).attr('id');
				var arr = str.split('input');
				var vat = arr['1'];
				
				marginData[vat] = val;
			});
			
			
			$.ajax({
						url: '<?php echo Yii::app()->createUrl('purchaseBillDetail/ajaxupdate'); ?>/id/'+poid,
						type: 'post',

						data: {
							qty: formData,
									mrp: mrpData,
									salerate: salerateData,
									discount: discountData,
									discount_amt: discount_amtData,
									discount1: discountData1,
									discount_amt1: discount_amtData1,
									// cgstData: cgstData,
									cgstData: sgstData,
									sgstData: sgstData,
									cessData: cessData,
									cgstamtData: cgstamtData,
									sgstamtData: sgstamtData,
									cessamtData: cessamtData,
									igstData: igstData,
									igstamtData: igstamtData,
									// sale
									salecgstData: salesgstData,
									salesgstData: salesgstData,
									salecessData: salecessData,
									salecgstamtData: salecgstamtData,
									salesgstamtData: salesgstamtData,
									salecessamtData: salecessamtData,
									saleigstData: saleigstData,
									saleigstamtData: saleigstamtData,
									price: priceData,
									vat: vatData,
									other_charge: other_chargeData,
									amount: amountData,
								hsncode:hsncode,
									is_consignment:is_consignment,
									gross_amt: gross_amt,
									total_discount: total_discount,
									tax_amount: tax_amount,
									bill_amount: bill_amount,
									bill_no:bill_no,
									bill_date:bill_date,
									net_bill_amount:net_bill_amount,
								bill_other_discount :bill_other_discount,
								taxselectData:taxselectData,
								//sale
								saletaxselectData:saletaxselectData,
							credit_note_id:credit_note_id,
					credit_note_disc:credit_note_disc,
					marginData:marginData
						
									},
						success: function (data) {
							if(data == 'Success'){
							alert('Data is saved successfully');
							location.reload();
							}else{
								/*  if(data == 'Advance'){
								alert('Please do advance payment first'); 
								} */
								if(data == 'Failed'){
									alert('There is some error. Please try again'); 
									}
							}
						}
				
				}); 
			}else{
					alert('Please enter bill number'); 
			}
			       
		}			 
	}); 
		  
		
	   
		
	$("#approve").click(function (event) {
	event.preventDefault();
	var formData = {};
	var mrpData = {};
	var priceData = {};
	var salerateData = {};
	var discountData = {};
	var discount_amtData = {};
	var discountData1 = {};
	var discount_amtData1 = {};
	var vatData = {};
	var other_chargeData = {};
	var amountData = {};
	var cgstData = {};
	var sgstData = {};
	var cessData = {};
	var cgstamtData = {};
	var sgstamtData = {};
	var cessamtData = {};
	var igstamtData = {};
	var igstData = {};
	//sale
	var salecgstData = {};
	var salesgstData = {};
	var salecessData = {};
	var salecgstamtData = {};
	var salesgstamtData = {};
	var salecessamtData = {};
	var saleigstamtData = {};
	var saleigstData = {};
	var saletaxselectData = {};
	var marginData = {};
	var taxselectData = {};
	var hsncode = {};
	var status = 1;
	var gross_amt = $('#gross_amount').val();
	var total_discount =   $('#total_discount').val();
	
	var tax_amount =   $('#tax_amount').val();
	var bill_amount =   $('#bill_amount').val();
	var bill_no =   $('#PurchaseBillDetail_bill_no').val();
	var bill_date =   $('#PurchaseBillDetail_bill_date').val();
	var net_bill_amount =   $('#net_bill_amount').val();
	var bill_other_discount =   $('#bill_other_discount').val();
	var credit_note_id =   $('#credit_note_id').val();
	var credit_note_disc =   $('#credit_note_disc').val();

	var grossNetBillAmount = parseFloat(parseFloat(+(+gross_amt).toFixed() + +(+tax_amount).toFixed()) - parseFloat(+(+total_discount).toFixed() + +(+bill_other_discount).toFixed())).toFixed();
	if (grossNetBillAmount != net_bill_amount) {
		alert('Net Bill Amount not matching with (Gross Amount + Tax Amount - Total Discount - Other Discount)');
		return;
	}
	if(bill_no != ''){
		$.ajax({
		       url: '<?php echo Yii::app()->createUrl('purchaseBillDetail/ajaxBillNo'); ?>/id/'+poid,
		       type: 'post',

		       data: {
		    	   bill_no:bill_no,
	             
		             },
		       success: function (data) {
			if(data == 'Success'){
			
	var  is_consignment = 0;
	var link = '<?php echo Yii::app()->createUrl('purchaseBillDetail/list'); ?>';
	  if($('#is_consignment').prop("checked")) {
	        is_consignment = 1;
	    } 
	$("td.approve_qty input:text").each(function(key,value){
		 var val = $(this).val(); 
		var str = $(this).attr('id');
		 var approved_qty = str.split('qty');
	
	   formData[approved_qty['1']] = val;
}); 
$("td.hsn_code_input input:text").each(function(key,value){
			 var val = $(this).val(); 
			 var str = $(this).attr('id');
			 var hsn = str.split('input');
			 hsncode[hsn['1']] = val;
	}); 
	$("td.mrp input:text").each(function(key,value){
		 var val = $(this).val(); 
		 var str = $(this).attr('id');
		 var mrp = str.split('input');
		 mrpData[mrp['1']] = val;
}); 
	$("td.price input:text").each(function(key,value){
		 var val = $(this).val(); 
		 var str = $(this).attr('id');
		 var arr = str.split('input');
		 var price = arr['1'];
		priceData[price] = val;
}); 
	$("td.sale_rate input:text").each(function(key,value){
		 var val = $(this).val(); 
		 var str = $(this).attr('id');
		 var salerate = str.split('rate');
	    salerateData[salerate['1']] = val;
}); 
	$("td.discount input:text").each(function(key,value){
	 var val = $(this).val(); 
	 var str = $(this).attr('id');
	 var arr = str.split('input');
	 var discount = arr['1'];
	 discountData[discount] = val;
}); 
	$("td.discount_amt input:text").each(function(key,value){
		 var val = $(this).val(); 
		 var str = $(this).attr('id');
		 var arr = str.split('input');
		 var discount_amt = arr['1'];
	
		 discount_amtData[discount_amt] = val;
	}); 
	$("td.discount1 input:text").each(function(key,value){
		 var val = $(this).val(); 
		 var str = $(this).attr('id');
		 var arr = str.split('input');
		 var discount = arr['1'];
		 discountData1[discount] = val;
	}); 
		$("td.discount_amt1 input:text").each(function(key,value){
			 var val = $(this).val(); 
			 var str = $(this).attr('id');
			 var arr = str.split('input');
			 var discount_amt = arr['1'];
		
			 discount_amtData1[discount_amt] = val;
		});
	$("td.CGST_per_input input:text").each(function(key,value){
		 var val = $(this).val(); 
		 var str = $(this).attr('id');
		 var arr = str.split('input');
		 var vat = arr['1'];
		
		 cgstData[vat] = val;
	}); 
	$("td.SGST_per_input input:text").each(function(key,value){
		 var val = $(this).val(); 
		 var str = $(this).attr('id');
		 var arr = str.split('input');
		 var vat = arr['1'];
		
		 sgstData[vat] = val;
	});
	$("td.CESS_per_input input:text").each(function(key,value){
		 var val = $(this).val(); 
		 var str = $(this).attr('id');
		 var arr = str.split('input');
		 var vat = arr['1'];
		
		 cessData[vat] = val;
	});
	$("td.CGST_amt_input input:text").each(function(key,value){
		 var val = $(this).val(); 
		 var str = $(this).attr('id');
		 var arr = str.split('input');
		 var vat = arr['1'];
		
		 cgstamtData[vat] = val;
	});
	$("td.SGST_amt_input input:text").each(function(key,value){
		 var val = $(this).val(); 
		 var str = $(this).attr('id');
		 var arr = str.split('input');
		 var vat = arr['1'];
		
		 sgstamtData[vat] = val;
	});
	$("td.CESS_amt_input input:text").each(function(key,value){
		 var val = $(this).val(); 
		 var str = $(this).attr('id');
		 var arr = str.split('input');
		 var vat = arr['1'];
		
		 cessamtData[vat] = val;
	});
	// sale
	$("td.sale_CGST_per_input input:text").each(function(key,value){
				var val = $(this).val(); 
				var str = $(this).attr('id');
				var arr = str.split('input');
				var vat = arr['1'];
				
				salecgstData[vat] = val;
				
			}); 
			$("td.sale_SGST_per_input input:text").each(function(key,value){
				var val = $(this).val(); 
				var str = $(this).attr('id');
				var arr = str.split('input');
				var vat = arr['1'];
				
				salesgstData[vat] = val;
			});
			$("td.sale_CESS_per_input input:text").each(function(key,value){
				var val = $(this).val(); 
				var str = $(this).attr('id');
				var arr = str.split('input');
				var vat = arr['1'];
				
				salecessData[vat] = val;
				
			});
			$("td.sale_CGST_amt_input input:text").each(function(key,value){
				var val = $(this).val(); 
				var str = $(this).attr('id');
				var arr = str.split('input');
				var vat = arr['1'];
				
				salecgstamtData[vat] = val;
			});
			$("td.sale_SGST_amt_input input:text").each(function(key,value){
				var val = $(this).val(); 
				var str = $(this).attr('id');
				var arr = str.split('input');
				var vat = arr['1'];
				
				salesgstamtData[vat] = val;
			});
			$("td.sale_CESS_amt_input input:text").each(function(key,value){
				var val = $(this).val(); 
				var str = $(this).attr('id');
				var arr = str.split('input');
				var vat = arr['1'];
				
				salecessamtData[vat] = val;
			});
	$("td.other_charge input:text").each(function(key,value){
		 var val = $(this).val(); 
		 var str = $(this).attr('id');
		 var arr = str.split('input');
		 var other_charge = arr['1'];
		 other_chargeData[other_charge] = val;
	}); 
	$("td.amount input:text").each(function(key,value){
		 var val = $(this).val(); 
		 var str = $(this).attr('id');
		 var arr = str.split('input');
		 var amount = arr['1'];
		
		 amountData[amount] = val;
	}); 
	
	
	$("td.IGST_per_input input:text").each(function(key,value){
		 var val = $(this).val(); 
		 var str = $(this).attr('id');
		 var arr = str.split('input');
		 var vat = arr['1'];
		
		 igstData[vat] = val;
	});
	$("td.IGST_amt_input input:text").each(function(key,value){
		 var val = $(this).val(); 
		 var str = $(this).attr('id');
		 var arr = str.split('input');
		 var vat = arr['1'];
		
		 igstamtData[vat] = val;
	});
	// sale
	$("td.sale_IGST_per_input input:text").each(function(key,value){
				var val = $(this).val(); 
				var str = $(this).attr('id');
				var arr = str.split('input');
				var vat = arr['1'];
				
				saleigstData[vat] = val;
			});
			$("td.sale_IGST_amt_input input:text").each(function(key,value){
				var val = $(this).val(); 
				var str = $(this).attr('id');
				var arr = str.split('input');
				var vat = arr['1'];
				
				saleigstamtData[vat] = val;
			});
	$("td.select_tax_val select").each(function(key,value){
		 var str = $(this).attr('id');
		 console.log('str'+str);
		 var arr = str.split('tax');
		 var vat = arr['1'];
		 var val = $('#'+str).val(); 
		 taxselectData[vat] = val;
	});
	// sale
	$("td.select_sale_tax_val select").each(function(key,value){
			var str = $(this).attr('id');
			console.log('str'+str);
			var arr = str.split('tax');
			var vat = arr['1'];
			var val = $('#'+str).val(); 
			saletaxselectData[vat] = val;
		});
	$("td.margin_input input:text").each(function(key,value){
		 var val = $(this).val(); 
		 var str = $(this).attr('id');
		 var arr = str.split('input');
		 var vat = arr['1'];
		
		 marginData[vat] = val;
	});
	$.ajax({
	       url: '<?php echo Yii::app()->createUrl('purchaseBillDetail/ajaxupdate'); ?>/id/'+poid,
	       type: 'post',

	       data: {
	    	   qty: formData,
               mrp: mrpData,
               salerate: salerateData,
               discount: discountData,
               discount_amt: discount_amtData,
               discount1: discountData1,
               discount_amt1: discount_amtData1,
               // cgstData: cgstData,
                cgstData: sgstData,
               sgstData: sgstData,
               cessData: cessData,
			     hsncode:hsncode,
               cgstamtData: cgstamtData,
               sgstamtData: sgstamtData,
               cessamtData: cessamtData,
               igstData: igstData,
               igstamtData: igstamtData,
							 // sale
							 salecgstData: salesgstData,
								salesgstData: salesgstData,
								salecessData: salecessData,
								salecgstamtData: salecgstamtData,
								salesgstamtData: salesgstamtData,
								salecessamtData: salecessamtData,
								saleigstData: saleigstData,
								saleigstamtData: saleigstamtData,
               price: priceData,
               vat: vatData,
               other_charge: other_chargeData,
               amount: amountData,
               status : status,
               is_consignment:is_consignment,
               gross_amt: gross_amt,
               total_discount: total_discount,
               tax_amount: tax_amount,
               bill_amount: bill_amount,
               bill_no:bill_no,
               bill_date:bill_date,
           	   net_bill_amount:net_bill_amount,
	   	       bill_other_discount :bill_other_discount,
	   	       taxselectData:taxselectData,
						 //sale
						 saletaxselectData:saletaxselectData,
	   	    credit_note_id:credit_note_id,
	   	 credit_note_disc:credit_note_disc,
	     marginData:marginData
	   	   
	             },
	       success: function (data) {
		       if(data == 'Success'){
		       alert('Data is saved successfully');
		       window.location.href = link;
	    	   $.fn.yiiGridView.update('menu-grid'); 
		       }else{
		    	   if(data == 'Failed'){
			    	   alert('There is some error. Please try again'); 
				       }
		       }
	       }
		 
	  }); 
			}else{
				alert(bill_no+' is already taken for selected vendor');
			}
		       }
			 
		  }); 
	  
	}else{
		alert('Bill Number can not be blank');
	}
    });
	
	
})
</script>
								<button class="btn btn-info" id="update" type="submit"
									name="update">Update</button>	
<?php

if ($poid) {
	$getPurchaseBill = PurchaseBill::model ()->findByPk ( $poid );
	if ($getPurchaseBill) {
		if ($getPurchaseBill->status == PurchaseBill::STATUS_RECEIVED) {
			?>
	<button class="btn btn-primary" id="approve" type="submit"
									name="approve">Save</button>	
<?php
		}
	}
}
?>


<?php //echo CHtml::ajaxSubmitButton('Activate',array('mrsDetail/ajaxupdate','act'=>'Insert'), array('success'=>'reloadGrid')); ?>

<?php $this->endWidget(); ?>
  </div>
  
  
  	<?php
			$gross_amt = 0;
			$total_discount = 0;
			$tax_amount = 0;
			$bill_amount = 0;
			$mrn = PurchaseBill::model ()->findByPk ( $poid );
			if ($mrn) {
				$gross_amt = $mrn->gross_amt;
				$total_discount = $mrn->total_discount;
				$tax_amount = $mrn->tax_amount;
				$bill_amount = $mrn->bill_amount;
			}
			?>
							<div class="row">

								<div
									class="col-md-7  tax-table-responsive table-responsive customsmallgridwidth">
<?php $taxes = Tax::model()->findAllByAttributes(array('status'=>Tax::STATUS_ACTIVE));?>
<?php if($taxes){?>
<div id="taxes">
										<table>
											<tr>
												<th>Basic Value</th>
												<th>Title</th>
												<th>HRN Code</th>
												<th>CGST(%age)</th>
												<th>CGST Amount</th>
												<th>SGST(%age)</th>
												<th>SGST Amount</th>
												<th>CESS(%age)</th>
												<th>CESS Amount</th>
												<th>IGST(%age)</th>
												<th>IGST Amount</th>
											</tr>
<?php
	
	$total_cgst = '0.00';
	$total_sgst = '0.00';
	$total_cess = '0.00';
	$total_igst = '0.00';
	?>
<?php


	foreach ( $taxes as $tax ) {
		$total_cgst = $total_cgst + $tax->getPBillCgstAmount ( $poid, $tax->id, 'cgst_amt' );
		$total_sgst = $total_sgst + $tax->getPBillCgstAmount ( $poid, $tax->id, 'sgst_amt' );
		$total_cess = $total_cess + $tax->getPBillCgstAmount ( $poid, $tax->id, 'cess_amt' );
		$total_igst = $total_igst + $tax->getPBillCgstAmount ( $poid, $tax->id, 'igst_amt' );
		?>

			<tr>
												<td><?php echo $tax->getPBillAmount($poid,$tax->id);?></td>
												<td><?php echo $tax->title;?></td>
												<td><?php echo $tax->hrn_code;?></td>
												<td><?php echo $tax->tax_val1;?></td>
												<td><?php echo $tax->getPBillCgstAmount($poid,$tax->id,'cgst_amt');?></td>
												<td><?php echo $tax->tax_val2;?></td>
												<td><?php echo $tax->getPBillCgstAmount($poid,$tax->id,'sgst_amt');?></td>
												<td><?php echo $tax->tax_val3;?></td>
												<td>
												<?php


												echo $tax->getPBillCgstAmount($poid,$tax->id,'cess_amt');?></td>
												<td><?php echo $tax->tax_val4;?></td>
												<td><?php echo $tax->getPBillCgstAmount($poid,$tax->id,'igst_amt');?></td>
											</tr>

<?php }?>

<tr>
												<td></td>
												<td></td>
												<td></td>
												<td>Total Cgst</td>
												<td><b><?php echo $total_cgst;?></b></td>
												<td>Total Sgst</td>
												<td><b><?php echo $total_sgst;?></b></td>
												<td>Total Cess</td>
												<td><b><?php echo $total_cess;?></b></td>
												<td>Total Igst</td>
												<td><b><?php echo $total_igst;?></b></td>
											</tr>
										</table>
									</div>
<?php }?>
</div>
								<div class="col-md-5  ">
									<div class="total-bill">

										<div class="form-group">
											<label class="col-sm-4 control-label text-right">Gross Amount
											</label>
											<div class="col-sm-8">
												<input type="text" class="form-control" readonly="readonly"
													id="gross_amount" value="<?php echo $gross_amt;?>">
											</div>
											<div class="clearfix"></div>
										</div>
										<div class="form-group">
											<label class="col-sm-4 control-label text-right">Total
												Discount </label>
											<div class="col-sm-8">
												<input type="text" class="form-control" readonly="readonly"
													id="total_discount" value="<?php echo $total_discount;?>">
											</div>
											<div class="clearfix"></div>
										</div>
										<div class="form-group">
											<label class="col-sm-4 control-label text-right">Tax Amount</label>
											<div class="col-sm-8">
												<input type="text" class="form-control" readonly="readonly"
													id="tax_amount" value="<?php echo $tax_amount;?>">
											</div>
											<div class="clearfix"></div>
										</div>
										<div class="form-group">
											<label class="col-sm-4 control-label text-right">Bill Amount
											</label>
											<div class="col-sm-8">
												<input type="text" class="form-control" readonly="readonly"
													id="bill_amount" value="<?php echo $bill_amount;?>">
											</div>
											<div class="clearfix"></div>
										</div>
										<div class="form-group">
											<label class="col-sm-4 control-label text-right">Other
												Discount </label>
											<div class="col-sm-8">
												<input type="text" class="form-control"
													id="bill_other_discount" value="0.00">
											</div>
											<div class="clearfix"></div>
										</div>
										<div class="form-group">
											<label class="col-sm-4 control-label text-right">Bill Amount
											</label>
											<div class="col-sm-8">
												<input type="text" class="form-control" readonly="readonly"
													id="before_net_bill_amount"
													value="<?php echo $bill_amount;?>">
											</div>
											<div class="clearfix"></div>
										</div>
										<div class="form-group">
											<label class="col-sm-4 control-label text-right">Credit Note
											</label>
											<div class="col-sm-8">
												<input type="text" class="form-control" id="credit_note"
													value="">
											</div>
											<div class="clearfix"></div>
										</div>
										<div class="form-group">
											<label class="col-sm-4 control-label text-right">Credit Note
												Discount </label>
											<div class="col-sm-8">
												<input type="text" class="form-control"
													id="credit_note_disc" value="" readonly="readonly"> <input
													type="hidden" class="form-control" id="credit_note_id"
													value="">
											</div>
											<div class="clearfix"></div>
										</div>
										<div class="form-group">
											<label class="col-sm-4 control-label text-right">Net Bill
												Amount </label>
											<div class="col-sm-8">
												<input type="text" class="form-control" readonly="readonly"
													id="net_bill_amount" value="<?php echo $bill_amount;?>">
											</div>
											<div class="clearfix"></div>
										</div>
									</div>
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

$user = Yii::app ()->user->model;
$role = UserRole::model ()->findByAttributes ( array (
		'title' => 'Admin' 
) );
?>
<script>
$('form input').keydown(function (e) {
    if (e.keyCode == 13) {
        e.preventDefault();
        return false;
    }
});
$('#PurchaseBillDetail_purchase_bill_id').change(function(){
	var vendor_id = $('#PurchaseBillDetail_vendor_id').val();
	var poid = $('#PurchaseBillDetail_purchase_bill_id').val();
	var url = '<?php echo Yii::app()->createUrl('purchaseBillDetail/index')?>/id/'+vendor_id+'/poid/'+poid;
	window.location.href = url;
});
$('#credit_note').change(function(){
	<?php if($poid != ''){?>
	var poid = <?php echo $poid; ?>;
	<?php }else{?>
	var poid =   $("#PurchaseBillDetail_purchase_bill_id").val();
	<?php }?>
	var credit_note = $('#credit_note').val();
	var bill_amount  = $('#before_net_bill_amount').val();
	   jQuery.ajax({
	       'type': 'POST',
	       'url': '<?php echo CController::createUrl('purchaseBillDetail/applyCreditNote') ?>/id/'+poid,
	       'data': {'credit_note': credit_note,'bill_amount':bill_amount},
	       dataType: 'json',
	       'success': function (data) {
	           $('#credit_note_disc').val(data.amount);
	           $('#credit_note_id').val(data.id);
	           var bill_amountt = parseFloat(bill_amount) -  parseFloat(data.amount);
	           console.log(data);
	           console.log(data.amount);
	           $('#net_bill_amount').val(bill_amountt);
	          /*  if(data.message != ''){
	           alert(data.message);
	           } */
	       },
	       'cache': false
	    });
});
$(document).ready(function () {
	$('#PurchaseBillDetail_vendor_id').trigger('change');
	checkBarcodes();
	checkcompletecalc();
	   <?php if($vendor_id != null) {?>
	   var select_vendor = "<?php echo $vendor_id;?>";
	   $('#PurchaseBillDetail_vendor_id').val(select_vendor);
	   <?php }?>
	   <?php if($outlet_id != null) {?>
	   var select_outlet = "<?php echo $outlet_id;?>";
	   $('#PurchaseBillDetail_outlet_id').val(select_outlet);
	   <?php }?>
	   <?php if($start_date != null) {?>
	   var start_date = "<?php echo $start_date;?>";
	   $('#PurchaseBillDetail_start_date').val(start_date);
	   <?php }?>
	   var vendor_id = $('#PurchaseBillDetail_vendor_id').val();
		checkPONo(vendor_id);

 });
$('#PurchaseBillDetail_vendor_id').change(function(){
	var vendor_id = $('#PurchaseBillDetail_vendor_id').val();
	checkPONo(vendor_id);
 
});
$('#PurchaseBillDetail_item_id').change(function () {  
	checkBarcodes();
	
});
function checkcompletecalc(){
	 var calculated_amt = 0;
	 var total_discount_amt = 0;
	 var calculated_tax_amt = 0;
	 var calculated_gross = 0;
$(".total_amount_input").each(function() {
	
	 calculated_amt += parseFloat($(this).val());
	 console.log('val1'+$(this).val()); 
	   
 });
$(".discount_amt_input").each(function() {
	 var str = $(this).attr('id');
	 console.log('str'+str); 
	 var arr = str.split('input');
	 var id = arr['1'];
	 total_discount_amt += parseFloat($(this).val())+parseFloat($('#discount_amt1_input'+id).val());
	 console.log('val2'+$(this).val()); 
	   
 });
 	<?php if($model->getGSTTrue($poid) == true){?>
		$(".CGST_amount_input").each(function() {
			var str = $(this).attr('id');
			console.log('str'+str); 
			var arr = str.split('input');
			var id = arr['1'];
			var this_tax = parseFloat($('#CGST_amt_input'+id).val()) + parseFloat($('#SGST_amt_input'+id).val()) +
			parseFloat($('#CESS_amt_input'+id).val()) ;
			calculated_tax_amt += this_tax;
			console.log('val3'+calculated_tax_amt); 
				
		});
	<?php } else { ?>
		$(".IGST_amount_input").each(function() {
		var str = $(this).attr('id');
		console.log('str'+str); 
		var arr = str.split('input');
		var id = arr['1'];
		var this_tax = parseFloat($('#IGST_amt_input'+id).val()) + parseFloat($('#CESS_amt_input'+id).val()) ;
		calculated_tax_amt += this_tax;
		console.log('val3'+calculated_tax_amt); 
			
		});
	<?php } ?>
$(".price_inpput").each(function() {
	 var str = $(this).attr('id');
	 var arr = str.split('input');
	 var id = arr['1'];
	 var is_free = $('#free_val'+id).val();
	 var this_price = parseFloat($('#price_input'+id).val()) * parseFloat($('#approve_input_qty'+id).val()) ;
	 if(is_free != 'Yes'){
	 calculated_gross += this_price;
	 }
	 console.log('valgross'+calculated_gross); 
	   
 });
 $('#gross_amount').val(calculated_gross.toFixed(2));
 $('#total_discount').val(total_discount_amt.toFixed(2));
  $('#tax_amount').val(calculated_tax_amt.toFixed(2));
 $('#bill_amount').val(calculated_amt.toFixed());


 var bill_other_discount =  $('#bill_other_discount').val();
 var credit_note_disc =  $('#credit_note_disc').val();
 if(credit_note_disc ==''){
	   credit_note_disc = '0.00';
 }
 var before_net_bill_amount = parseNumber(parseFloat(calculated_amt) - parseFloat(bill_other_discount) - parseFloat(credit_note_disc));

 var net_bill_amount = parseNumber(parseFloat(calculated_amt) - parseFloat(bill_other_discount));


  $('#net_bill_amount').val(before_net_bill_amount);
  $('#before_net_bill_amount').val(net_bill_amount);
 }
function checkBarcodes(){
	 var item_id = $('#item_hsn_code').val();
	    jQuery.ajax({
	       'type': 'POST',
	       'url': '<?php echo CController::createUrl('purchaseBillDetail/ajaxItems') ?>',
	       'data': {'item_id': item_id},
	       'success': function (data) {
	          // $('#item_detail_data').html('');
	          // $('#item_detail_data').html(data);
	    	   $('#PurchaseBillDetail_bar_code').val('');
	           $('#PurchaseBillDetail_bar_code').val(data);
	           $('#PurchaseBillDetail_bar_code').trigger('change');
	         
	       },
	       'cache': false
	    }
	    );	
}
$('#PurchaseBillDetail_bar_code').change(function(){
	  checkTaxes();	
	});
function checkTaxes(){
//	 var item_id = $('#PurchaseBillDetail_item_id').val();
	 var item_id = $('#item_hsn_code').val();
		
	 var item_detail_id = $('#PurchaseBillDetail_bar_code').val();
	 var vendor_id = $('#PurchaseBillDetail_vendor_id').val();
//	 var item_detail_id = $('#PurchaseBillDetail_item_detail_id').val();
 jQuery.ajax({
	       'type': 'POST',
	       'url': '<?php echo CController::createUrl('purchaseBillDetail/ajaxPBillTax') ?>',
	       'data': {'item_id': item_id,'item_detail_id':item_detail_id,'vendor_id':vendor_id},
	       dataType: 'json',
	       'success': function (data) {
		       console.log('new'+data);
		       if(data.msg == 'success'){
	           $('#item_tax_data').html('');
	           $('#item_tax_data').html(data.options);
	           $('#CGST_per').val(data.cgst);
	           $('#SGST_per').val(data.sgst);
	           $('#CESS_per').val(data.cess);
	           $('#IGST_per').val(data.igst);
	           $('#PurchaseBillDetail_mrp').val(data.mrp);
	           $('#PurchaseBillDetail_price').val(data.price);
	           $('#PurchaseBillDetail_req_qty').val(data.max_qty);
	           $('#PurchaseBillDetail_item_detaill_id').val(data.item_detail_id);
	          
	           $('#PurchaseBillDetail_item_val_id').val(data.item_title);
	           $('#item_hsn_code').val(data.item_id);
	           $('#PurchaseBillDetail_sale_rate').val(data.sale_rate);
	           $('#PurchaseBillDetail_item_detaill_list_id').val(data.tax_id);
	           if(data.attr == 'readOnly'){
	           $('#PurchaseBillDetail_sale_rate').attr('readonly', true);
	           }else{
		           
	        	   $('#PurchaseBillDetail_sale_rate').attr('readonly', false);
	           }
	           calculation();
		       }else{
		    	   if(data.msg == 'Inactive'){
			       alert('Scanned Item is not of Active');
		    	   }else{
		    		   alert('Scanned Item is not of selected vendor');
		    	   }
			       location.reload();
		       }
	       },
	       'cache': false
	    }
	    );

 <?php 
/*
								       * ?>
								       * jQuery.ajax({
								       * 'type': 'POST',
								       * 'url': '<?php echo CController::createUrl('purchaseBillDetail/ajaxPBillTax') ?>',
								       * 'data': {'item_id': item_id,'item_detail_id':item_detail_id,'vendor_id':vendor_id},
								       * dataType: 'json',
								       * 'success': function (data) {
								       * $('#item_tax_data').html('');
								       * $('#item_tax_data').html(data.options);
								       * $('#CGST_per').val(data.cgst);
								       * $('#SGST_per').val(data.sgst);
								       * $('#CESS_per').val(data.cess);
								       * $('#IGST_per').val(data.igst);
								       * $('#PurchaseBillDetail_mrp').val(data.mrp);
								       * $('#PurchaseBillDetail_req_qty').val(data.max_qty);
								       * $('#PurchaseBillDetail_price').val(data.price);
								       * $('#PurchaseBillDetail_sale_rate').val(data.sale_rate);
								       * $('#PO_item_detaill_list_id').val(data.tax_id);
								       * $('#PurchaseBillDetail_item_detaill_id').val(data.item_detail_id);
								       * $('#PurchaseBillDetail_item_val_id').val(data.item_title);
								       * $('#item_hsn_code').val(data.item_id);
								       * if(data.attr == 'readOnly'){
								       * $('#PurchaseBillDetail_sale_rate').attr('readonly', true);
								       * }else{
								       *
								       * $('#PurchaseBillDetail_sale_rate').attr('readonly', false);
								       * }
								       * calculation();
								       * },
								       * 'cache': false
								       * }
								       * );
								       */
	?>
}
function checkPONo(vendor_id){
	<?php if($poid != ''){?>
	var poid = <?php echo $poid; ?>;
	<?php }else{?>
	var poid =   $("#PurchaseBillDetail_purchase_bill_id").val();
	<?php }?>
	   jQuery.ajax({
	       'type': 'POST',
	       'url': '<?php echo CController::createUrl('purchaseBillDetail/ajaxPONo') ?>',
	       'data': {'vendor_id': vendor_id},
	       'success': function (data) {
	           $('#po_detail_data').html('');
	           $('#po_detail_data').html(data);
	           $('#PurchaseBillDetail_purchase_bill_id').val(poid);
	       },
	       'cache': false
	    });
}
$('.approve_input_qty').change(function(){
	var mrp = $(this).val();
	var str = $(this).attr('id');
	 var arr = str.split('qty');
	 var id = arr['1'];
	
	 var approve_qty = $('#approve_input_qty'+id).val();
	 var max_qty = $('#req_input_qty'+id).val();
	  gridcalculation(id);
	 /*if(parseFloat(max_qty) < parseFloat(approve_qty)){
		
		 alert('Maximum Quantity can not be less than Approve Quantity');
		 $('#approve_input_qty'+id).val('0.000');
	 }else{
		 gridcalculation(id);
	 }*/
	
});
$('.mrp_input').change(function(){
	var mrp = $(this).val();
	var str = $(this).attr('id');
	 var arr = str.split('input');
	 var id = arr['1'];
	 if(mrp != ''){
			$('#sale_rate'+arr['1']).val(mrp);
		}
		<?php if($user->role_id != $role->id){?>
		$('#sale_rate'+arr['1']).attr('readonly', true);
		<?php }?>
		 gridcalculation(id);
	
});
$('.price_inpput').change(function(){


	var mrp = $(this).val();
	var price = mrp;
     var str = $(this).attr('id');
	 var arr = str.split('input');
	 var id = arr['1'];
	var qty = $('#approve_input_qty'+id).val();
	var search = mrp.search( '/' );
	if(search != '-1'){
	var price = parseFloat(mrp)/parseFloat(qty);
	}
	$(this).val(parseFloat(price).toFixed(2));

	 gridcalculation(id);
	
	
});
$('.discount_input').change(function(){
	var mrp = $(this).val();
	var str = $(this).attr('id');
	 var arr = str.split('input');
	 var id = arr['1'];
	 gridcalculation(id,val=0);
	
	
});

$('.other_charge_input').change(function(){
	var mrp = $(this).val();
	var str = $(this).attr('id');
	 var arr = str.split('input');
	 var id = arr['1'];
	 gridcalculation(id);
	
	
});
$('.discount_amt_input').change(function(){
	var mrp = $(this).val();
	var str = $(this).attr('id');
	 var arr = str.split('input');
	 var id = arr['1'];
	 gridcalculation(id,val=1);
	
	
});
$('.discount_amt1_input').change(function(){
	var mrp = $(this).val();
	var str = $(this).attr('id');
	 var arr = str.split('input');
	 var id = arr['1'];
	 gridcalculation(id,val=1);
	
	
});
$('.discount1_input').change(function(){
	var mrp = $(this).val();
	var str = $(this).attr('id');
	 var arr = str.split('input');
	 var id = arr['1'];
	 gridcalculation(id,val=2);
	
	
});
$('#bill_other_discount').change(function(){
	  var calculated_amt =  $('#bill_amount').val();
	   var bill_other_discount =  $('#bill_other_discount').val();
	   var credit_note_disc =  $('#credit_note_disc').val();
	   if(credit_note_disc ==''){
		   credit_note_disc = '0.00';
	   }
	   var before_net_bill_amount = parseNumber(parseFloat(calculated_amt) - parseFloat(bill_other_discount) - parseFloat(credit_note_disc));
	  
	   var net_bill_amount = parseNumber(parseFloat(calculated_amt) - parseFloat(bill_other_discount));
	  
	 
	    $('#net_bill_amount').val(before_net_bill_amount);
	    $('#before_net_bill_amount').val(net_bill_amount);
	    
	
	
});
$('.CGST_per_input').change(function(){
	var mrp = $(this).val();
	var str = $(this).attr('id');
	 var arr = str.split('input');
	 var id = arr['1'];
	 gridcalculation(id);
	
	
});
$('.SGST_per_input').change(function(){
	var mrp = $(this).val();
	var str = $(this).attr('id');
	 var arr = str.split('input');
	 var id = arr['1'];
	 gridcalculation(id);
	
	
});
$('.CESS_per_input').change(function(){
	var mrp = $(this).val();
	var str = $(this).attr('id');
	 var arr = str.split('input');
	 var id = arr['1'];
	 gridcalculation(id);
	
	
});
$('.CGST_amount_input').change(function(){
	var mrp = $(this).val();
	var str = $(this).attr('id');
	 var arr = str.split('input');
	 var id = arr['1'];
	 gridcalculation(id);
	
	
});
$('.SGST_amount_input').change(function(){
	var mrp = $(this).val();
	var str = $(this).attr('id');
	 var arr = str.split('input');
	 var id = arr['1'];
	 gridcalculation(id);
	
	
});
$('.CESS_amount_input').change(function(){
	var mrp = $(this).val();
	var str = $(this).attr('id');
	 var arr = str.split('input');
	 var id = arr['1'];
	 gridcalculation(id);
	
	
});
$('.IGST_per_input').change(function(){
	var mrp = $(this).val();
	var str = $(this).attr('id');
	 var arr = str.split('input');
	 var id = arr['1'];
	 gridcalculation(id);
	
	
});
$('.IGST_amount_input').change(function(){
	var mrp = $(this).val();
	var str = $(this).attr('id');
	 var arr = str.split('input');
	 var id = arr['1'];
	 gridcalculation(id);
	
	
});
$('.select_tax').change(function(){
	var tax = $(this).val();
	console.log('id'+$(this).attr('id'));
	var str = $(this).attr('id');
	 var arr = str.split('tax');
	 var id = arr['1'];
	
	var tax_id = $('#select_tax'+id).val();
	checkTaxVal(tax,id);
 
});

$('.select_sale_tax').change(function(){
	var tax = $(this).val();
	console.log('id'+$(this).attr('id'));
	var str = $(this).attr('id');
	 var arr = str.split('tax');
	 var id = arr['1'];
	
	var tax_id = $('#select_sale_tax'+id).val();
	checkSaleTaxVal(tax,id);
 
});
function checkTaxVal(tax_id,id){
	  jQuery.ajax({
	       'type': 'POST',
	       'url': '<?php echo CController::createUrl('purchaseBillDetail/ajaxTax') ?>',
	       'data': {'tax_id': tax_id},
	       dataType: 'json',
	       'success': function (data) {
	           $('#CGST_per_input'+id).val(data.cgst);
	           $('#SGST_per_input'+id).val(data.sgst);
	           $('#CESS_per_input'+id).val(data.cess);
	           $('#IGST_per_input'+id).val(data.igst);
	           gridcalculation(id);
	       },
	       'cache': false
	    }
	    );
	  
}
function checkSaleTaxVal(tax_id,id){
	  jQuery.ajax({
	       'type': 'POST',
	       'url': '<?php echo CController::createUrl('purchaseBillDetail/ajaxTax') ?>',
	       'data': {'tax_id': tax_id},
	       dataType: 'json',
	       'success': function (data) {
					console.log(data);
	           $('#sale_CGST_per_input'+id).val(data.cgst);
	           $('#sale_SGST_per_input'+id).val(data.sgst);
	           $('#sale_CESS_per_input'+id).val(data.cess);
	           $('#sale_IGST_per_input'+id).val(data.igst);
	          //  gridcalculation(id);
						var CGST_amt = '0.00';
						var SGST_amt = '0.00';
						var CESS_amt = '0.00';
						var IGST_amt = '0.00';
						var CGST_per = '0.00';
						var SGST_per = '0.00';
						var CESS_per = '0.00';
						var IGST_per = '0.00';
						var price = $('#price_input'+id).val();
						var CGST_per = $('#sale_CGST_per_input'+id).val();
							var  SGST_per = $('#sale_SGST_per_input'+id).val();
							var CESS_per = $('#sale_CESS_per_input'+id).val(); 
							var IGST_per = $('#sale_IGST_per_input'+id).val(); 
							
						if(price != ''){
							var qty = $('#approve_input_qty'+id).val();
							//console.log('discount_amt1'+discount_amt1);
							var calculate_amount = qty * (parseFloat(price));
							var taxable_amount = parseFloat(calculate_amount); 
							CGST_amt = taxable_amount * CGST_per/100;
							SGST_amt = taxable_amount * SGST_per/100;
							CESS_amt = taxable_amount * CESS_per/100; 
							IGST_amt = taxable_amount * IGST_per/100; 
								$('#sale_CGST_amt_input'+id).val(parseFloat(CGST_amt).toFixed(2));
								$('#sale_SGST_amt_input'+id).val(SGST_amt.toFixed(2));
								$('#sale_CESS_amt_input'+id).val(CESS_amt.toFixed(2));
								$('#sale_IGST_amt_input'+id).val(IGST_amt.toFixed(2));
						}
	       },
	       'cache': false
	    }
	    );
	  
}
localStorage.clear();
function checkTaxTable(tax_id,id){
	var cgst = '0.00';
	var sgst = '0.00';
	var cess = '0.00';
	var igst = '0.00';
	var purchase_ids = [];
	$(".select_tax").each(function() {
		 var str = $(this).attr('id');
		 console.log('str'+str); 
		 var arr = str.split('tax');
		 var valid = arr['1'];
		
		console.log('kriti'+valid );
		 var free_val = $('#free_val'+valid).val();
		 var app_id = $('#select_tax'+valid).val();
		 var cgst = $('#CGST_amt_input'+valid).val();
		 var sgst = $('#SGST_amt_input'+valid).val();
		 var cess = $('#CESS_amt_input'+valid).val();
		 var igst = $('#IGST_amt_input'+valid).val();
		 var qty = $('#approve_input_qty'+valid).val();
		 var price = $('#price_input'+valid).val();
		 var discount = $('#discount_amt_input'+valid).val();
		 var discount1 = $('#discount_amt1_input'+valid).val();
		 var igst = $('#IGST_amt_input'+valid).val();
		 if(free_val == 'No'){
			 var is_free = 0;
		 }else{
			 var is_free = 1;
		 }
		 var data =  {
				 "id": valid,
			        "tax_id":app_id,
			        "cgst" :cgst,
			        "sgst" :sgst,
			        "cess" :cess,
			        "igst" :igst,
			        "qty" : qty,
			        "price":price,
			        "discount":discount,
			        "discount1":discount1,
			        "is_free" :is_free
			       
		    };
		 var receiveddata = JSON.stringify(data); 
		
		 console.log('receiveddata'+receiveddata);
		  
		purchase_ids.push(receiveddata);
		console.log('purchase_ids'+purchase_ids);
		
			
		   
	    });
	    
	
	$(".CGST_amount_input").each(function() {
		 var str = $(this).attr('id');
		// console.log('str'+str); 
		 var arr = str.split('input');
		 var valid = arr['1'];
		 var app_id = $('#select_tax'+valid).val();
		 if(app_id == tax_id){
		 var this_tax = parseFloat(cgst) + parseFloat($('#CGST_amt_input'+valid).val());
		 cgst = this_tax;
		 }
		 
		   
	    });
	$(".SGST_amount_input").each(function() {
		 var str = $(this).attr('id');
		// console.log('str'+str); 
		 var arr = str.split('input');
		 var valid = arr['1'];
		 var app_id = $('#select_tax'+valid).val();
		 if(app_id == tax_id){
		 var this_tax = parseFloat(sgst) + parseFloat($('#SGST_amt_input'+valid).val());
		 sgst = this_tax;
		 }
		 
		   
	    });
	$(".CESS_amount_input").each(function() {
		 var str = $(this).attr('id');
		// console.log('str'+str); 
		 var arr = str.split('input');
		 var valid = arr['1'];
		 var app_id = $('#select_tax'+valid).val();
		 if(app_id == tax_id){
			 var this_tax = parseFloat(cess) + parseFloat($('#CESS_amt_input'+valid).val());
			 cess = this_tax;
		
		 }
		 
		   
	    });
	$(".IGST_amount_input").each(function() {
		 var str = $(this).attr('id');
		// console.log('str'+str); 
		 var arr = str.split('input');
		 var valid = arr['1'];
		 var app_id = $('#select_tax'+valid).val();
		 if(app_id == tax_id){
			 var this_tax = parseFloat(igst) + parseFloat($('#IGST_amt_input'+valid).val());
			 igst = this_tax;
		
		 }
		 
		   
	    });
   // console.log('cgst'+cgst);
   //console.log('sgst'+sgst);
    console.log('cess'+cess);
  //  console.log('igst'+igst);

	jQuery.ajax({
	       'type': 'POST',
	       'url': '<?php echo CController::createUrl('purchaseBillDetail/ajaxTaxTable') ?>',
	       'data': {'tax_id': tax_id,'id':id,
	    	   'cgst': cgst,'sgst':sgst,
	    	   'cess': cess,'igst':igst,'purchase_ids':purchase_ids},
	      // dataType: 'json',
	       'success': function (data) {
		      console.log('data'+data);
	          $('#taxes').html('');
	         $('#taxes').html(data);
	       },
	       'cache': false
	    }
	    );
}
function parseNumber(val, decimalPlaces) {
    if (decimalPlaces == null) decimalPlaces = 0
    var ret = Number(val).toFixed(decimalPlaces)
    return Number(ret)
}
function gridcalculation(id,val=1){
	var CGST_amt = '0.00';
	var SGST_amt = '0.00';
	var CESS_amt = '0.00';
	var IGST_amt = '0.00';
	var CGST_per = '0.00';
	var SGST_per = '0.00';
	var CESS_per = '0.00';
	var IGST_per = '0.00';
	var price = $('#price_input'+id).val();
	var discount = $('#discount_input'+id).val();
	var discount1 = $('#discount1_input'+id).val();
	 var CGST_per = $('#CGST_per_input'+id).val();
    var  SGST_per = $('#SGST_per_input'+id).val();
     var CESS_per = $('#CESS_per_input'+id).val(); 
     var IGST_per = $('#IGST_per_input'+id).val(); 
     if(discount == '' ){
         discount = '0.00';
     }
     if(discount1 == '' ){
         discount1 = '0.00';
     }
     
	if(price != '' &&  discount != ''){
		var qty = $('#approve_input_qty'+id).val();
		var discount_amt = qty * (price * discount/100);
		console.log('discount'+discount);
		console.log('discount_amt'+discount_amt);
		if(val !=0 && discount =='0.00'){
			discount_amt = $('#discount_amt_input'+id).val(); 
			if(discount_amt == ''){
				discount_amt = '0.00';
			}
		}
		console.log('discount_amt_new'+discount_amt);
		var discount_amt1 =  qty * (price * discount1/100);
		console.log('discount_amt1'+discount_amt1);
		console.log('val'+val);
		if(val !=2 && discount1 =='0.00'){
			discount_amt1 = $('#discount_amt1_input'+id).val(); 
			if(discount_amt1 == ''){
				discount_amt1 = '0.00';
			}
		}
		//console.log('discount_amt1'+discount_amt1);
		var calculate_amount = qty * (parseFloat(price));
		var calculate_discount =  parseFloat(discount_amt1)+parseFloat(discount_amt);
		console.log('calculate_discount' + calculate_discount)
		var taxable_amount = parseFloat(calculate_amount) - parseFloat(calculate_discount);
		 CGST_amt = taxable_amount * CGST_per/100;
		 SGST_amt = taxable_amount * SGST_per/100;
		 CESS_amt = taxable_amount * CESS_per/100; 
		 IGST_amt = taxable_amount * IGST_per/100; 
			$('#discount_amt_input'+id).val(parseFloat(discount_amt).toFixed(2));
			$('#discount_amt1_input'+id).val(parseFloat(discount_amt1).toFixed(2));
			$('#CGST_amt_input'+id).val(parseFloat(CGST_amt).toFixed(2));
			$('#SGST_amt_input'+id).val(SGST_amt.toFixed(2));
			$('#CESS_amt_input'+id).val(CESS_amt.toFixed(2));
			$('#IGST_amt_input'+id).val(IGST_amt.toFixed(2));
		var other_charge = $('#other_charge_input'+id).val();
	
		if (other_charge == ''){
			other_charge = '0.00';
		}
		var mrp = $('#mrp_input'+id).val();
		console.log('mrp'+mrp);
		<?php if($model->getGSTTrue($poid) == true){?>
		var price_cgst = parseFloat(price) * CGST_per/100;
		var price_sgst = parseFloat(price) * SGST_per/100;
		var price_cess = parseFloat(price) * CESS_per/100;
		 var gst = parseFloat(price_cgst)+parseFloat(price_sgst) + parseFloat(price_cess);
		<?php }else{?>
		var price_cess = parseFloat(price) * CESS_per/100;
		var price_igst = parseFloat(price) * IGST_per/100;
		 var gst = parseFloat(price_igst) + parseFloat(price_cess);
		<?php }?>
		
		
		 var margin = (parseFloat(mrp) - (parseFloat(price)+ parseFloat(gst)))*100/(parseFloat(price)+ parseFloat(gst));
		 $('#margin_input'+id).val(margin.toFixed(2));
		 var calculated_amt = 0;
		 var total_discount_amt = 0;
		 var calculated_tax_amt = 0;
		 var calculated_gross = 0;
		 <?php if($model->getGSTTrue($poid) == true){?>
		var total_amount =  parseFloat(taxable_amount) + parseFloat(CGST_amt) +  parseFloat(SGST_amt) + parseFloat(CESS_amt);
       <?php }else{?>
       var total_amount =  parseFloat(taxable_amount) + parseFloat(IGST_amt) + parseFloat(CESS_amt);
       <?php }?>
		$('#total_amount_input'+id).val(total_amount.toFixed(2));
		
		
		 $(".total_amount_input").each(function() {
			
			 calculated_amt += parseFloat($(this).val());
			 console.log('val1'+$(this).val()); 
			   
		    });
		 $(".discount_amt_input").each(function() {
			 var str = $(this).attr('id');
			 console.log('str'+str); 
			 var arr = str.split('input');
			 var id = arr['1'];
			 total_discount_amt += parseFloat($(this).val())+parseFloat($('#discount_amt1_input'+id).val());
			 console.log('val2'+$(this).val()); 
			   
		    });
		 $(".CGST_amount_input").each(function() {
			 var str = $(this).attr('id');
			 console.log('str'+str); 
			 var arr = str.split('input');
			 var id = arr['1'];
			 var this_tax = parseFloat($('#CGST_amt_input'+id).val()) + parseFloat($('#SGST_amt_input'+id).val()) +
			 parseFloat($('#CESS_amt_input'+id).val()) ;
			 calculated_tax_amt += this_tax;
			 console.log('val3'+calculated_tax_amt); 
			   
		    });
		 $(".IGST_amount_input").each(function() {
			 var str = $(this).attr('id');
			 console.log('str'+str); 
			 var arr = str.split('input');
			 var id = arr['1'];
			 var this_tax = parseFloat($('#IGST_amt_input'+id).val())+
			 parseFloat($('#CESS_amt_input'+id).val()) ;
			 calculated_tax_amt += this_tax;
			 console.log('val3'+calculated_tax_amt); 
			   
		    });
			
		 $(".price_inpput").each(function() {
			 var str = $(this).attr('id');
			 var arr = str.split('input');
			 var id = arr['1'];
			 var is_free = $('#free_val'+id).val();
			 var this_price = parseFloat($('#price_input'+id).val()) * parseFloat($('#approve_input_qty'+id).val()) ;
			 if(is_free !='Yes'){
				 calculated_gross += this_price;
			 }
			  console.log('val4'+calculated_gross); 
			   
		    });

		   var bill_other_discount =  $('#bill_other_discount').val();
		   var net_bill_amount = parseNumber(parseFloat(calculated_amt) - parseFloat(bill_other_discount));
		   var table_cgst  = '0.00';
		    $('#gross_amount').val(calculated_gross.toFixed(2));
		    $('#total_discount').val(total_discount_amt.toFixed(2));
		    $('#tax_amount').val(calculated_tax_amt.toFixed(2));
		    $('#bill_amount').val(parseNumber(calculated_amt));
		    $('#net_bill_amount').val(net_bill_amount);
		    $('#before_net_bill_amount').val(net_bill_amount);
		    
		    var table_tax_id = $('#select_tax'+id).val();
		    //var table_cgst = $('#CGST_amt_input'+id).val();
		   // var table_sgst = $('#SGST_amt_input'+id).val();
		   // var table_cess = $('#CESS_amt_input'+id).val();
		   // var table_igst = $('#IGST_amt_input'+id).val();
		   
		 
		    checkTaxTable(table_tax_id,id);
		///var total_amount =  qty * (parseFloat(discounted_amount) + parseFloat(CGST_amt) + parseFloat(SGST_amt) + parseFloat(CESS_amt)+ parseFloat(other_charge));
		
		}
}
</script>
<script>


$('#addItem').click(function(){
	var poid = "<?php echo $poid;?>";
var req_qty = $('#PurchaseBillDetail_req_qty').val();
var change = $('#grid_changed').val();
if(change == 0){
	if(req_qty != ''){

 jQuery.ajax({
     'type': 'POST',
     'url': '<?php echo CController::createUrl('purchaseBillDetail/ajaxCreate') ?>/id/'+poid,
     data: $("#po-detail-add-form").serialize(),
     'success': function (data) {
    	 //alert('Data is added Successfully');
    	 location.reload();
    	/*  $.fn.yiiGridView.update('purchase-bill-detail-grid'); 
    	 $("#po-detail-add-form").trigger('reset');
    	 
    	 checkcompletecalc(); */
    	 //$('#po-detail-add-form').refresh();
         /* alert(data);
         location.reload(); */
    /*      $('#item_detail_data').html('');
         $('#item_detail_data').html(data); */
       
     },
     'cache': false
  }
  );
//console.log(form_values);
	} else {
		alert('Please add required quantity');
	}
}else{
	alert('Firstly Update the table values');
}
	
});
$('#PurchaseBillDetail_mrp').change(function(){
	var mrp = $('#PurchaseBillDetail_mrp').val();
	if(mrp != ''){
		$('#PurchaseBillDetail_sale_rate').val(mrp);
	}
	<?php if($user->role_id != $role->id){?>
	$('#PurchaseBillDetail_sale_rate').attr('readonly', true);
	<?php }?>
	
});
$('#PurchaseBillDetail_discount').change(function(){
	calculation(val=0);
	
});
$('#PurchaseBillDetail_price').change(function(){
	var price = $('#PurchaseBillDetail_price').val();
	var qty = $('#PurchaseBillDetail_approved_qty').val();
	var search = price.search( '/' );
	if(search != '-1' && qty != ''){
	var price = parseFloat(price)/parseFloat(qty);
	}
	$('#PurchaseBillDetail_price').val(parseFloat(price).toFixed(2));
	calculation();
	
});
$('#PurchaseBillDetail_approved_qty').change(function(){
	calculation();
	
});
$('#PurchaseBillDetail_other_charge').change(function(){
	calculation();
	
});
$('#PurchaseBillDetail_discount_amt').change(function(){
	calculation(val=1);
	
});
$('#PurchaseBillDetail_discount1').change(function(){
	calculation(val=2);
	
});
$('#PurchaseBillDetail_discount_amt1').change(function(){
	calculation(val=1);
	
});
$('#PurchaseBillDetail_is_free').change(function(){
	calculation();
	
});

function calculation(val = 1){
	
	var CGST_amt = '0.00';
	var SGST_amt = '0.00';
	var CESS_amt = '0.00';
	var IGST_amt = '0.00';
	var margin = '0.00';
	var price = $('#PurchaseBillDetail_price').val();
	if(price == 'undefined'){
		price = '0.00';
	}
	var discount = $('#PurchaseBillDetail_discount').val();
	var discount1 = $('#PurchaseBillDetail_discount1').val();
	 var CGST_per = $('#CGST_per').val();
    var  SGST_per = $('#SGST_per').val();
     var CESS_per = $('#CESS_per').val();
     var IGST_per = $('#IGST_per').val();
     var free = $('#PurchaseBillDetail_is_free').val();
     console.log(free+'free');
     if(free == 0){
     if(discount == '' || discount == 'NaN'){
         var discount = '0.00';
         var discount_amt = '0.00';
     }
     if(discount1 == ''|| discount1 == 'NaN'){
         var discount1 = '0.00';
         var discount_amt1 = '0.00';
     }
 	console.log('IGST_per'+IGST_per);
 	console.log('IGST_amt'+IGST_amt);
	if(price != '' &&  discount != ''){
		var qty = $('#PurchaseBillDetail_approved_qty').val();
		console.log('discount'+discount);
		if(discount != '0.00')
			var discount_amt =  qty * (price * discount/100);
	
		if(val != 0){
			 discount_amt =  $('#PurchaseBillDetail_discount_amt').val();
			 if(discount_amt == ''){
				  var discount_amt = '0.00';
			 }
		}
		
		console.log('discount_amt'+discount_amt);
		if(discount1 != '0.00')
			var discount_amt1 =  qty * (price * discount1/100);
		if(val != 2){
			 discount_amt1 =  $('#PurchaseBillDetail_discount_amt1').val();
			 if(discount_amt1 == ''){
				  var discount_amt1 = '0.00';
			 }
		}
		console.log('discount_amt1'+discount_amt1);
		var calculate_amount = qty * (parseFloat(price));
		console.log('qty' + qty);
		console.log('price' + price);
		console.log('calculate_amount' + calculate_amount);
		var calculate_discount =  parseFloat(discount_amt1)+parseFloat(discount_amt);
		console.log('calculate_discount' + calculate_discount);
		var taxable_amount = parseFloat(calculate_amount) - parseFloat(calculate_discount);
		console.log('taxable_amount' + taxable_amount)
		 CGST_amt = taxable_amount * CGST_per/100;
		 SGST_amt = taxable_amount * SGST_per/100;
		 CESS_amt = taxable_amount * CESS_per/100;
		 IGST_amt = taxable_amount * IGST_per/100;
			$('#PurchaseBillDetail_discount_amt').val(discount_amt);
			$('#PurchaseBillDetail_discount_amt1').val(discount_amt1);
			$('#CGST_amt').val(CGST_amt.toFixed(2));
			$('#SGST_amt').val(SGST_amt.toFixed(2));
			$('#CESS_amt').val(CESS_amt.toFixed(2));
			$('#IGST_amt').val(IGST_amt.toFixed(2));
		var other_charge = $('#PurchaseBillDetail_other_charge').val();
		
		if (other_charge == ''){
			other_charge = '0.00';
		}
		console.log(other_charge);
		var mrp = $('#PurchaseBillDetail_mrp').val();
		console.log('mrp'+mrp);
	<?php if($model->getGSTTrue($poid) == true){?>
			var price_cgst = parseFloat(price) * CGST_per/100;
			var price_sgst = parseFloat(price) * SGST_per/100;
			var price_cess = parseFloat(price) * CESS_per/100;
			 var gst = parseFloat(price_cgst)+parseFloat(price_sgst) + parseFloat(price_cess);
			<?php }else{?>
			var price_cess = parseFloat(price) * CESS_per/100;
			var price_igst = parseFloat(price) * IGST_per/100;
			 var gst = parseFloat(price_igst) + parseFloat(price_cess);
			<?php }?>
		
		
		 var margin = (parseFloat(mrp) - (parseFloat(price)+ parseFloat(gst)))*100/(parseFloat(price)+ parseFloat(gst));
		 $('#PurchaseBillDetail_margin').val(margin.toFixed(2));
		 
		 <?php if($model->getGSTTrue($poid) == true){?>
		var total_amount =  parseFloat(taxable_amount) + parseFloat(CGST_amt) + parseFloat(SGST_amt) + parseFloat(CESS_amt) ;
<?php }else{?>
var total_amount =  parseFloat(taxable_amount) + parseFloat(IGST_amt) + parseFloat(CESS_amt) ;

<?php }?>

		$('#PurchaseBillDetail_amount').val(total_amount.toFixed(2));
		}
     }else{
    	 $('#PurchaseBillDetail_discount').val('0.00');
    	 $('#PurchaseBillDetail_discount_amt').val('0.00');
    	 $('#PurchaseBillDetail_discount1').val('0.00');
    	 $('#PurchaseBillDetail_discount_amt1').val('0.00');
         $('#CGST_amt').val('0.00');
         $('#SGST_amt').val('0.00');
         $('#CESS_amt').val('0.00');
         $('#IGST_amt').val('0.00');
         $('#PurchaseBillDetail_amount').val('0.00');
         
         
     }
}
$('#grn-qty').change(function() {
	  $('#grid_changed').val('1');
	});
</script>