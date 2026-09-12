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
       
        <?php /*?><div  id="bar_code_print">
        	<div class="bar-code-box">
        	<div class="bar-box">
        	<p>DOVE SHAMPOO DRYNE</p>
        	<p>MRP: 400.00</p>
        	<p>8901030696428</p>
       <?php  echo ItemDetail::getItemBarcode(array("itemId"=> '12975', "barocde"=>'8901030696428'));?>
       </div>
        	</div>
        	
        	
        	
        	<div class="bar-box">
        	<p>DOVE SHAMPOO DRYNE</p>
        	<p>MRP: 400.00</p>
        	<p>8901030696428</p>
        	 <?php echo ItemDetail::getItemBarcode(array("itemId"=> '12976', "barocde"=>'8901030696420'));?></div>
        	 
        	 
        	 
        	
        	<div class="clearfix"></div>
        
        	
        	</div>
        	
        	
        </div>*/?>
        
        
        
        
        
        
        
        
        
        
        
        
      
        
     <div class="row">
     <br/>
     <div class="form well">


<?php $form=$this->beginWidget('bootstrap.widgets.TbActiveForm',array(
	'action'=>Yii::app()->createUrl('item/printBarcode'),
	'id' => 'print-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>array('enctype'=>'multipart/form-data'),
));
?>
	

	<?php echo $form->errorSummary($model); ?>
<div class="col-md-6">
<?php echo $form->dropDownListRow($model, 'item_print_id',$model->getItemPrintDetails(),array('class'=>'form-control')); ?>

