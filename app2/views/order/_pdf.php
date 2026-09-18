<?php
/**
 * Ported from protected/views/order/_pdf.php.
 */

use Yii;
use app\models\Order;
?>
<style>
body {
	-webkit-print-color-adjust: exact;
	/*font-family:Segoe, "Segoe UI", "DejaVu Sans", "Trebuchet MS", Verdana, sans-serif;*/
	font-family:"Helvetica Neue", Helvetica, Arial, sans-serif;
	font-size:12px;
}
 
@media print {
body {
	-webkit-print-color-adjust: exact;
	-moz-print-color-adjust: exact;
}


 
    table.print-friendly tr td, table.print-friendly tr th {
     
        padding:2px;
    }
    
    table.print-friendly tr th {background:#747474;}
 

}

table {
	border-spacing: 0;
	border-collapse: collapse;
}

.table-bordered > tbody > tr > td, .table-bordered > tbody > tr > th, .table-bordered > tfoot > tr > td, .table-bordered > tfoot > tr > th, .table-bordered > thead > tr > td, .table-bordered > thead > tr > th {
	border: 1px solid #ddd;
}

  
 
</style>

<table border="0" cellspacing="0" cellpadding="0" width="100%" style="margin-bottom:5px;"  >
     
      	<tr>
            <td colspan="5" align="center">
            	<h4 style="margin:0; padding:0; font-size:20px;">Userwise Sale Report</h4> <br>
                
            </td>
         </tr>
         </table>
         <table border="1" cellspacing="0" cellpadding="0" width="100%" style="margin-bottom:5px;"  >
        
<?php
	
$query = Order::find();
if((Yii::$app->session['start_date'] != '') && (Yii::$app->session['end_date'] != '')){
	$query->andWhere(['between', 'bill_date', Yii::$app->session['start_date'], Yii::$app->session['end_date']]);
}
$query->orderBy(['id' => SORT_DESC]);
$query->groupBy('create_user_id');
$userorders = $query->all();

	?>
        
   
      
        
        <?php 
          if($userorders){
          	?>
          		<tr>
                      <th align="center"><strong>S.No.</strong></th>
                       <th align="center"><strong>Date</strong></th>
                      <th align="center"><strong>Username</strong></th>
                      <th align="center"><strong>Gross Amount</strong></th>
                      </tr>
                    <?php foreach($userorders as $userorder){
                    $i = $i+1;?>
                    <tr>
                      <td><?php echo $i;?></td>
                      <td><?php echo isset($userorder->bill_date)?$userorder->bill_date:"";?></td>
                      <td><?php echo isset($userorder->createUser)?$userorder->createUser:"";?></td>
                      <td><?php echo $userorder->getTotalNetAmount();?></td>
                     
                    </tr>
                    <?php }?>
                
    			<?php }?>
        
       
     </table>