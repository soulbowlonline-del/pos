<?php
/**
 * Ported from protected/views/mrsDetail/admin.php.
 */

use app\components\Gx;
use app\components\Ui;
use app\models\Mrs;
use app\models\MrsDetail;
use app\models\Outlet;
use app\models\Tax;
use app\models\UserRole;
use app\models\Vendor;
use app\widgets\ActiveForm;
use app\widgets\Button;
use app\widgets\ButtonGroup;
use app\widgets\CheckboxColumn;
use app\widgets\GridView;
use app\widgets\TbTypeAhead;
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
<?php
$gst = true;
$user = Yii::$app->user->model;
if ($mrsid) {
	$mrs = Mrs::find()->where(['id' => $mrsid])->orderBy(['id' => SORT_DESC])->one();
	if ($mrs) {
		$outlet = Outlet::findOne($mrs->outlet_id);
		if ($outlet) {
			$vendor = Vendor::findOne($mrs->vendor_id);
			if ($vendor->state_id != $outlet->state_id) {
				$gst = false;
			}
		}
	}
} ?>
<section class="content-header">
	<h1> <?php echo 'Manage'; ?> <?php echo Html::encode($model->label(2)) ?> </h1>

	<?php /*?><a href="<?php echo Ui::to('mrs/admin');?>" class="btn btn-info export-btn" target="_blank">Merge</a>
*/ ?>
</section>
<section class="content">

	<div class="row">
		<div class="col-md-12 col-xs-12">
			<div class="box">
				<div class="box-header">
					<h3 class="box-title">MrsDetails</h3>
				</div>
				<div class="box-body">
					<div class="row">
						<!--  form code start here -->

						<div class="col-md-12">



							<?php $form = ActiveForm::begin([
								'id' => 'mrs-detail-form',
								'type' => 'horizontal',
								'enableAjaxValidation' => true,
								'action' => Ui::to('mrsDetail/admin', ['id' => $user->id]),
								'htmlOptions' => ['enctype' => 'multipart/form-data'],
							]);
							?>

							<div class="box-body">
								<?php echo $form->datepickerRow(
									$model,
									'mrs_req_date',
									[
										'hint' => 'Click inside! to select a date.',
										'prepend' => '<i class="icon-calendar"></i>',
										'options' => ['format' => 'yyyy-mm-dd']
									]
								); ?>
								<?php echo $form->dropdownListRow($model, 'outlet_id', Gx::listData(Outlet::find()->where(['status' => Outlet::STATUS_ACTIVE])->orderBy(['id' => SORT_DESC])->all()), ['class' => 'form-control']); ?>
								<?php
								if ($user->role_id != 6) { ?>
									<div class="form-group ">
										<label class="control-label col-md-3" for="MrsDetail_vendor_id">Vendor</label>
										<div class="col-md-9">
											<select class="form-control" name="MrsDetail[vendor_id]" id="MrsDetail_vendor_id" onchange="this.className=this.options[this.selectedIndex].className">
												<?php $vendors = $model->getMrsVendorOptions();
												?>

												<?php if ($vendors) {
													foreach ($vendors as $key => $vendor) {
														$query = Mrs::find();
        $query->orderBy(['id' => SORT_DESC]);
														$query->andWhere('vendor_id =' . $key);
														$query->andWhere('status =' . Mrs::STATUS_PENDING);
														$mrss = $query->all();
														$class = "form-control";
														if ($mrss) {
															foreach ($mrss as $mrs) {
																$current_date = date('Y-m-d');
																$create_time = date('Y-m-d', strtotime($mrs->mrs_req_date));
																$last_date = date('Y-m-d', (strtotime('+5 day', strtotime($create_time))));

																if ($current_date >= $last_date) {
																	$class = "redText form-control";
																}
															}
														}
														$selected = '';
														if ($vendor_id == $key) {
															$selected = 'selected';
														} ?>
														<option value="<?php echo $key; ?>" class="<?php echo $class; ?>" <?php echo $selected; ?>><?php echo $vendor; ?></option>
												<?php }
												} ?>
											</select>

										</div>
									</div>
									<?php //echo $form->dropdownListRow($model, 'vendor_id',$model->getMrsVendorOptions(),array('class'=>'form-control')); 
									?>
								<?php } ?>
								<div class="form-group">
									<label class="control-label col-md-3" for="">Mrs No</label>
									<div id="mrs_detail_data" class="col-md-9"></div>
								</div>
								<?php //echo $form->dropDownListRow($model, 'mrs_id', $model->getMrsOptions($user->id),array('class'=>'form-control')); 
								?>






							</div>
							<div class="form-actions box-footer">
								<?php echo Button::widget([
									'buttonType' => 'submit',
									'type' => 'primary',
									'label' => 'Search',
								]); ?>
							</div>

							<?php ActiveForm::end(); ?>
						</div>

						<div class="col-md-12">
							<?php

							$form = ActiveForm::begin([
								'id' => 'mrs-detail-add-form',
								'type' => 'horizontal',
								// 'action'=> Ui::to('mrsDetail/create',array('id'=>$mrsid)),
								'enableAjaxValidation' => true,
								'htmlOptions' => [
									'enctype' => 'multipart/form-data'
								]
							]);
							?>
							<div class="add-item">
								<div class="col-md-10 col-sm-12 col-xs-12 margin10">

									<div class="row">


										<div class="col-md-2 col-xs-12 padding2px">
											<!-- <label class="control-label" for="">Bar Code</label>
											<div id="item_detail_data"></div> -->
											<input type="hidden" name="MrsDetail[item_detail_id]" id="MrsDetail_item_detaill_id">
											<label class="control-label" for="">Bar Code</label>
											<?php echo $form->textField($model, 'bar_code', ['class' => 'form-control', 'id' => 'MrsDetail_bar_code']); ?>
										</div>
										<div class="col-md-3 col-xs-12 padding2px">

											<label class="control-label" for="">Item</label>

											<input type="hidden" name="MrsDetail[item_id]" id="item_hsn_code">

											<?php

											echo TbTypeAhead::widget([
												'model' => $model,
												'attribute' => 'item_val_id',
												'enableHogan' => true,
												'htmlOptions' => ['class' => 'form-control'],
												'options' => [
													[
														'limit' => 100,
														'attribute' => 'item_val_id',
														'valueKey' => 'name',
														'remote' => [
															'url' => Ui::to('/item/getAllItems') . '?vendor_id=' . $vendor_id . '&&term=%QUERY',
														],
														'template' => '<p>{{name}}     <strong> [ {{mrp}} ] </strong></p>',
														//     'template' => '<p>{{name}}<strong> [ {{username}} ] </strong> - {{user_id}}</p>',
														'engine' => new \yii\web\JsExpression('Hogan'),
													]
												],

												'events' => [
													'selected' => new \yii\web\JsExpression("function(obj, datum, name) {
                    var    uid = datum.item_id;
                           $('#item_hsn_code').val(uid);
                           		checkBarcodes();
          
         }")
												],
											]);


											?>
											<?php echo $form->error($model, 'item_id'); ?>
										</div>

										<?php /*?>
											<label class="control-label" for="">Item</label>
                  <?php echo $form->dropDownList($model, 'item_id',$model->getItemOptions($vendor_id),array('class'=>'form-control')); ?>
               */ ?>

										<div class="col-md-1 col-xs-12 padding2px">
											<label class="control-label" for="">Max Qty</label>
											<?php echo $form->textField($model, 'req_qty', ['class' => 'form-control']); ?>
										</div>

										<div class="col-md-1 col-xs-12 padding2px">
											<label class="control-label" for="">Appr Qty</label>
											<?php echo $form->textField($model, 'approved_qty', ['class' => 'form-control']); ?>
										</div>

										<div class="col-md-1 col-xs-12 padding2px">
											<label class="control-label" for="">Remarks</label>
											<?php echo $form->textArea($model, 'remarks',  ['class' => 'form-control', 'rows' => 1]); ?>
										</div>

										<div class="col-md-1 col-xs-12 padding2px">
											<label class="control-label" for="">Mrp</label>
											<?php echo $form->textField($model, 'mrp',  ['class' => 'form-control']); ?>
										</div>


										<div class="col-md-1 col-xs-12 padding2px">
											<label class="control-label" for="">Price</label>
											<?php echo $form->textField($model, 'price',  ['class' => 'form-control', 'id' => 'MrsDetail_price_field']); ?>
										</div>

										<div class="col-md-1 col-xs-12 padding2px">
											<label class="control-label" for="">Sale Rate</label>
											<?php echo $form->textField($model, 'sale_rate',  ['class' => 'form-control']); ?>
										</div>
										<div class="col-md-1 col-xs-12 padding2px">
											<label class="control-label" for="">Margin</label>
											<?php echo $form->textField($model, 'margin',  ['class' => 'form-control', 'rows' => 1]); ?>
										</div>


									</div>

									<div class="row">

										<div class="col-md-1 col-xs-12 padding2px">
											<label class="control-label" for="">Disc %</label>
											<?php echo $form->textField($model, 'discount',  ['class' => 'form-control']); ?>
										</div>
										<div class="col-md-1 col-xs-12 padding2px">
											<label class="control-label" for="">Disc Amt</label>
											<?php echo $form->textField($model, 'discount_amt',  ['class' => 'form-control']); ?>
										</div>


										<div class="col-md-1 col-xs-12 padding2px">
											<label class="control-label" for=""> Disc1 %</label>
											<?php echo $form->textField($model, 'discount1',  ['class' => 'form-control']); ?>
										</div>

										<div class="col-md-1 col-xs-12 padding2px">
											<label class="control-label" for=""> Disc1 Amt</label>
											<?php echo $form->textField($model, 'discount_amt1',  ['class' => 'form-control']); ?>
										</div>
										<div class="col-md-1 col-xs-12 padding2px">
											<label class="control-label" for="">Tax</label>
											<div id="item_tax_data"><?php echo $form->textField($model, 'tax_id',  ['class' => 'form-control']); ?></div>
											<?php echo $form->error($model, 'tax_id'); ?>

										</div>

										<?php if ($gst == true) { ?>
											<div class="col-md-1 col-xs-12 padding2px">
												<label class="control-label" for="">CGTS %</label> <input type="text" name="MrsDetail[cgst_per]" id="CGST_per" class="form-control" placeholder="%">
											</div>

											<div class="col-md-1 col-xs-12 padding2px">
												<label class="control-label" for="">CGTS Amt</label> <input type="text" name="MrsDetail[cgst_amt]" id="CGST_amt" class="form-control" placeholder="Amount">
											</div>


											<div class="col-md-1 col-xs-12 padding2px">
												<label class="control-label" for="">SGTS %</label> <input type="text" name="MrsDetail[sgst_per]" id="SGST_per" class="form-control" placeholder="%">
											</div>

											<div class="col-md-1 col-xs-12 padding2px">
												<label class="control-label" for="">SGTS Amt</label> <input type="text" name="MrsDetail[sgst_amt]" id="SGST_amt" class="form-control" placeholder="Amount">
											</div>

											<div class="col-md-1 col-xs-12 padding2px">
												<label class="control-label" for="">CESS %</label> <input type="text" name="MrsDetail[cess_per]" id="CESS_per" class="form-control" placeholder="%">
											</div>

											<div class="col-md-1 col-xs-12 padding2px">
												<label class="control-label" for="">CESS Amt</label> <input type="text" name="MrsDetail[cess_amt]" id="CESS_amt" class="form-control" placeholder="Amount">
											</div>
										<?php } else { ?>
											<div class="col-md-1 col-xs-12 padding2px">
												<label class="control-label" for="">CESS %</label> <input type="text" name="MrsDetail[cess_per]" id="CESS_per" class="form-control" placeholder="%">
											</div>

											<div class="col-md-1 col-xs-12 padding2px">
												<label class="control-label" for="">CESS Amt</label> <input type="text" name="MrsDetail[cess_amt]" id="CESS_amt" class="form-control" placeholder="Amount">
											</div>
											<div class="col-md-1 col-xs-12 padding2px">
												<label class="control-label" for="">IGST %</label> <input type="text" name="MrsDetail[igst_per]" id="IGST_per" class="form-control" placeholder="%">
											</div>

											<div class="col-md-1 col-xs-12 padding2px">
												<label class="control-label" for="">IGST Amt</label> <input type="text" name="MrsDetail[igst_amt]" id="IGST_amt" class="form-control" placeholder="Amount">
											</div>
										<?php } ?>
										<?php /*?>
										<div class="col-md-1 col-xs-12 padding2px">
											<label class="control-label" for="">Charge</label>
                 <?php  echo $form->hiddenField($model,'other_charge',  array('class'=>'form-control')); ?>
                </div>*/ ?>
										<?php echo $form->hiddenField($model, 'other_charge',  ['class' => 'form-control']); ?>
										<div class="col-md-1 col-xs-12 padding2px">
											<label class="control-label" for="">Amount</label>
											<?php echo $form->textField($model, 'amount',  ['class' => 'form-control']); ?>
										</div>



									</div>



								</div>

								<div class="col-md-2 col-sm-12 col-xs-12 add-bttn-box">

									<?php

									echo Button::widget([
											'buttonType' => 'button',
											'type' => 'primary',
											'label' => 'Add Item',
											// Yii 1's CWidget numbers its widgets yw0, yw1, ... across the whole
											// page, and this view's javascript addresses this button by the id it
											// happened to get. The port's widgets render no such id, so the click
											// handler bound to nothing and Add Item did nothing. Set explicitly
											// rather than by reproducing Yii 1's counter, which spans every widget
											// on the page - here yw0 is the List link and yw1 the Search button.
											'htmlOptions' => ['id' => 'yw3'],
										]
									);
									?>




								</div>

							</div>

							<?php ActiveForm::end(); ?>
						</div>
						<?php
						//if($mrsid != null){
						/* echo ButtonGroup::widget(array(
	'buttons'=>$this->context->menu,
	'type'=>'success',
	'htmlOptions'=>array('class'=> 'pull-right'),
)); 
} */
						?>

						<div class="clearfix"></div>
						<hr />

						<div class="col-md-12 item-list-table">
							<div class="table-responsive customsmallgridwidth">
								<div class="">

									<?php $form = ActiveForm::begin([
										'enableAjaxValidation' => true,
										'id' => 'mrs-qty',
									]); ?>

									<?php
									echo GridView::widget([
										'id' => 'menu-grid',
										'dataProvider' => $model->search(),
										'filter' => $model,
										'itemsCssClass' => 'table table-bordered table-striped dataTable',
										'rowCssClassExpression' => '$data->getCssClass()',
										'columns' => [
											// array(
											// 'id'=>'mrsId',
											// 'class' => CheckboxColumn::class,
											// 'selectableRows' => '50',   
											// ),
											[
												'header' => 'SN.',
												'value' => function ($data, $key, $index) { return ++$index; },
											],
											[
												'attribute' => 'item_id',
												'value' => function ($data, $key, $index) { return Gx::str($data->item); },
												//'filter'=>$model->getItemOptions($vendor_id),
												'filter' => false,
											],
											[
												'attribute' => 'item_detail_id',
												'value' => function ($data, $key, $index) { return Gx::str($data->itemDetail); },
												//	'filter'=>$model->getItemOptionbarcodes(),
												'filter' => false,
											],
											/*	 array(
					'attribute' =>'outlet_id',
					'value' => function ($data, $key, $index) { return Gx::str($data->outlet); },
					'filter'=>Gx::listData(Outlet::class),
			),
			array(
					'header'=>'vendor',
					'value' => function ($data, $key, $index) { return Gx::str($data->mrs->vendor); },
					
			), */
											[

												'attribute' => 'min_qty',

												'value' => function ($data, $key, $index) { return Html::activeTextInput($data,'min_qty', ["id"=>"min_input_qty$data->id","class"=>"min_input_qty","readOnly"=>true]); },
												'format' => 'raw',
												'htmlOptions' => ['class' => 'min_qty'],
												'filter' => false


											],
											[
												'header' => 'Ttl Rmn Qty',
												'value' => function ($data, $key, $index) { return isset($data->item)?$data->item->getTotalRemainingQuantity():""; },
												'htmlOptions' => ['class' => 'item_qty_field'],

											],
											/* array(
    				'attribute' =>'req_qty',
    		
    				'value' => function ($data, $key, $index) { return $data->req_qty; },
    				'filter'=>false,
    				
    		), */
											[

												'attribute' => 'req_qty',

												'value' => function ($data, $key, $index) { return Html::activeTextInput($data,'req_qty', ["id"=>"req_input_qty$data->id","class"=>"req_input_qty","readOnly"=>true]); },
												'format' => 'raw',
												'htmlOptions' => ['class' => 'req_qty'],
												'filter' => false


											],
											[

												'attribute' => 'ai_qty',

												'value' => function ($data, $key, $index) { return Html::activeTextInput($data,'ai_qty', ["id"=>"req_input_qty$data->id","class"=>"req_input_qty","readOnly"=>true]); },
												'format' => 'raw',
												'htmlOptions' => ['class' => 'ai_qty'],
												'filter' => false


											],


											/* array(
				'attribute' =>'req_qty',
				
				'value' => function ($data, $key, $index) { return $data->req_qty; },
				'filter'=>false,
				//	'filterInputOptions' =>array('style'=>'width: 20px'),
			), */
											[

												'header' => 'App Qty',

												'value' => function ($data, $key, $index) { return Html::activeTextInput($data,'approved_qty', ["id"=>"approve_input_qty$data->id","class"=>"approve_input_qty"]); },
												'format' => 'raw',
												'htmlOptions' => ['class' => 'approve_qty'],


											],
											[

												'header' => 'Mrp',
												'value' => function ($data, $key, $index) { return Html::activeTextInput($data,'mrp', ["id"=>"mrp_input$data->id","class"=>"mrp_input","readOnly"=>true]); },
												'format' => 'raw',
												'htmlOptions' => ['class' => 'mrp'],

											],
											[

												'header' => 'Sale Rate',
												'value' => function ($data, $key, $index) { return Html::activeTextInput($data,'sale_rate', ["id"=>"sale_rate$data->id","class"=>"sale_rate_input","disabled"=>$data->getCompanyBarcode($data->item_detail_id),"readOnly"=>true]); },
												'format' => 'raw',
												'htmlOptions' => ['class' => 'sale_rate'],

											],
											[
												'visible' => $user->role_id != 6,
												'header' => 'Adjust Type',
												'value' => function ($data, $key, $index) { return Html::dropDownList("MrsDetail[adjust_type_id]",$data->adjust_type_id,$data->getAdjustTypeOptions($data->adjust_type_id),["id"=>"adjust_type$data->id","class"=>"adjust_type","readOnly"=>true]); },
												'format' => 'raw',
												'htmlOptions' => [
													'class' => 'adjust_type'
												]
											],
											[
												'visible' => $user->role_id != 6,
												'attribute' => 'adjust_qty',

												'value' => function ($data, $key, $index) { return Html::activeTextInput($data,'adjust_qty', ["id"=>"adjust_qty$data->id","class"=>"adjust_qty","value"=>"0.000","readOnly"=>true]); },
												'format' => 'raw',
												'htmlOptions' => ['class' => 'adjust_qty'],
												'filter' => false


											],
											[

												'header' => 'Price',
												'value' => function ($data, $key, $index) { return Html::activeTextInput($data,'price', ["id"=>"price_input$data->id","class"=>"price_inpput","readOnly"=>true]); },
												'format' => 'raw',
												'htmlOptions' => ['class' => 'price'],

											],
											[

												'header' => 'Margin',
												'value' => function ($data, $key, $index) { return Html::activeTextInput($data,'margin', ["id"=>"margin_input$data->id","class"=>"margin_inpput","readOnly"=>true]); },
												'format' => 'raw',
												'htmlOptions' => ['class' => 'margin'],

											],

											[

												'header' => 'Amt',
												'value' => function ($data, $key, $index) { return Html::activeTextInput($data,'amount',["id"=>"total_amount_input$data->id","class"=>"total_amount_input","readOnly"=>true]); },
												'format' => 'raw',
												'htmlOptions' => ['class' => 'amount'],

											],
											[

												'header' => 'Dis%',
												'value' => function ($data, $key, $index) { return Html::activeTextInput($data,'discount',["id"=>"discount_input$data->id","class"=>"discount_input","readOnly"=>true]); },
												'format' => 'raw',
												'htmlOptions' => ['class' => 'discount'],

											],
											[

												'header' => 'Disc Amt',
												'value' => function ($data, $key, $index) { return Html::activeTextInput($data,'discount_amt',["id"=>"discount_amt_input$data->id","class"=>"discount_amt_input","readOnly"=>true]); },
												'format' => 'raw',
												'htmlOptions' => ['class' => 'discount_amt'],

											],
											[

												'header' => 'Other Disc%',
												'value' => function ($data, $key, $index) { return Html::activeTextInput($data,'discount1',["id"=>"discount1_input$data->id","class"=>"discount1_input","readOnly"=>true]); },
												'format' => 'raw',
												'htmlOptions' => ['class' => 'discount1'],

											],
											[

												'header' => 'Other Disc Amt',
												'value' => function ($data, $key, $index) { return Html::activeTextInput($data,'discount_amt1',["id"=>"discount_amt1_input$data->id","class"=>"discount_amt1_input","readOnly"=>true]); },
												'format' => 'raw',
												'htmlOptions' => ['class' => 'discount_amt1'],

											],
											[
												'header' => 'Tax',
												'value' => function ($data, $key, $index) { return Gx::str($data->tax); },
												'htmlOptions' => ['class' => 'select_tax_val'],
											],
											[
												'visible' => $model->getGSTTrue($mrsid) == true,
												'header' => 'CGST (%age)',
												'value' => function ($data, $key, $index) { return Html::activeTextInput($data,'cgst_per',["id"=>"CGST_per_input$data->id","class"=>"CGST_per_input","ReadOnly"=>"ReadOnly"]); },
												'format' => 'raw',
												'htmlOptions' => ['class' => 'CGST_per_input'],

											],
											[
												'visible' => $model->getGSTTrue($mrsid) == true,
												'header' => 'SGST (%age)',
												'value' => function ($data, $key, $index) { return Html::activeTextInput($data,'sgst_per',["id"=>"SGST_per_input$data->id","class"=>"SGST_per_input","ReadOnly"=>"ReadOnly"]); },
												'format' => 'raw',
												'htmlOptions' => ['class' => 'SGST_per_input'],

											],
											[
												'visible' => $model->getGSTTrue($mrsid) == true,
												'header' => 'CESS (%age)',
												'value' => function ($data, $key, $index) { return Html::activeTextInput($data,'cess_per',["id"=>"CESS_per_input$data->id","class"=>"CESS_per_input","ReadOnly"=>"ReadOnly"]); },
												'format' => 'raw',
												'htmlOptions' => ['class' => 'CESS_per_input'],

											],
											[
												'visible' => $model->getGSTTrue($mrsid) == true,
												'header' => 'CGST Amt',
												'value' => function ($data, $key, $index) { return Html::activeTextInput($data,'cgst_amt',["id"=>"CGST_amt_input$data->id","class"=>"CGST_amount_input","ReadOnly"=>"ReadOnly"]); },
												'format' => 'raw',
												'htmlOptions' => ['class' => 'CGST_amt_input'],

											],
											[
												'visible' => $model->getGSTTrue($mrsid) == true,
												'header' => 'SGST Amt',
												'value' => function ($data, $key, $index) { return Html::activeTextInput($data,'sgst_amt',["id"=>"SGST_amt_input$data->id","class"=>"SGST_amount_input","ReadOnly"=>"ReadOnly"]); },
												'format' => 'raw',
												'htmlOptions' => ['class' => 'SGST_amt_input'],

											],
											[
												'visible' => $model->getGSTTrue($mrsid) == true,
												'header' => 'CESS Amt',
												'value' => function ($data, $key, $index) { return Html::activeTextInput($data,'cess_amt',["id"=>"CESS_amt_input$data->id","class"=>"CESS_amount_input","ReadOnly"=>"ReadOnly"]); },
												'format' => 'raw',
												'htmlOptions' => ['class' => 'CESS_amt_input'],

											],
											[
												'visible' => $model->getGSTTrue($mrsid) == false,
												'header' => 'IGST (%age)',
												'value' => function ($data, $key, $index) { return Html::activeTextInput($data,'igst_per',["id"=>"IGST_per_input$data->id","class"=>"IGST_per_input","ReadOnly"=>"ReadOnly"]); },
												'format' => 'raw',
												'htmlOptions' => ['class' => 'CGST_per_input'],

											],
											[
												'visible' => $model->getGSTTrue($mrsid) == false,
												'header' => 'IGST Amt',
												'value' => function ($data, $key, $index) { return Html::activeTextInput($data,'igst_amt',["id"=>"IGST_amt_input$data->id","class"=>"IGST_amount_input","ReadOnly"=>"ReadOnly"]); },
												'format' => 'raw',
												'htmlOptions' => ['class' => 'IGST_amt_input'],

											],
											/* array(
    					
    				'header'=>'Other Charge',
    				'value' => function ($data, $key, $index) { return Html::activeTextInput($data,'other_charge',["id"=>"other_charge_input$data->id","class"=>"other_charge_input"]); },
    				'format' => 'raw',
    				'htmlOptions'=>array('class'=>'other_charge'),
    					
    		), */



										],
									]); ?>


									<script>
										// function reloadGrid(data) {
										// $.fn.yiiGridView.update('menu-grid');
										// }
										$(document).ready(function() {
											//$('#mrs-qty input').addClass('approved_qty');
											//var mrsid = ("#MrsDetail_mrs_id").val();
											var mrsid = <?php echo $mrsid; ?>;

											$("td.approve_qty input:text").each(function(key, value) {
												var val = $(this).val();
												var str = $(this).attr('id');
												var approved_qty = str.split('qty');

												var checkid = approved_qty['1'];
												/// console.log(checkid);
												gridcalculation(checkid);

											});


											$("#approve").click(function(event) {
												if (confirm("Do you really want to save?") == true) {

													event.preventDefault();
													var formData = {};
													var maxData = {};
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
													var adjust_qty = {};
													var adjust_type = {};
													var cgstData = {};
													var sgstData = {};
													var cessData = {};
													var igstData = {};
													var cgstamtData = {};
													var sgstamtData = {};
													var cessamtData = {};
													var igstamtData = {};
													var marginData = {};
													var status = 1;
													var gross_amt = $('#gross_amount').val();
													var total_discount = $('#total_discount').val();
													var tax_amount = $('#tax_amount').val();
													var bill_amount = $('#bill_amount').val();

													$("td.approve_qty input:text").each(function(key, value) {
														var val = $(this).val();
														var str = $(this).attr('id');
														var approved_qty = str.split('qty');

														formData[approved_qty['1']] = val;

													});
													$("td.req_qty input:text").each(function(key, value) {
														var val = $(this).val();
														var str = $(this).attr('id');
														var req_qty = str.split('qty');

														maxData[req_qty['1']] = val;
													});
													$("td.adjust_qty input:text").each(function(key, value) {
														var val = $(this).val();
														var str = $(this).attr('id');
														var adjust_qty_val = str.split('qty');

														adjust_qty[adjust_qty_val['1']] = val;

													});
													$("td.adjust_type select").each(function(key, value) {
														var str = $(this).attr('id');
														console.log('str' + str);
														var arr = str.split('type');
														var vat = arr['1'];
														var val = $('#' + str).val();
														adjust_type[vat] = val;
														console.log('adjust_type' + adjust_type);
													});


													$("td.mrp input:text").each(function(key, value) {
														var val = $(this).val();
														var str = $(this).attr('id');
														var mrp = str.split('input');
														mrpData[mrp['1']] = val;
													});
													$("td.price input:text").each(function(key, value) {
														var val = $(this).val();
														var str = $(this).attr('id');
														var arr = str.split('input');
														var price = arr['1'];
														priceData[price] = val;
													});
													$("td.sale_rate input:text").each(function(key, value) {
														var val = $(this).val();
														var str = $(this).attr('id');
														var salerate = str.split('rate');
														salerateData[salerate['1']] = val;
													});
													$("td.discount input:text").each(function(key, value) {
														var val = $(this).val();
														var str = $(this).attr('id');
														var arr = str.split('input');
														var discount = arr['1'];
														discountData[discount] = val;
													});
													$("td.discount_amt input:text").each(function(key, value) {
														var val = $(this).val();
														var str = $(this).attr('id');
														var arr = str.split('input');
														var discount_amt = arr['1'];

														discount_amtData[discount_amt] = val;
													});
													$("td.discount1 input:text").each(function(key, value) {
														var val = $(this).val();
														var str = $(this).attr('id');
														var arr = str.split('input');
														var discount = arr['1'];
														discountData1[discount] = val;
													});
													$("td.discount_amt1 input:text").each(function(key, value) {
														var val = $(this).val();
														var str = $(this).attr('id');
														var arr = str.split('input');
														var discount_amt = arr['1'];

														discount_amtData1[discount_amt] = val;
													});
													$("td.CGST_per_input input:text").each(function(key, value) {
														var val = $(this).val();
														var str = $(this).attr('id');
														var arr = str.split('input');
														var vat = arr['1'];

														cgstData[vat] = val;
													});
													$("td.SGST_per_input input:text").each(function(key, value) {
														var val = $(this).val();
														var str = $(this).attr('id');
														var arr = str.split('input');
														var vat = arr['1'];

														sgstData[vat] = val;
													});
													$("td.CESS_per_input input:text").each(function(key, value) {
														var val = $(this).val();
														var str = $(this).attr('id');
														var arr = str.split('input');
														var vat = arr['1'];

														cessData[vat] = val;
													});
													$("td.CGST_amt_input input:text").each(function(key, value) {
														var val = $(this).val();
														var str = $(this).attr('id');
														var arr = str.split('input');
														var vat = arr['1'];

														cgstamtData[vat] = val;
													});
													$("td.SGST_amt_input input:text").each(function(key, value) {
														var val = $(this).val();
														var str = $(this).attr('id');
														var arr = str.split('input');
														var vat = arr['1'];

														sgstamtData[vat] = val;
													});
													$("td.CESS_amt_input input:text").each(function(key, value) {
														var val = $(this).val();
														var str = $(this).attr('id');
														var arr = str.split('input');
														var vat = arr['1'];

														cessamtData[vat] = val;
													});
													$("td.other_charge input:text").each(function(key, value) {
														var val = $(this).val();
														var str = $(this).attr('id');
														var arr = str.split('input');
														var other_charge = arr['1'];
														other_chargeData[other_charge] = val;
													});
													$("td.amount input:text").each(function(key, value) {
														var val = $(this).val();
														var str = $(this).attr('id');
														var arr = str.split('input');
														var amount = arr['1'];

														amountData[amount] = val;
													});

													$("td.IGST_per_input input:text").each(function(key, value) {
														var val = $(this).val();
														var str = $(this).attr('id');
														var arr = str.split('input');
														var vat = arr['1'];

														igstData[vat] = val;
													});
													$("td.IGST_amt_input input:text").each(function(key, value) {
														var val = $(this).val();
														var str = $(this).attr('id');
														var arr = str.split('input');
														var vat = arr['1'];

														igstamtData[vat] = val;
													});
													$("td.margin_input input:text").each(function(key, value) {
														var val = $(this).val();
														var str = $(this).attr('id');
														var arr = str.split('input');
														var vat = arr['1'];

														marginData[vat] = val;
													});
													$.ajax({
														url: '<?php echo Ui::to('mrsDetail/ajaxupdate'); ?>/' + mrsid,
														type: 'post',

														data: {
															qty: formData,
															adjust_qty: adjust_qty,
															maxData: maxData,
															adjust_type: adjust_type,
															mrp: mrpData,
															salerate: salerateData,
															discount: discountData,
															discount_amt: discount_amtData,
															discount1: discountData1,
															discount_amt1: discount_amtData1,
															cgstData: cgstData,
															sgstData: sgstData,
															cessData: cessData,
															cgstamtData: cgstamtData,
															sgstamtData: sgstamtData,
															cessamtData: cessamtData,
															igstData: igstData,
															igstamtData: igstamtData,
															price: priceData,
															vat: vatData,
															other_charge: other_chargeData,
															amount: amountData,
															status: status,
															gross_amt: gross_amt,
															total_discount: total_discount,
															tax_amount: tax_amount,
															bill_amount: bill_amount,
															marginData: marginData


														},
														success: function(data) {
															location.reload();
															/*  $.fn.yiiGridView.update('menu-grid');
																$('#gross_amount').val('');
																$('#total_discount').val('');
																$('#tax_amount').val('');
																$('#bill_amount').val(''); */
														}

													});
												}
											});

											$("#reject").click(function(event) {
												event.preventDefault();
												var formData = {};
												var maxData = {};
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
												var adjust_qty = {};
												var adjust_type = {};
												var cessData = {};
												var igstData = {};
												var cgstamtData = {};
												var sgstamtData = {};
												var cessamtData = {};
												var igstamtData = {};
												var marginData = {};
												var status = 2;
												var gross_amt = $('#gross_amount').val();
												var total_discount = $('#total_discount').val();
												var tax_amount = $('#tax_amount').val();
												var bill_amount = $('#bill_amount').val();
												$("td.approve_qty input:text").each(function(key, value) {
													var val = $(this).val();
													var str = $(this).attr('id');
													var approved_qty = str.split('qty');

													formData[approved_qty['1']] = val;
												});
												$("td.req_qty input:text").each(function(key, value) {
													var val = $(this).val();
													var str = $(this).attr('id');
													var req_qty = str.split('qty');

													maxData[req_qty['1']] = val;
												});

												$("td.mrp input:text").each(function(key, value) {
													var val = $(this).val();
													var str = $(this).attr('id');
													var mrp = str.split('input');
													mrpData[mrp['1']] = val;
												});
												$("td.adjust_qty input:text").each(function(key, value) {
													var val = $(this).val();
													var str = $(this).attr('id');
													var adjust_qty_val = str.split('qty');

													adjust_qty[adjust_qty_val['1']] = val;

												});
												$("td.adjust_type select").each(function(key, value) {
													var str = $(this).attr('id');
													console.log('str' + str);
													var arr = str.split('type');
													var vat = arr['1'];
													var val = $('#' + str).val();
													adjust_type[vat] = val;
													console.log('adjust_type' + adjust_type);
												});
												$("td.price input:text").each(function(key, value) {
													var val = $(this).val();
													var str = $(this).attr('id');
													var arr = str.split('input');
													var price = arr['1'];
													priceData[price] = val;
												});
												$("td.sale_rate input:text").each(function(key, value) {
													var val = $(this).val();
													var str = $(this).attr('id');
													var salerate = str.split('rate');
													salerateData[salerate['1']] = val;
												});
												$("td.discount input:text").each(function(key, value) {
													var val = $(this).val();
													var str = $(this).attr('id');
													var arr = str.split('input');
													var discount = arr['1'];
													discountData[discount] = val;
												});
												$("td.discount_amt input:text").each(function(key, value) {
													var val = $(this).val();
													var str = $(this).attr('id');
													var arr = str.split('input');
													var discount_amt = arr['1'];

													discount_amtData[discount_amt] = val;
												});
												$("td.discount1 input:text").each(function(key, value) {
													var val = $(this).val();
													var str = $(this).attr('id');
													var arr = str.split('input');
													var discount = arr['1'];
													discountData1[discount] = val;
												});
												$("td.discount_amt1 input:text").each(function(key, value) {
													var val = $(this).val();
													var str = $(this).attr('id');
													var arr = str.split('input');
													var discount_amt = arr['1'];

													discount_amtData1[discount_amt] = val;
												});
												$("td.CGST_per_input input:text").each(function(key, value) {
													var val = $(this).val();
													var str = $(this).attr('id');
													var arr = str.split('input');
													var vat = arr['1'];

													cgstData[vat] = val;
												});
												$("td.SGST_per_input input:text").each(function(key, value) {
													var val = $(this).val();
													var str = $(this).attr('id');
													var arr = str.split('input');
													var vat = arr['1'];

													sgstData[vat] = val;
												});
												$("td.CESS_per_input input:text").each(function(key, value) {
													var val = $(this).val();
													var str = $(this).attr('id');
													var arr = str.split('input');
													var vat = arr['1'];

													cessData[vat] = val;
												});
												$("td.CGST_amt_input input:text").each(function(key, value) {
													var val = $(this).val();
													var str = $(this).attr('id');
													var arr = str.split('input');
													var vat = arr['1'];

													cgstamtData[vat] = val;
												});
												$("td.SGST_amt_input input:text").each(function(key, value) {
													var val = $(this).val();
													var str = $(this).attr('id');
													var arr = str.split('input');
													var vat = arr['1'];

													sgstamtData[vat] = val;
												});
												$("td.CESS_amt_input input:text").each(function(key, value) {
													var val = $(this).val();
													var str = $(this).attr('id');
													var arr = str.split('input');
													var vat = arr['1'];

													cessamtData[vat] = val;
												});
												$("td.IGST_per_input input:text").each(function(key, value) {
													var val = $(this).val();
													var str = $(this).attr('id');
													var arr = str.split('input');
													var vat = arr['1'];

													igstData[vat] = val;
												});
												$("td.IGST_amt_input input:text").each(function(key, value) {
													var val = $(this).val();
													var str = $(this).attr('id');
													var arr = str.split('input');
													var vat = arr['1'];

													igstamtData[vat] = val;
												});
												$("td.other_charge input:text").each(function(key, value) {
													var val = $(this).val();
													var str = $(this).attr('id');
													var arr = str.split('input');
													var other_charge = arr['1'];
													other_chargeData[other_charge] = val;
												});
												$("td.amount input:text").each(function(key, value) {
													var val = $(this).val();
													var str = $(this).attr('id');
													var arr = str.split('input');
													var amount = arr['1'];

													amountData[amount] = val;
												});

												$("td.margin_input input:text").each(function(key, value) {
													var val = $(this).val();
													var str = $(this).attr('id');
													var arr = str.split('input');
													var vat = arr['1'];

													marginData[vat] = val;
												});

												$.ajax({
													url: '<?php echo Ui::to('mrsDetail/ajaxupdate'); ?>/' + mrsid,
													type: 'post',

													data: {
														qty: formData,
														maxData: maxData,
														mrp: mrpData,
														salerate: salerateData,
														discount: discountData,
														discount_amt: discount_amtData,
														discount1: discountData1,
														discount_amt1: discount_amtData1,
														cgstData: cgstData,
														sgstData: sgstData,
														cessData: cessData,
														adjust_qty: adjust_qty,
														adjust_type: adjust_type,
														cgstamtData: cgstamtData,
														sgstamtData: sgstamtData,
														cessamtData: cessamtData,
														igstData: igstData,
														igstamtData: igstamtData,
														price: priceData,
														vat: vatData,
														other_charge: other_chargeData,
														amount: amountData,
														status: status,
														gross_amt: gross_amt,
														total_discount: total_discount,
														tax_amount: tax_amount,
														bill_amount: bill_amount,
														marginData: marginData
													},
													success: function(data) {
														location.reload();
														/*  $.fn.yiiGridView.update('menu-grid');
		    	  $('#gross_amount').val('');
				    $('#total_discount').val('');
				    $('#tax_amount').val('');
				    $('#bill_amount').val(''); */
													}

												});
											});
										})
									</script>
									<button class="btn btn-primary" id="approve" type="submit" name="approve">Save</button>
									<button class="btn btn-primary" id="reject" type="submit" name="reject">Reject</button>
									<?php //echo CHtml::ajaxSubmitButton('Activate',array('mrsDetail/ajaxupdate','act'=>'Insert'), array('success'=>'reloadGrid')); 
									?>

									<?php ActiveForm::end(); ?>
								</div>
							</div>
							<div class="clearfix"></div>
							<div class="row">
								<?php /*?>
<div class="col-md-7  tax-table-responsive table-responsive customsmallgridwidth">
<?php $taxes = Tax::find()->where(array('status'=>MrsDetail::STATUS_PENDING))->all();?>
<?php if($taxes){?>
<table>
<tr>
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
<?php foreach($taxes as $tax){?>

<tr>
<td><?php echo $tax->title;?></td>
<td><?php echo $tax->hrn_code;?></td>
<td><?php echo $tax->tax_val1;?></td>
<td>CGST Amount</td>
<td><?php echo $tax->tax_val2;?></td>
<td>SGST Amount</td>
<td><?php echo $tax->tax_val3;?></td>
<td>CESS Amount</td>
<td><?php echo $tax->tax_val4;?></td>
<td>IGST Amount</td>
</tr>

<?php }?>
</table>
<?php }?>
</div>*/ ?>
								<div class="col-md-5  col-md-offset-7">
									<div class="total-bill">

										<div class="form-group">
											<label class="col-sm-4 control-label text-right">Gross Amount </label>
											<div class="col-sm-8"><input type="text" class="form-control" readonly="readonly" id="gross_amount"></div>
											<div class="clearfix"></div>
										</div>
										<div class="form-group">
											<label class="col-sm-4 control-label text-right">Total Discount </label>
											<div class="col-sm-8"><input type="text" class="form-control" readonly="readonly" id="total_discount">
											</div>
											<div class="clearfix"></div>
										</div>
										<div class="form-group">
											<label class="col-sm-4 control-label text-right">Tax Amount</label>
											<div class="col-sm-8"> <input type="text" class="form-control" readonly="readonly" id="tax_amount">
											</div>
											<div class="clearfix"></div>
										</div>
										<div class="form-group">
											<label class="col-sm-4 control-label text-right">Bill Amount </label>
											<div class="col-sm-8"><input type="text" class="form-control" readonly="readonly" id="bill_amount">
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
	</div>