</div>
<div class="col-md-6">
<?php echo $form->textFieldRow($model,'item_qty',array('class'=>'form-control','maxlength'=>205)); ?>
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
<?php if((Yii::app()->session['item_print_id'] == '')&& (Yii::app()->session['item_qty'] == '')){?>
      <div id="bar_code_print">
  
     <?php  
	foreach($dataProvider->getData() as $item){
		$result = array();
		$result1 = array();
	$list = Yii::app()->session['date_list'];
	$packing = Yii::app()->session['packing_date_list'];
	$idlist = Yii::app()->session['idList'];
	Yii::log ( CVarDumper::dumpAsString ( $idlist ), CLogger::LEVEL_WARNING, '$idlist' );
	Yii::log ( CVarDumper::dumpAsString ( $list ), CLogger::LEVEL_WARNING, '$list' );
	$expiry_val = Yii::app()->session['expiry_val'];
	if(!empty($list)){
		$result = array_combine($idlist, $list);
	}
	if(!empty($packing)){
		$result1 = array_combine($idlist, $packing);
	}

	?>
		 <div class="bar-box">
	<p><?php 	
	echo isset($item->item)?substr($item->item->short_name,0,20):"";?></p>
	
	<p><?php echo 'MRP: '.$item->getItemDetailSaleRate();?>
		<span>&nbsp;&nbsp;<?php 	
	//echo isset($item->item)?$item->item->item_code:"";
	echo $item->bar_code;
	?></span>
	</p>
	<p>
	<?php 
	if(!empty($result1) ){
		foreach($result1 as $key=>$res){
			if($key == $item->id && ($result1[$key] != 'Empty')){
	echo 'Pck: '.date('d/m/y',strtotime($result1[$key]));
			}
		}
	} 
	?>
	<?php 
	 if(!empty($result) && ($expiry_val  == 1)){
		foreach($result as $key=>$res){
			if($key == $item->id){
	echo 'Exp: '.date('d/m/y',strtotime($result[$key]));
	
			}
		}
	} 
	?>
	<?php //echo 'Exp:04/06/18';?>
	<?php //echo 'Pck:04/06/18';?>
	
	
	
	</p>
	
	<!--<p>Auto-Ser Stn,CC:9914770022</p>-->
	
	<?php 	$mrp =  $item->getItemDetailSaleRate();
	if($item->company_bar_code == ItemDetail::IS_COMPANY){
		$barcode = $item->bar_code.'!'.intval($mrp);
	}else{
		$barcode = $item->bar_code;
	}
	//$barcode = $item->bar_code.'!'.$mrp;
	
	echo ItemDetail::getItemBarcode(array("itemId"=> $item->id, "barocde"=>$barcode));?>
	
	
		
	</div>	</div>	
     <?php 
     }
    

/* 
$this->renderPartial('/itemDetail/_list', array(
		'dataProvider'=>$dataProvider,
)); */?>
</div>

<?php }else{
	$result = array();
	$result1 = array();
	$list = Yii::app()->session['date_list'];
	$idlist = Yii::app()->session['idList'];
	$expiry_val = Yii::app()->session['expiry_val'];
	$packing = Yii::app()->session['packing_date_list'];
	if(!empty($list)){
		$result = array_combine($idlist, $list);
	}
	if(!empty($packing)){
		$result1 = array_combine($idlist, $packing);
	}
$item_detail_id = Yii::app()->session['item_print_id'];
if($item_detail_id){
	$item = ItemDetail::model()->findByPk($item_detail_id);
	if($item){
		
	
if(isset(Yii::app()->session['item_qty']) && (Yii::app()->session['item_qty']!= '')){
  		$qty = Yii::app()->session['item_qty'];
  		if($qty > 50){
  			$qty = 50;
  		}
  	}else{
  		$qty = 1;
  		
  	}
	?>
	 <div id="bar_code_print">
	<?php for($i =1;$i<=$qty;$i++){?>
  			 
  			 <div class="bar-box">
		<p><?php 	
	echo isset($item->item)?substr($item->item->short_name,0,20):"";?></p>
	
	<p><?php echo 'MRP: '.$item->getItemDetailSaleRate();?>
		<span>&nbsp;&nbsp;<?php 	
//	echo isset($item->item)?$item->item->item_code:"";
	echo $item->bar_code;
	?></span>
	</p>
	<p>
	<?php 
	if(!empty($result1) ){
		foreach($result1 as $key=>$res){
			if($key == $item->id && ($result1[$key] != 'Empty')){
	echo 'Pck: '.date('d/m/y',strtotime($result1[$key]));
			}
		}
	} 
	?>
	<?php 
	 if(!empty($result) && ($expiry_val  == 1)){
		foreach($result as $key=>$res){
			if($key == $item->id){
	echo 'Exp: '.date('d/m/y',strtotime($result[$key]));
	
			}
		}
	} 
	?>
	<?php //echo 'Exp:04/06/18';?>
	<?php //echo 'Pck:04/06/18';?>
	
	
	
	</p>
	
	<!--<p>Auto-Ser Stn,CC:9914770022</p>-->
	
	<?php 	$mrp =  $item->getItemDetailSaleRate();
	if($item->company_bar_code == ItemDetail::IS_COMPANY){
		$barcode = $item->bar_code.'!'.intval($mrp);
	}else{
		$barcode = $item->bar_code;
	}
	
	echo ItemDetail::getItemBarcode(array("itemId"=> $item->id.$i, "barocde"=>$barcode));?>
	
	
		
	</div>	</div>
	<?php }?>
	</div>
	
	
	<?php 
	
	
	}
}?>

<?php }?>
</div>
<?php 
  $this->widget('CLinkPager',array(
    'pages'=>$dataProvider->pagination,
  	//	'htmlOptions'=>array('class'=>''),
  		
  )
  		);
						?> 
            
            </div>
            
            
        </div>    
     </div>
     
     </div></div>
</section>
<!-- form code ends here -->
<script>
<?php if(Yii::app()->session['item_print_id'] != ''){?>
$('#ItemDetail_item_print_id').val(<?php echo Yii::app()->session['item_print_id'];?>);

<?php }?>
<?php if(Yii::app()->session['item_qty'] != ''){?>
$('#ItemDetail_item_qty').val(<?php echo Yii::app()->session['item_qty'];?>);

<?php }?>
$("#print_btn").click(function () {
    $("#bar_code_print").print();
});
</script>
