<!--  form code start here -->
<!--  form code start here -->
<script
src="<?php  echo Yii::app()->theme->baseUrl; ?>/js/jQuery.print.js"
		type="text/javascript"></script>
		<section class="content">
		<div class="row">
		<div class="col-md-12 col-xs-12">
		<div class="box">

		<div class="box-header"><h3 class="box-title">Print Barcode</h3></div>


		<div class="box-body">
		<div class="row">
		<div class="col-md-12">

	
        <a id="print_btn" class="btn btn-success"><i class="icon-wrench icon-white"></i> Print</a>
       
   
 <div class="row">
 <div class="form well">


<?php $form=$this->beginWidget('bootstrap.widgets.TbActiveForm',array(
	'action'=>Yii::app()->createUrl('purchaseBill/printBarcode'),
	'id' => 'print-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>array('enctype'=>'multipart/form-data'),
));
?>
	<p class="help-block">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>
<div class="col-md-6">
<?php echo $form->dropDownListRow($model, 'print_id',$model->getPurchasePrintDetails(),array('class'=>'form-control')); ?>

</div>
<div class="col-md-6">
<?php echo $form->textFieldRow($model,'qty',array('class'=>'form-control','maxlength'=>205)); ?>
</div>

	<div class="form-actions">
		<?php $this->widget('bootstrap.widgets.TbButton', array(
			'buttonType'=>'submit',
			'type'=>'primary',
			'label'=>'View',
		)); ?>
	</div>

<?php $this->endWidget(); ?>

