<?php
/**
 * Ported from protected/views/order/_billpdf.php.
 */

use app\models\Customer;
use app\models\Item;
use app\models\OrderItem;
use app\models\User;
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

<div style="margin:0 auto; text-align: center;">
  <div style="display:inline-block;margin:0 auto; width:94%; border:solid 1px #333; padding:15px;" >
  
<table border="0" width="100%" cellpadding="0" cellspacing="0">
  <tbody>
    <tr>
      <td colspan="4" style="text-align:left; font-size:18px; font-weight:bold">Retail Bill - ORIGINAL</td>
    
    </tr>
    <tr>
     <td colspan="4" style="text-align:left; font-size:18px; font-weight:bold">Auto Service Station</td>
    </tr>
   <tr>
     <td colspan="4" style="text-align:left; font-size:16px; font-weight:bold">In & Out Store</td>
    </tr>
    <tr>
     <td colspan="4" style="text-align:left; font-size:16px; font-weight:bold">Sector 4 Petrol Pump Chandigarh</td>
    </tr>
    <tr>
     <td colspan="4" style="text-align:left; font-size:14px; font-weight:bold">GST NO: 04ABZPS6311A1ZO</td>
    </tr>
    <?php $customer = Customer::findOne($order->customer_id);
	$user = User::findOne($order->create_user_id);?>
    <tr>
     <td colspan="4" style="text-align:left; font-size:14px; font-weight:bold">Bill No: B-<?php echo $order->bill_no;?></td>
    </tr>
    
    <tr>
     <td colspan="4" style="text-align:left; font-size:14px; font-weight:bold">Customer Name:<?php if($customer){ echo $customer->name; }?></td>
    </tr>
    
     <tr>
     <td colspan="4" style="text-align:left; font-size:14px; font-weight:bold">User: <?php if($user){ echo $user->full_name; }?></td>
    </tr>
    
     <tr>
     <td colspan="4" style="text-align:left; font-size:14px; font-weight:bold">Date: <?php echo date('Y-m-d h:i a',strtotime($order->bill_date));?></td>
    </tr>
    
     <tr>
     <td colspan="4" style="text-align:left; font-size:14px; font-weight:bold"><br></td>
    </tr>
    
   
    
    </tbody>
  </table>
   <table border="1" width="100%" cellpadding="1" cellspacing="0" class="table-bordered"> 
  <tbody>
    <tr>
      <th style="text-align:left; font-size:14px; font-weight:bold; width:40%">Name </th>
      <th style="text-align:left; font-size:14px; font-weight:bold">QTY</th>
      <th style="text-align:left; font-size:14px; font-weight:bold">MRP</th>
      <th style="text-align:left; font-size:14px; font-weight:bold">Rate</th>
      <th style="text-align:left; font-size:14px; font-weight:bold">Total</th> 
    </tr>
	<?php $items = $order->orderItems;
	if($items){
		foreach($items as $item){
			$product = Item::findOne($item->item_id);
			if($product){
			?>
    <tr>
      <td style="text-align:left; font-size:14px; font-weight:bold; text-transform:uppercase;"><?php echo $product->title; ?></td>
      <td style="text-align:left; font-size:14px; font-weight:bold; text-transform:uppercase;"><?php echo $item->qty; ?></td>
      <td style="text-align:left; font-size:14px; font-weight:bold; text-transform:uppercase;"><?php echo $item->mrp; ?></td>
      <td style="text-align:left; font-size:14px; font-weight:bold; text-transform:uppercase;"><?php echo $item->sale_rate; ?></td>
      <td style="text-align:left; font-size:14px; font-weight:bold; text-transform:uppercase;"><?php echo $item->total_amt; ?></td>
    </tr>
	<?php }}}?>
       
     
  </tbody>
