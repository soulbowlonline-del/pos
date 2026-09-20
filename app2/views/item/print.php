<?php
/**
 * Ported from protected/views/item/print.php.
 */

use app\components\Ui;
use app\models\ItemDetail;
use app\widgets\ActiveForm;
use app\widgets\Button;
?>
<!--  form code start here -->
<!--  form code start here -->
<script
src="<?php  echo '/themes/bar'; ?>/js/jQuery.print.js"
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


<?php $form = ActiveForm::begin([
	'action'=>Ui::to('item/printBarcode'),
	'id' => 'print-form',
	'type'=>'horizontal',
	'enableAjaxValidation' => true,
	'htmlOptions'=>['enctype'=>'multipart/form-data'],
]);
?>
	

	<?php echo $form->errorSummary($model); ?>
<div class="col-md-6">
<?php echo $form->dropDownListRow($model, 'item_print_id',$model->getItemPrintDetails(),['class'=>'form-control']); ?>

</div>
<div class="col-md-6">
<?php echo $form->textFieldRow($model,'item_qty',['class'=>'form-control','maxlength'=>205]); ?>
</div>

	<div class="form-actions">
		<?php echo Button::widget([
			'buttonType'=>'submit',
			'type'=>'primary',
			'label'=>'View',
		]); ?>
	</div>

<?php ActiveForm::end(); ?>

</div>
<?php if((Yii::$app->session['item_print_id'] == '')&& (Yii::$app->session['item_qty'] == '')){?>
      <div id="bar_code_print">
  
     <?php  
	foreach($dataProvider->getModels() as $item){
		$result = [];
		$result1 = [];
	$list = Yii::$app->session['date_list'];
	$packing = Yii::$app->session['packing_date_list'];
	$idlist = Yii::$app->session['idList'];
	Yii::warning( var_export($idlist, true), '$idlist');
	Yii::warning( var_export($list, true), '$list');
	$expiry_val = Yii::$app->session['expiry_val'];
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
	
	echo ItemDetail::getItemBarcode(["itemId"=> $item->id, "barocde"=>$barcode]);?>
	
	
		
	</div>	</div>	
     <?php 
     }
    

/* 
echo $this->render('/itemDetail/_list', array(
		'dataProvider'=>$dataProvider,
)); */?>
</div>

<?php }else{
	$result = [];
	$result1 = [];
	$list = Yii::$app->session['date_list'];
	$idlist = Yii::$app->session['idList'];
	$expiry_val = Yii::$app->session['expiry_val'];
	$packing = Yii::$app->session['packing_date_list'];
	if(!empty($list)){
		$result = array_combine($idlist, $list);
	}
	if(!empty($packing)){
		$result1 = array_combine($idlist, $packing);
	}
$item_detail_id = Yii::$app->session['item_print_id'];
if($item_detail_id){
	$item = ItemDetail::findOne($item_detail_id);
	if($item){
		
	
if(isset(Yii::$app->session['item_qty']) && (Yii::$app->session['item_qty']!= '')){
  		$qty = Yii::$app->session['item_qty'];
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
	
	echo ItemDetail::getItemBarcode(["itemId"=> $item->id.$i, "barocde"=>$barcode]);?>
	
	
		
	</div>	</div>
	<?php }?>
	</div>
	
	
	<?php 
	
	
	}
}?>

<?php }?>
</div>
<?php 
  echo \app\widgets\LinkPager::widget([
    'pages'=>$dataProvider->pagination,
  	//	'htmlOptions'=>array('class'=>''),
  		
  ]
  		);
						?> 
            
            </div>
            
            
        </div>    
     </div>
     
     </div></div>
</section>
<!-- form code ends here -->
<script>
<?php if(Yii::$app->session['item_print_id'] != ''){?>
$('#ItemDetail_item_print_id').val(<?php echo Yii::$app->session['item_print_id'];?>);

<?php }?>
<?php if(Yii::$app->session['item_qty'] != ''){?>
$('#ItemDetail_item_qty').val(<?php echo Yii::$app->session['item_qty'];?>);

<?php }?>
$("#print_btn").click(function () {
    $("#bar_code_print").print();
});
</script>