</section>
<?php $user = Yii::$app->user->model;
$role = UserRole::find()->where(['title' => 'Admin'])->orderBy(['id' => SORT_DESC])->one(); ?>
<script>
	$('#MrsDetail_bar_code').change(function() {
		checkTaxes();
	});
	$('form input').keydown(function(e) {
		if (e.keyCode == 13) {
			e.preventDefault();
			return false;
		}
	});
	$('.mrp_input').change(function() {
		var mrp = $(this).val();
		var str = $(this).attr('id');
		var arr = str.split('input');

		if (mrp != '') {
			$('#sale_rate' + arr['1']).val(mrp);
		}
		<?php if ($user->role_id != $role->id) { ?>
			$('#sale_rate' + arr['1']).attr('readonly', true);
		<?php } ?>

	});
	$('.approve_input_qty').change(function() {

		console.log('asxasdasxas');
		var mrp = $(this).val();


		var str = $(this).attr('id');



		var arr = str.split('qty');
		var id = arr['1'];


		var approve_qty = $('#approve_input_qty' + id).val();
		var max_qty = $('#req_input_qty' + id).val();
		console.log('approve_qty' + approve_qty);
		console.log('max_qty' + max_qty);
		/* if(parseFloat(max_qty) < parseFloat(approve_qty)){
			
			 alert('Maximum Quantity can not be less than Approve Quantity');
			 $('#approve_input_qty'+id).val('0.000');
		 }else{
			 gridcalculation(id);
		 }*/
		gridcalculation(id);

	});
	$('.mrp_input').change(function() {
		var mrp = $(this).val();
		var str = $(this).attr('id');
		var arr = str.split('input');
		var id = arr['1'];
		gridcalculation(id);


	});
	$('.price_inpput').change(function() {


		var mrp = $(this).val();
		var price = mrp;
		var str = $(this).attr('id');
		var arr = str.split('input');
		var id = arr['1'];
		var qty = $('#approve_input_qty' + id).val();
		var search = mrp.search('/');
		if (search != '-1') {
			var price = parseFloat(mrp) / parseFloat(qty);
		}
		$(this).val(parseFloat(price).toFixed(2));

		gridcalculation(id);


	});
	$('.discount_input').change(function() {
		var mrp = $(this).val();
		var str = $(this).attr('id');
		var arr = str.split('input');
		var id = arr['1'];
		gridcalculation(id, val = 0);


	});

	$('.other_charge_input').change(function() {
		var mrp = $(this).val();
		var str = $(this).attr('id');
		var arr = str.split('input');
		var id = arr['1'];
		gridcalculation(id);


	});
	$('.discount_amt_input').change(function() {
		var mrp = $(this).val();
		var str = $(this).attr('id');
		var arr = str.split('input');
		var id = arr['1'];
		gridcalculation(id, val = 1);


	});
	$('.discount_amt1_input').change(function() {
		var mrp = $(this).val();
		var str = $(this).attr('id');
		var arr = str.split('input');
		var id = arr['1'];
		gridcalculation(id, val = 1);


	});
	$('.discount1_input').change(function() {
		var mrp = $(this).val();
		var str = $(this).attr('id');
		var arr = str.split('input');
		var id = arr['1'];
		gridcalculation(id, val = 2);


	});

	function gridcalculation(id, val = 1) {
		var CGST_amt = '0.00';
		var SGST_amt = '0.00';
		var CESS_amt = '0.00';
		var IGST_amt = '0.00';
		var CGST_per = '0.00';
		var SGST_per = '0.00';
		var CESS_per = '0.00';
		var IGST_per = '0.00';
		var margin = '0.00';
		var price = $('#price_input' + id).val();
		var discount = $('#discount_input' + id).val();
		var discount1 = $('#discount1_input' + id).val();
		var CGST_per = $('#CGST_per_input' + id).val();
		var SGST_per = $('#SGST_per_input' + id).val();
		var CESS_per = $('#CESS_per_input' + id).val();
		var IGST_per = $('#IGST_per_input' + id).val();
		if (discount == '') {
			discount = '0.00';
		}
		if (discount1 == '') {
			discount1 = '0.00';
		}

		if (price != '' && discount != '') {
			var qty = $('#approve_input_qty' + id).val();
			var discount_amt = qty * (price * discount / 100);
			if (val != 0 && discount == '0.00') {
				discount_amt = $('#discount_amt_input' + id).val();
				if (discount_amt == '') {
					discount_amt = '0.00';
				}
			}
			var discount_amt1 = qty * (price * discount1 / 100);
			if (val != 2 && discount1 == '0.00') {
				discount_amt1 = $('#discount_amt1_input' + id).val();
				if (discount_amt1 == '') {
					discount_amt1 = '0.00';
				}
			}

			var calculate_amount = qty * (parseFloat(price));
			console.log('calculate_amount' + calculate_amount)
			var calculate_discount = parseFloat(discount_amt1) + parseFloat(discount_amt);
			console.log('calculate_discount' + calculate_discount)
			var taxable_amount = parseFloat(calculate_amount) - parseFloat(calculate_discount);
			console.log('taxable_amount' + taxable_amount)
			CGST_amt = taxable_amount * CGST_per / 100;
			console.log('CGST_amt' + CGST_amt)
			SGST_amt = taxable_amount * SGST_per / 100;
			CESS_amt = taxable_amount * CESS_per / 100;
			IGST_amt = taxable_amount * IGST_per / 100;
			$('#discount_amt_input' + id).val(parseFloat(discount_amt).toFixed(2));
			$('#discount_amt1_input' + id).val(parseFloat(discount_amt1).toFixed(2));
			$('#CGST_amt_input' + id).val(parseFloat(CGST_amt).toFixed(2));
			$('#SGST_amt_input' + id).val(SGST_amt.toFixed(2));
			$('#CESS_amt_input' + id).val(CESS_amt.toFixed(2));
			$('#IGST_amt_input' + id).val(IGST_amt.toFixed(2));
			var other_charge = $('#other_charge_input' + id).val();

			if (other_charge == '') {
				other_charge = '0.00';
			}
			var mrp = $('#mrp_input' + id).val();
			console.log('mrp' + mrp);
			<?php if ($model->getGSTTrue($mrsid) == true) { ?>
				var price_cgst = parseFloat(price) * CGST_per / 100;
				var price_sgst = parseFloat(price) * SGST_per / 100;
				var price_cess = parseFloat(price) * CESS_per / 100;
				var gst = parseFloat(price_cgst) + parseFloat(price_sgst) + parseFloat(price_cess);
			<?php } else { ?>
				var price_igst = parseFloat(price) * IGST_per / 100;
				var gst = parseFloat(price_igst);
			<?php } ?>


			var margin = (parseFloat(mrp) - (parseFloat(price) + parseFloat(gst))) * 100 / (parseFloat(price) + parseFloat(gst));
			$('#margin_input' + id).val(margin.toFixed(2));
			var calculated_amt = 0;
			var total_discount_amt = 0;
			var calculated_tax_amt = 0;
			var calculated_gross = 0;
			<?php if ($model->getGSTTrue($mrsid) == true) { ?>
				var total_amount = parseFloat(taxable_amount) + parseFloat(CGST_amt) + parseFloat(SGST_amt) + parseFloat(CESS_amt);
			<?php } else { ?>
				var total_amount = parseFloat(taxable_amount) + parseFloat(IGST_amt);
			<?php } ?>
			$('#total_amount_input' + id).val(total_amount.toFixed(2));


			$(".total_amount_input").each(function() {

				calculated_amt += parseFloat($(this).val());
				console.log('val1' + $(this).val());

			});
			$(".discount_amt_input").each(function() {
				var str = $(this).attr('id');
				console.log('str' + str);
				var arr = str.split('input');
				var id = arr['1'];
				total_discount_amt += parseFloat($(this).val()) + parseFloat($('#discount_amt1_input' + id).val());
				console.log('val2' + $(this).val());

			});
			$(".CGST_amount_input").each(function() {
				var str = $(this).attr('id');
				console.log('str' + str);
				var arr = str.split('input');
				var id = arr['1'];
				var this_tax = parseFloat($('#CGST_amt_input' + id).val()) + parseFloat($('#SGST_amt_input' + id).val()) +
					parseFloat($('#CESS_amt_input' + id).val());
				calculated_tax_amt += this_tax;
				console.log('val3' + calculated_tax_amt);

			});
			$(".IGST_amount_input").each(function() {
				var str = $(this).attr('id');
				console.log('str' + str);
				var arr = str.split('input');
				var id = arr['1'];
				var this_tax = parseFloat($('#IGST_amt_input' + id).val());
				calculated_tax_amt += this_tax;
				console.log('val3' + calculated_tax_amt);

			});
			$(".price_inpput").each(function() {
				var str = $(this).attr('id');
				var arr = str.split('input');
				var id = arr['1'];
				var this_price = parseFloat($('#price_input' + id).val()) * parseFloat($('#approve_input_qty' + id).val());
				calculated_gross += this_price;
				console.log('val4' + calculated_gross);

			});
			$('#gross_amount').val(calculated_gross.toFixed(2));
			$('#total_discount').val(total_discount_amt.toFixed(2));
			$('#tax_amount').val(calculated_tax_amt.toFixed(2));
			$('#bill_amount').val(calculated_amt.toFixed());

			///var total_amount =  qty * (parseFloat(discounted_amount) + parseFloat(CGST_amt) + parseFloat(SGST_amt) + parseFloat(CESS_amt)+ parseFloat(other_charge));

		}
	}
	$(document).ready(function() {

		checkBarcodes();
		checkcompletecalc();
		<?php if ($vendor_id != null) { ?>
			var select_vendor = "<?php echo $vendor_id; ?>";
			$('#MrsDetail_vendor_id').val(select_vendor);
		<?php } ?>
		<?php if ($outlet_id != null) { ?>
			var select_outlet = "<?php echo $outlet_id; ?>";
			$('#MrsDetail_outlet_id').val(select_outlet);
		<?php } ?>
		<?php if ($start_date != null) { ?>
			var start_date = "<?php echo $start_date; ?>";
			$('#MrsDetail_mrs_req_date').val(start_date);
		<?php } ?>
		var vendor_id = $('#MrsDetail_vendor_id').val();
		checkMrsNo(vendor_id);

		$('#item_hsn_code').change(function() {
			checkBarcodes();

		});


	});

	function checkcompletecalc() {
		var calculated_amt = 0;
		var total_discount_amt = 0;
		var calculated_tax_amt = 0;
		var calculated_gross = 0;
		$(".total_amount_input").each(function() {

			calculated_amt += parseFloat($(this).val());
			console.log('val1' + $(this).val());

		});
		$(".discount_amt_input").each(function() {
			var str = $(this).attr('id');
			console.log('str' + str);
			var arr = str.split('input');
			var id = arr['1'];
			total_discount_amt += parseFloat($(this).val()) + parseFloat($('#discount_amt1_input' + id).val());
			console.log('val2' + $(this).val());

		});
		$(".CGST_amount_input").each(function() {
			var str = $(this).attr('id');
			console.log('str' + str);
			var arr = str.split('input');
			var id = arr['1'];
			var this_tax = parseFloat($('#CGST_amt_input' + id).val()) + parseFloat($('#SGST_amt_input' + id).val()) +
				parseFloat($('#CESS_amt_input' + id).val());
			calculated_tax_amt += this_tax;
			console.log('val3' + calculated_tax_amt);

		});
		$(".IGST_amount_input").each(function() {
			var str = $(this).attr('id');
			console.log('str' + str);
			var arr = str.split('input');
			var id = arr['1'];
			var this_tax = parseFloat($('#IGST_amt_input' + id).val());
			calculated_tax_amt += this_tax;
			console.log('val3' + calculated_tax_amt);

		});
		$(".price_inpput").each(function() {
			var str = $(this).attr('id');
			var arr = str.split('input');
			var id = arr['1'];
			var this_price = parseFloat($('#price_input' + id).val()) * parseFloat($('#approve_input_qty' + id).val());
			calculated_gross += this_price;
			console.log('val4' + calculated_gross);

		});
		$('#gross_amount').val(calculated_gross.toFixed(2));
		$('#total_discount').val(total_discount_amt.toFixed(2));
		$('#tax_amount').val(calculated_tax_amt.toFixed(2));
		$('#bill_amount').val(calculated_amt.toFixed());

	}
	$('#MrsDetail_vendor_id').change(function() {
		var vendor_id = $('#MrsDetail_vendor_id').val();
		checkMrsNo(vendor_id);

	});

	function checkMrsNo(vendor_id) {
		var mrsid = <?php echo $mrsid; ?>;
		jQuery.ajax({
			'type': 'POST',
			'url': '<?php echo Ui::to('mrsDetail/ajaxMrsNo') ?>',
			'data': {
				'vendor_id': vendor_id
			},
			'success': function(data) {
				$('#mrs_detail_data').html('');
				$('#mrs_detail_data').html(data);
				$('#MrsDetail_mrs_id').val(mrsid);
			},
			'cache': false
		});
	}

	function checkBarcodes() {
		var item_id = $('#item_hsn_code').val();
		jQuery.ajax({
			'type': 'POST',
			'url': '<?php echo Ui::to('mrsDetail/ajaxItems') ?>',
			'data': {
				'item_id': item_id
			},
			'success': function(data) {
				$('#MrsDetail_bar_code').val('');
				$('#MrsDetail_bar_code').val(data);
				$('#MrsDetail_bar_code').trigger('change');
			},
			'cache': false
		});
	}

	function checkTaxes() {
		var item_id = $('#item_hsn_code').val();

		var item_detail_id = $('#MrsDetail_bar_code').val();
		var vendor_id = $('#MrsDetail_vendor_id').val();

		console.log('new' + item_detail_id);
		jQuery.ajax({
			'type': 'POST',
			'url': '<?php echo Ui::to('mrsDetail/ajaxTax') ?>',
			'data': {
				'item_id': item_id,
				'item_detail_id': item_detail_id,
				'vendor_id': vendor_id
			},
			dataType: 'json',
			'success': function(data) {
				console.log('new' + data);
				if (data.msg == 'success') {
					$('#item_tax_data').html('');
					$('#item_tax_data').html(data.options);
					$('#CGST_per').val(data.cgst);
					$('#SGST_per').val(data.sgst);
					$('#CESS_per').val(data.cess);
					$('#IGST_per').val(data.igst);
					$('#MrsDetail_mrp').val(data.mrp);
					$('#MrsDetail_price_field').val(data.price);
					$('#MrsDetail_req_qty').val(data.max_qty);
					$('#MrsDetail_item_detaill_id').val(data.item_detail_id);
					$('#MrsDetail_item_val_id').val(data.item_title);
					$('#item_hsn_code').val(data.item_id);
					$('#MrsDetail_sale_rate').val(data.sale_rate);
					$('#MrsDetail_item_detaill_list_id').val(data.tax_id);
					if (data.attr == 'readOnly') {
						$('#MrsDetail_sale_rate').attr('readonly', true);
					} else {

						$('#MrsDetail_sale_rate').attr('readonly', false);
					}
					calculation();
				} else {
					if (data.msg == 'Inactive') {
						alert('Scanned Item is not of Active');
					} else {
						alert('Scanned Item is not of selected vendor');
					}
					location.reload();
				}
			},
			'cache': false
		});
	}