</table>

     
     
   <table border="0" width="100%" cellpadding="0" cellspacing="0">
  <tbody>
    
    <tr>
     <td colspan="4" style="text-align:left; font-size:14px; font-weight:bold"><br></td>
    </tr>
    
    <tr>
     <td colspan="4" style="text-align:left; margin:10px 0; font-size:14px; font-weight:bold; border-top:dashed 3px #000;"></td>
    </tr>
    
    
    
	<?php 
	
	
			
	$query = OrderItem::find();
	 												$query->groupBy('tax_id');
	 												// MySQL 5.7 implicitly sorted GROUP BY results; MySQL 8.0 does not, so
	 												// without an explicit order the GST summary lines on a bill come back in
	 												// an unspecified sequence and the same invoice prints its tax rows in a
	 												// different order between renders. Sort explicitly to match 5.7.
	 												$query->orderBy(['tax_id' => SORT_ASC]);
	 												Criteria::compare($query, 'order_id', $order->id);
	 												$itemms = $query->all();
	if($itemms){
		foreach($itemms as $item){
			$query_2 = OrderItem::find();
			$query_2->andWhere('order_id =' . $order->id);
			$query_2->andWhere('tax_id =' . $item->tax_id);
			$taxes = $query_2->all();
			// Yii::warning( var_export($taxes, true), '$ordertaxes');
			
			if ($taxes) {
				$cgst = 0;
				$sgst = 0;
				$cess = 0;
				$igst = 0;
				
				foreach ( $taxes as $tax ) {
					$cgst = $cgst + ($tax->cgst_amt);
					$sgst = $sgst + ($tax->sgst_amt);
					$cess = $cess + ($tax->cess_amt);
					$igst = $igst + ($tax->igst_amt);
				}
			}
			
			?>
			<tr>
     <td  style="text-align:left; margin:10px 0; font-size:14px; font-weight:bold; ">
     	CGST @<?php echo $item->cgst_per.' '.$cgst;?>
     
     </td>
     
     <td  style="text-align:left; margin:10px 0; font-size:14px; font-weight:bold; ">
     	SGST @<?php echo $item->sgst_per.' '.$sgst;?>
     
     </td>
     
     <td  style="text-align:left; margin:10px 0; font-size:14px; font-weight:bold; ">
     	IGST @<?php echo $item->igst_per.' '.$igst;?>
     
     </td>
     <td  style="text-align:left; margin:10px 0; font-size:14px; font-weight:bold; ">
     	CESS @<?php echo $item->cess_per.' '.$cess;?>
     
     </td>
    </tr>
    <?php }}?>
    
      <tr>
     <td colspan="4" style="text-align:left; margin:10px 0; font-size:14px; font-weight:bold; border-top:dashed 3px #000;"></td>
    </tr>
    
    
    <tr>
      <td colspan="4" style="text-align:left; font-size:14px;">Total Sale Value : <?php echo $order->total_amt + $order->discount_amt; ?></td>
    
    </tr>
    
      <tr>
      <td colspan="4" style="text-align:left; font-size:14px; font-weight:bold">NET Amount  : <?php echo $order->total_amt; ?></td>
    
    </tr>
    
     <tr>
      <td colspan="4" style="text-align:left; font-size:14px; text-transform:uppercase;">Please Check Expiry, MRP, Bill </td>    
    </tr>
    
    <tr>
      <td colspan="4" style="text-align:left; font-size:14px; font-weight:bold">Shop Online on SOULBOWL.in </td>    
    </tr>
    
     <tr>
      <td colspan="4" style="text-align:left; font-size:14px; font-weight:bold;text-transform:uppercase;">Bill amount Inclusive of UTGST & CGST </td>    
    </tr>
    
     <tr>
      <td colspan="4" style="text-align:left; font-size:14px; font-weight:bold;text-transform:uppercase;">PH: 0172-2740064 M:9814740064</td>    
    </tr>
    
    <tr>
      <td colspan="4" style="text-align:left; font-size:14px; font-weight:bold;text-transform:uppercase;">Good Once Sold Will Not be Taken Back</td>    
    </tr>
    
  </tbody>
</table>

   
  </div>
 
</div>