</div>
<?php Yii::log ( CVarDumper::dumpAsString ( Yii::app()->session['print_id'] ), CLogger::LEVEL_WARNING, 'session_pint' );?>
<?php if(isset(Yii::app()->session['print_id']) && (Yii::app()->session['print_id']!= '')){
  		?>
  		<div id="bar_code_print">
    
  <?php 
  $criteria = new CDbCriteria();
  $criteria->compare('id', Yii::app()->session['print_id']);
  $purchasebilldetail = PurchaseBillDetail::model()->find($criteria);
 
  $result = array();
  $result1 = array();
  $list = Yii::app()->session['bill_date_list'];
  $packing = Yii::app()->session['packing_date_list'];
  $idlist = Yii::app()->session['billidList'];
  Yii::log ( CVarDumper::dumpAsString ( Yii::app()->session['$list'] ), CLogger::LEVEL_WARNING, '$list' );
  $expiry_val = Yii::app()->session['bill_expiry_val'];
  
  if(!empty($list)){
  	$result = array_combine($idlist, $list);
  }
  if(!empty($packing)){
  	$result1 = array_combine($idlist, $packing);
  }
  
  if($purchasebilldetail){
  
  		$item= ItemDetail::model()->findByPk($purchasebilldetail->item_detail_id);
  	if(isset(Yii::app()->session['qty']) && (Yii::app()->session['qty']!= '')){
  		$qty = Yii::app()->session['qty'];
  		if($qty > 50){
  			$qty = 50;
  		}
  	}else{
  		$qty = $purchasebilldetail->approved_qty;
  		if($qty > 50){
  			$qty = 50;
  		}
  	}
  			
  			
  		
  		if($item){
  			?>
  			<?php for($i =1;$i<=$qty;$i++){?>
  			 
  			 <div class="bar-box">
	<p><?php 	
	echo isset($item->item)?substr($item->item->short_name,0,20):"";?></p>
	
	<p><?php echo 'MRP: '.$item->getItemDetailMrp();?>
		<span>&nbsp;&nbsp;<?php 	
//	echo isset($item->item)?$item->item->item_code:"";
		echo $item->bar_code;
	?></span>
	</p>
	
	<p>
	<?php 
	if(!empty($result1) ){
		foreach($result1 as $key=>$res){
			if($key == $purchasebilldetail->id && ($result1[$key] != 'Empty')){
	echo 'Pck: '.date('d/m/y',strtotime($result1[$key]));
			}
		}
	} 
	?>
	<?php 
	 if(!empty($result) && ($expiry_val  == 1)){
		foreach($result as $key=>$res){
			if($key == $purchasebilldetail->id){
	echo 'Exp: '.date('d/m/y',strtotime($result[$key]));
	
			}
		}
	} 
	?>
	<?php //echo 'Exp:04/06/18';?>
	<?php //echo 'Pck:04/06/18';?>
	
	
	
	</p>
	
	<!--<p>Auto-Ser Stn,CC:9914770022</p>-->
	
	<?php 	$mrp =  $item->getItemDetailMrp();
	if($item->company_bar_code == ItemDetail::IS_COMPANY){
	$barcode = $item->bar_code.'!'.intval($purchasebilldetail->sale_rate);
	}else{
		$barcode = $item->bar_code;
	}
	
	Yii::log ( CVarDumper::dumpAsString ( $item ), CLogger::LEVEL_WARNING, '$$$item' );
	echo ItemDetail::getItemBarcode(array("itemId"=> $item->id.$i, "barocde"=>$barcode));?>
	
	
		
	</div></div>
	<?php }?>
  			<?php 
  		}
  		
  	
  }
  ?>
     
</div>
</div>
  		<?php 	
  		}else{?>
      <div id="bar_code_print">
    
  <?php 
  $criteria = new CDbCriteria();
  // The session key is unset until a bill has been picked, and
  // addInCondition() calls count() on its argument - a TypeError on PHP 8
  // where PHP 7 warned and carried on with zero.
  $criteria->addInCondition('id', is_array(Yii::app()->session['billidList'])
      ? Yii::app()->session['billidList'] : array());
  $purchasebilldetails = PurchaseBillDetail::model()->findAll($criteria);
  Yii::log ( CVarDumper::dumpAsString ( Yii::app()->session['billidList'] ), CLogger::LEVEL_WARNING, '$ids' );
  $result = array();
  $result1 = array();
  $list = Yii::app()->session['bill_date_list'];
  $idlist = Yii::app()->session['billidList'];
  $packing = Yii::app()->session['packing_date_list'];
  Yii::log ( CVarDumper::dumpAsString ( Yii::app()->session['$list'] ), CLogger::LEVEL_WARNING, '$list' );
  $expiry_val = Yii::app()->session['bill_expiry_val'];
  
  if(!empty($list)){
  	$result = array_combine($idlist, $list);
  }
  if(!empty($packing)){
  	$result1 = array_combine($idlist, $packing);
  }

  
  if($purchasebilldetails){
  	foreach($purchasebilldetails as $purchasebilldetail){
  		$item= ItemDetail::model()->findByPk($purchasebilldetail->item_detail_id);
  		$qty = $purchasebilldetail->approved_qty;
  		if($qty > 50){
  			$qty = 50;
  		}
  		if($item){
  			?>
  			<?php for($i =1;$i<=$qty;$i++){?>
  			 
  			 <div class="bar-box">
	<p><?php 	
	echo isset($item->item)?substr($item->item->short_name,0,20):"";?></p>
	
	<p><?php echo 'MRP: '.$item->getItemDetailMrp();?>
		<span>&nbsp;&nbsp;<?php 	
	//echo isset($item->item)?$item->item->item_code:"";
		echo $item->bar_code;
	?></span>
	</p>
	
	<p>
	<?php 
	if(!empty($result1) ){
		foreach($result1 as $key=>$res){
			if($key == $purchasebilldetail->id && ($result1[$key] != 'Empty')){
	echo 'Pck: '.date('d/m/y',strtotime($result1[$key]));
			}
		}
	} 
	?>
	<?php 
	 if(!empty($result) && ($expiry_val  == 1)){
		foreach($result as $key=>$res){
			if($key == $purchasebilldetail->id){
	echo 'Exp: '.date('d/m/y',strtotime($result[$key]));
	
			}
		}
	} 
	?>
	<?php //echo 'Exp:04/06/18';?>
	<?php //echo 'Pck:04/06/18';?>
	
	
	
	</p>
	
	<!--<p>Auto-Ser Stn,CC:9914770022</p>-->
	
	<?php 	$mrp =  $item->getItemDetailMrp();
	if($item->company_bar_code == ItemDetail::IS_COMPANY){
	$barcode = $item->bar_code.'!'.intval($purchasebilldetail->sale_rate);
	}else{
		$barcode = $item->bar_code;
	}
	
	Yii::log ( CVarDumper::dumpAsString ( $item ), CLogger::LEVEL_WARNING, '$$$item' );
	echo ItemDetail::getItemBarcode(array("itemId"=> $item->id.$i, "barocde"=>$barcode));?>
	
	
		
	</div></div>
	<?php }?>
  			<?php 
  		}
  		
  	}
  }
  ?>
     
</div>
</div>
<?php }?>



            
            </div>
        </div>    
     </div>
     
     </div></div>
</section>
<!-- form code ends here -->
<script>
$("#print_btn").click(function () {
    $("#bar_code_print").print();
});
</script>