</script>
<script>
	$('#MrsDetail_mrs_id').change(function() {
		var vendor_id = $('#MrsDetail_vendor_id').val();
		console.log(vendor_id);
		var mrsid = $('#MrsDetail_mrs_id').val();
		var url = '<?php echo Ui::to('mrsDetail/admin') ?>/id/' + vendor_id + '/mrsid/' + mrsid;
		window.location.href = url;
	});

	$('#yw3').click(function() {
		var mrsid = "<?php echo $mrsid; ?>";
		var req_qty = $('#MrsDetail_req_qty').val();
		if (req_qty != '') {

			if (mrsid != '') {

				jQuery.ajax({
					'type': 'POST',
					'url': '<?php echo Ui::to('mrsDetail/ajaxCreate') ?>/id/' + mrsid,
					data: $("#mrs-detail-add-form").serialize(),
					'success': function(data) {

						//  alert(data);
						location.reload();
						/*      $('#item_detail_data').html('');
						     $('#item_detail_data').html(data); */

					},
					'cache': false
				});
				//console.log(form_values);
			} else {
				alert('Already there is no mrs');
			}
		} else {
			alert('Please add required quantity');
		}

	});
	$('#MrsDetail_mrp').change(function() {
		var mrp = $('#MrsDetail_mrp').val();
		if (mrp != '') {
			$('#MrsDetail_sale_rate').val(mrp);
		}
		<?php if ($user->role_id != $role->id) { ?>
			$('#MrsDetail_sale_rate').attr('readonly', true);
		<?php } ?>

	});

	$('#MrsDetail_approved_qty').change(function() {
		calculation();

	});
	$('#MrsDetail_price_field').change(function() {
		var price = $('#MrsDetail_price_field').val();
		var qty = $('#MrsDetail_approved_qty').val();
		var search = price.search('/');
		if (search != '-1' && qty != '') {
			var price = parseFloat(price) / parseFloat(qty);
		}
		$('#MrsDetail_price_field').val(parseFloat(price).toFixed(2));
		calculation();

	});
	$('#MrsDetail_discount').change(function() {
		calculation(val = 0);

	});
	$('#MrsDetail_discount_amt').change(function() {
		calculation(val = 1);

	});
	$('#MrsDetail_discount1').change(function() {
		calculation(val = 2);

	});
	$('#MrsDetail_discount_amt1').change(function() {
		calculation(val = 1);

	});
	$('#MrsDetail_other_charge').change(function() {
		calculation();

	});
	$('#MrsDetail_mrp').change(function() {
		calculation();

	});

	function calculation(val = 1) {

		var CGST_amt = '0.00';
		var SGST_amt = '0.00';
		var CESS_amt = '0.00';
		var IGST_amt = '0.00';
		var price = $('#MrsDetail_price_field').val();
		var discount = $('#MrsDetail_discount').val();
		var discount1 = $('#MrsDetail_discount1').val();
		var CGST_per = $('#CGST_per').val();
		var SGST_per = $('#SGST_per').val();
		var CESS_per = $('#CESS_per').val();
		var IGST_per = $('#IGST_per').val();
		if (discount == '' || discount == 'NaN') {
			var discount = '0.00';
			var discount_amt = '0.00';
		}
		if (discount1 == '' || discount1 == 'NaN') {
			var discount1 = '0.00';
			var discount_amt1 = '0.00';
		}
		console.log('IGST_per' + IGST_per);
		console.log('IGST_amt' + IGST_amt);
		if (price != '' && discount != '') {
			console.log('discount' + discount);
			var qty = $('#MrsDetail_approved_qty').val();
			if (discount != '0.00')
				var discount_amt = qty * (price * discount / 100);

			if (val != 0) {
				discount_amt = $('#MrsDetail_discount_amt').val();
				if (discount_amt == '') {
					var discount_amt = '0.00';
				}
			}

			console.log('discount_amt' + discount_amt);
			if (discount1 != '0.00')
				var discount_amt1 = qty * (price * discount1 / 100);
			if (val != 2) {
				discount_amt1 = $('#MrsDetail_discount_amt1').val();
				if (discount_amt1 == '') {
					var discount_amt1 = '0.00';
				}
			}
			console.log('discount_amt1' + discount_amt1);
			var calculate_amount = qty * (parseFloat(price));
			console.log('calculate_amount' + calculate_amount);
			var calculate_discount = parseFloat(discount_amt1) + parseFloat(discount_amt);
			console.log('calculate_discount' + calculate_discount);
			var taxable_amount = parseFloat(calculate_amount) - parseFloat(calculate_discount);
			console.log('taxable_amount' + taxable_amount)
			CGST_amt = taxable_amount * CGST_per / 100;
			SGST_amt = taxable_amount * SGST_per / 100;
			CESS_amt = taxable_amount * CESS_per / 100;
			IGST_amt = taxable_amount * IGST_per / 100;
			$('#MrsDetail_discount_amt').val(discount_amt);
			$('#MrsDetail_discount_amt1').val(discount_amt1);
			$('#CGST_amt').val(CGST_amt.toFixed(2));
			$('#SGST_amt').val(SGST_amt.toFixed(2));
			$('#CESS_amt').val(CESS_amt.toFixed(2));
			$('#IGST_amt').val(IGST_amt.toFixed(2));
			var mrp = $('#MrsDetail_mrp').val();
			console.log('mrp' + mrp);
			<?php if ($model->getGSTTrue($mrsid) == true) { ?>
				var price_cgst = parseFloat(price) * CGST_per / 100;
				var price_sgst = parseFloat(price) * SGST_per / 100;
				var price_cess = parseFloat(price) * CESS_per / 100;
				var gst = parseFloat(price_cgst) + parseFloat(price_sgst) + parseFloat(price_cess);
			<?php } else { ?>
				var price_igst = parseFloat(price) * IGST_per / 100;
				var gst = parseFloat(price_igst);
			<?php } ?>


			var margin = (parseFloat(mrp) - (parseFloat(price) + parseFloat(gst))) * 100 / (parseFloat(price) + parseFloat(gst));
			$('#MrsDetail_margin').val(margin.toFixed(2));
			var other_charge = $('#MrsDetail_other_charge').val();

			if (other_charge == '') {
				other_charge = '0.00';
			}
			console.log(other_charge);
			<?php if ($model->getGSTTrue($mrsid) == true) { ?>
				var total_amount = parseFloat(taxable_amount) + parseFloat(CGST_amt) + parseFloat(SGST_amt) + parseFloat(CESS_amt);
			<?php } else { ?>
				var total_amount = parseFloat(taxable_amount) + parseFloat(IGST_amt);

			<?php } ?>

			$('#MrsDetail_amount').val(total_amount.toFixed(2));
		}
	}
</script>