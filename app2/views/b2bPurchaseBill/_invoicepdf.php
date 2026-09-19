<?php
/**
 * Ported from protected/views/b2bpurchaseBill/_invoicepdf.php.
 */

use app\models\Tax;
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Online orders</title>
<link rel="stylesheet" type="text/css"  media="print" >
<style>
body {
	-webkit-print-color-adjust: exact;
	font-family: "Calibri";
	font-size:14px;
}
table {
	margin: 20px 0px;
}
td, th {
	padding: 0;
	border: 0;
}
.TableDataItem{
width:100% !important;	
height:auto;
page-break-after: always;
}
table, tr, td, th, tbody, thead, tfoot {
    page-break-inside: auto !important;
}
@page {
	margin-left: 20px;
	margin-right: 20px;
	margin-top: 20px;
	margin-bottom: 20px;
	margin: 20;
	-webkit-print-color-adjust: exact;
}
@media print {
body {
	-webkit-print-color-adjust: exact;
	-moz-print-color-adjust: exact;
	font-size:10px;
}

table {
	margin: 10px 0px;
}
table, tr, td, th, tbody, thead, tfoot {
    page-break-inside: auto !important;
}
.TableDataItem{
width:100% !important;	
height:auto;
page-break-after: always;
}
td, th {
	padding: 0;
	border: 0;
}
td{
	font-size:9px;
}

}
</style>
</head>

<body>
<?php
$billDetail_ = new B2bPurchaseBillDetail ( 'search' );
$bill=$billDetail_->purchasesearch();
// $bill->getGSTTrue($poid) == true
// $billDetail_->getGSTTrue($poid)== false
// echo"<pre>"; print_r($billDetail_->getGSTTrue($poid)); die;
?>
<div class="main" style=" width:95%; margin:0 auto; border: solid 1px #000;">
<!--New Table Start-->
  <table border="0" cellspacing="0" cellpadding="0" width="100%" style="margin-bottom:20px;">
    <tbody>
      <tr>
        <td width="100%" align="center"><h1 style="font-weight:bold; width:100%; font-size:22px; text-align: center; margin-bottom: 0; display:block; margin-top: 0; ">Auto Service Station<p style="font-size:14px;">IN & OUT STORE, SECTOR-4, PETROL PUMP, CHANDIGARH-160004</p> </h1></td>
      </tr>
    </tbody>
  </table>
  <!--New Table End-->

  <!--New Table Start-->
  <table border="0" cellspacing="0" cellpadding="0" width="100%" style="margin-bottom:0px;">
    <tbody>
      <tr>
        <td colspan="2" width="100%" style="border:1px solid #cee1ff !important; vertical-align:top;">
          <!--New Table Start-->
          <table border="0" cellspacing="0" cellpadding="0" width="100%" style="margin-bottom:0; margin-top:0;">
			 <thead style="background:#cee1ff; font-weight:bold;">
            	<th colspan="5" style="text-align:center; padding-top:5px; padding-bottom:5px;  padding-left:5px; font-size: 16px; padding-right:5px;">Tax Invoice</th>
            </thead>
            <tbody>
				<tr>
                	<td colspan="4" width="50%" style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; font-weight:bold; border: solid 1px #646464;">GSTIN: <sapn>04ABZPS6311A1ZO</sapn></td>
                    <td width="50%" style="padding-top:5px; padding-bottom:5px; font-weight:bold;  padding-left:5px; padding-right:5px; border: solid 1px #646464;">PAN  : <span>ABZPS6311A</span></td>
                </tr>
					<tr>
                	<td colspan="4" width="50%" style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; font-weight:bold; border: solid 1px #646464;">CONTACT: <sapn>01722-2740064 / 9814740064</sapn></td>
                    <td width="50%" style="padding-top:5px; padding-bottom:5px; font-weight:bold;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"></td>
                </tr>
                <tr>
                	<td colspan="4" width="50%" style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; font-weight:bold; border: solid 1px #646464;">Invoice No: <span><?php echo $billno;?></span> </td>
                    <td width="50%" style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;">Transport Mode: <span>NA</span></td>
                </tr>
				<tr>
                	<td colspan="4" width="50%" style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; font-weight:bold; border: solid 1px #646464;">Invoice Date: <span><?php echo date(" d M Y", strtotime($invoicedate)); ?></span></td>
                    <td width="50%" style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;">Vehicle number: <span>NA</span> </td>
                </tr>
                <tr>
                	<td colspan="4" width="50%" style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; font-weight:bold; border: solid 1px #646464;">Reverse Charge (Y/N): N  </td>
                    <td width="50%" style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;">Date of Supply: NA </td>
                </tr>
                <tr>
                	<td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; font-weight:bold; border: solid 1px #646464;">State: </td>
                    <td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;">Chandigarh</td>
                    <td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;">Code</td>
                    <td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"><?php echo substr('04ABZPS6311A1ZO', 0, 2); ?> </td>
					<td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;">Place of Supply : NA</td>
                </tr>

            </tbody>
          </table>
          <!-- Table End-->
          </td>
      </tr>
    </tbody>
  </table>
  <!--New Table End-->
	
  <!--New Table Start-->
  <table border="0" cellspacing="0" cellpadding="0" width="100%" style="margin-bottom:20px; margin-top: 0;">
    <tbody>
      <tr>
        <td colspan="2" width="100%" style="border:1px solid #cee1ff !important; vertical-align:top;">
          <!--New Table Start-->
          <table border="0" cellspacing="0" cellpadding="0" width="100%" style="margin-bottom:0; margin-top:0;">
            <tbody>
				<tr>
                    <td colspan="5" align="center" width="100%" style="background:#cee1ff; padding-top:5px; padding-bottom:5px;  padding-left:5px; text-align:center; font-size:14px; font-weight:bold; padding-right:5px; border: solid 1px #646464;"><span>Bill to Party</span></td>
                </tr>
				<tr>
                	<td width="20%" style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; font-weight:bold; border: solid 1px #646464;">Name:</td>
                    <td colspan="4" width="80%" style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"><span><?php echo $vendor->name; ?></span></td>
                </tr>
                <tr>
                	<td width="20%" style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; font-weight:bold; border: solid 1px #646464;">Address:</td>
                    <td colspan="4" width="80%" style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"><span><?php echo $vendor->primary_address; ?></span></td>
                </tr>
				<tr>
                	<td width="20%" style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; font-weight:bold; border: solid 1px #646464;">GSTIN:</td>
                    <td colspan="4" width="80%" style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"><span> <?php echo $vendor->tax_no; ?> </span></td>
                </tr>
                <tr>
                	<td width="20%" style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; font-weight:bold; border: solid 1px #646464;">State: </td>
                    <td width="80%" colspan="4" style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"><span><?php echo $state->title; ?> </span></td>
                  
                </tr>
				 <tr>
                	<td width="20%" style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; font-weight:bold; border: solid 1px #646464;">State Code: </td>
                    <td width="80%" colspan="4" style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"><span><?php echo substr($vendor->tax_no, 0, 2); ?> </span></td>
                  
                </tr>
				<tr>
                	<td width="20%" style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; font-weight:bold; border: solid 1px #646464;">Payment Terms:</td>
                    <td width="80%" colspan="4" style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"><span> --</span></td>
                </tr>
            </tbody>
          </table>
          <!-- Table End-->
          </td>
      </tr>
    </tbody>
  </table>
  <!--New Table End-->
	
	
  <!--New Table Start-->
  <table autosize="1"   border="0" cellspacing="0" cellpadding="0" width="100%" style="margin-bottom:20px;">
    
	 <thead style="background:#cee1ff; font-weight:bold;">
				<tr>
					<th rowspan="2"  style="text-align:left; padding-top:5px; background:#cee1ff; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;">S.No.</th>
					<th rowspan="2" style="text-align:left; padding-top:5px; background:#cee1ff; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;">Product Description</th>
					<!--<th rowspan="2" style="text-align:left; padding-top:5px; background:#cee1ff; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;">Bar Code</th>-->
					<th rowspan="2" style="text-align:left; padding-top:5px; background:#cee1ff; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;">HSN Code</th>
					<th rowspan="2" style="text-align:left; padding-top:5px; background:#cee1ff; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;">Unit</th>
					<th rowspan="2" style="text-align:left; padding-top:5px; background:#cee1ff; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;">Qty</th>
					<th rowspan="2" style="text-align:left; padding-top:5px; background:#cee1ff; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;">MRP</th>
					<th rowspan="2" style="text-align:left; padding-top:5px; background:#cee1ff; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;">Taxable</th>
					<th rowspan="2" style="text-align:left; padding-top:5px; background:#cee1ff; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;">Tax rate</th>
					<?php if($billDetail_->getGSTTrue($poid)== false){?>
					<th colspan="2" style="padding-top:10px; padding-bottom:10px; background:#cee1ff; text-align: center;	  padding-left:15px; padding-right:15px; border: solid 1px #646464;">IGST</th>
					<?php } ?>
					<?php if($billDetail_->getGSTTrue($poid)== true){?>		
					<th colspan="2" style="text-align:center; padding-top:5px; background:#cee1ff; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;">CGST</th>
					<th colspan="2" style="text-align:center; padding-top:5px; background:#cee1ff; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;">SGST</th>
					<?php } ?>
					<th colspan="2" style="text-align:center; padding-top:5px; background:#cee1ff; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464; ">CESS</th>
					<th colspan="2" style="text-align:center; padding-top:5px; background:#cee1ff; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;">Discount 1</th>
					<th colspan="2" style="text-align:center; padding-top:5px; background:#cee1ff; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464; ">Discount 2</th>
					<th rowspan="2" colspan="2" style="text-align:center; background:#cee1ff; padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;">Total</th>
					
				</tr>
					<tr>		
	<?php if($billDetail_->getGSTTrue($poid)== false){?>					
					<th style="text-align:center; padding-top:5px; padding-bottom:5px; background:#cee1ff;  padding-left:5px; padding-right:5px; border: solid 1px #646464;">Rate</th>
					<th style="text-align:center; padding-top:5px; padding-bottom:5px; background:#cee1ff;  padding-left:5px; padding-right:5px; border: solid 1px #646464;">Amount</th>
	<?php } ?>
	<?php if($billDetail_->getGSTTrue($poid)== true){?>			
					<th style="text-align:center; padding-top:5px; padding-bottom:5px; background:#cee1ff;  padding-left:5px; padding-right:5px; border: solid 1px #646464;">Rate</th>
					<th style="text-align:center; padding-top:5px; padding-bottom:5px; background:#cee1ff;  padding-left:5px; padding-right:5px; border: solid 1px #646464;">Amount</th>
					<th style="text-align:center; padding-top:5px; padding-bottom:5px; background:#cee1ff;  padding-left:5px; padding-right:5px; border: solid 1px #646464;">Rate</th>
					<th style="text-align:center; padding-top:5px; padding-bottom:5px; background:#cee1ff;  padding-left:5px; padding-right:5px; border: solid 1px #646464;">Amount</th>
	<?php } ?>
					<th style="text-align:center; padding-top:5px; padding-bottom:5px; background:#cee1ff;c  padding-left:5px; padding-right:5px; border: solid 1px #646464;">Rate</th>
					<th style="text-align:center; padding-top:5px; padding-bottom:5px; background:#cee1ff;  padding-left:5px; padding-right:5px; border: solid 1px #646464;">Amount</th>
					
						<th style="text-align:center; padding-top:5px; padding-bottom:5px; background:#cee1ff;c  padding-left:5px; padding-right:5px; border: solid 1px #646464;">Rate</th>
					<th style="text-align:center; padding-top:5px; padding-bottom:5px; background:#cee1ff;  padding-left:5px; padding-right:5px; border: solid 1px #646464;">Amount</th>
					
						<th style="text-align:center; padding-top:5px; padding-bottom:5px; background:#cee1ff;c  padding-left:5px; padding-right:5px; border: solid 1px #646464;">Rate</th>
					<th style="text-align:center; padding-top:5px; padding-bottom:5px; background:#cee1ff;  padding-left:5px; padding-right:5px; border: solid 1px #646464;">Amount</th>
					
				</tr>
            </thead>
			     <tbody>
			<?php 
			$a=1;
			foreach($billDetail as $_billDetail) {	
			 if($billDetail_->getGSTTrue($poid)== false){	
				// $taxable=$_billDetail->amount - ($_billDetail->igst_amt + $_billDetail->cess_amt);
				// $taxable=$_billDetail->amount - ($_billDetail->discount_amt + $_billDetail->discount_amt1 + $_billDetail->igst_amt + $_billDetail->cess_amt);
				
				// $taxable=$_billDetail->price - ($_billDetail->discount_amt + $_billDetail->discount_amt1 + $_billDetail->igst_amt + $_billDetail->cess_amt);


				$tamount=$_billDetail->price *  $_billDetail->approved_qty;
				
				$taxable=$tamount -  ($_billDetail->discount_amt + $_billDetail->discount_amt1);
				 } else{
				
				
				$taxable=$_billDetail->amount - ($_billDetail->igst_amt + $_billDetail->cgst_amt + $_billDetail->sgst_amt + $_billDetail->cess_amt);
				 }
				 // $taxable=$_billDetail->price;
				?>
				<tr class="TableDataItem">
                	<td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"><?php echo $a; ?></td>
					 <td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"><?php echo $_billDetail->item; ?></td>
                  <!--  <td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"><?php echo $_billDetail->itemDetail; ?></td>-->
                    <td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"><?php echo $_billDetail->hsn_code; ?></td>
                    <td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"><?php echo $_billDetail->getUnitName(); ?></td>
					<td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"><?php echo $_billDetail->approved_qty; ?></td>	
					<td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"><?php echo $_billDetail->mrp; ?></td>
					<td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"><?php echo $taxable;?></td>
                    <td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"><?php echo $_billDetail->tax; ?></td>
	<?php if($billDetail_->getGSTTrue($poid)== false){?>	                  
				  <td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"><?php echo $_billDetail->igst_per; ?></td>
                    <td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"><?php echo $_billDetail->igst_amt; ?></td>
	<?php } ?>
	<?php if($billDetail_->getGSTTrue($poid)== true){?>		
					<td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"><?php echo $_billDetail->cgst_per; ?></td>
                    <td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"><?php echo $_billDetail->cgst_amt; ?></td>
					<td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"><?php echo $_billDetail->sgst_per; ?></td>
                    <td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"><?php echo $_billDetail->sgst_amt; ?></td>
	<?php } ?>
					<td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"><?php echo $_billDetail->cess_per; ?></td>
                    <td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"><?php echo $_billDetail->cess_amt; ?></td>
						<td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"><?php echo $_billDetail->discount; ?></td>
                    <td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"><?php echo $_billDetail->discount_amt; ?></td>
						<td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"><?php echo $_billDetail->discount1; ?></td>
                    <td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"><?php echo $_billDetail->discount_amt1; ?></td>
					<td colspan="2" style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"><?php echo $_billDetail->amount; ?></td>
                 
                </tr>
				
				<?php $a++; } ?>
				
				<tr>
                	<td colspan="20" style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;">&nbsp;</td>
                </tr>
				<tr>
                	<td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"></td>
                    <td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"></td>
                    <td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"></td>
                    <td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"></td>
					<td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"></td>
				
						<td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"></td>
                    <td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"><?php echo $_billDetail->getTotalTaxableAmt();  ?></td>
                	
					<?php if($billDetail_->getGSTTrue($poid)== false){?>	
					<td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"></td>
					<?php } ?>
                    <td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"></td>
					<?php if($billDetail_->getGSTTrue($poid)== false){?>	
					<td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"><?php echo $_billDetail->getTotalIgstAmt();  ?></td>
					<?php } ?>
					<?php if($billDetail_->getGSTTrue($poid)== true){?>	
                    <td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"></td>
					<td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"><?php echo $_billDetail->getTotalCgstAmt();  ?></td>
                    <td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"></td>
					<td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"><?php echo $_billDetail->getTotalSgstAmt();  ?></td>
					<?php } ?>
                    <td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"></td>
					 <td  style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464; "><?php echo $_billDetail->getTotalCessAmt();  ?></td>
					 
					   <td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"></td>
					 <td  style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464; "><?php echo $_billDetail->getTotalDisAmt();  ?></td>
					 
					   <td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"></td>
					 <td  style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464; "><?php echo $_billDetail->getTotalDis1Amt();  ?></td>
                    <td  style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464; text-align: right;"><?php  echo $_billDetail->getTotalNetAmt();?></td>
                </tr>
				
   </tbody>
       
			
			
	
  </table>
  <!--New Table End-->
	   <table border="0" cellspacing="0" cellpadding="0" width="100%" style="margin-bottom:0; margin-top:0;">
			  <tbody>
			  <tr>
					<td colspan="8" style="padding-top:5px; text-align: center; background-color: #cee1ff; font-weight: bold; padding-bottom:5px; padding-left:5px; padding-right:5px; border: solid 1px #646464;">Total Invoice amount In words </td>
                    <td colspan="6" style="padding-top:10px; padding-bottom:10px; background-color: #cee1ff; padding-left:15px; padding-right:15px; border: solid 1px #646464;"><b>Total Amount before Tax</b> </td>
                    
					<td colspan="6" style="font-size:11px; padding-top:10px; text-align: right; padding-bottom:10px; padding-left:15px; padding-right:15px; border: solid 1px #646464;"><b><?php  echo $_billDetail->getBasicAmount();?> </b></td>
                </tr>
<tr>
                	<td colspan="8" rowspan="6" valign="top">
					<?php $taxes = Tax::find()->where(['status'=>Tax::STATUS_ACTIVE])->all();?>
<?php if($taxes){?>
					<table border="0" cellspacing="0" cellpadding="0" width="100%" style="margin-bottom:0; margin-top:0;">  
					<tbody>
					<tr>
												<th style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;">Basic Value</th>
												<th style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;">Title</th>
												<th style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;">HRN Code</th>
												
												<th style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;">CGST Amount</th>
												
												<th style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;">SGST Amount</th>
												
												<th style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;">CESS Amount</th>
												<?php if($billDetail_->getGSTTrue($poid)== false){?>	
												<th style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;">IGST Amount</th>
												<?php } ?>
											</tr>
<?php
	
	$total_cgst = '0.00';
	$total_sgst = '0.00';
	$total_cess = '0.00';
	$total_igst = '0.00';
	?>
<?php


	foreach ( $taxes as $tax ) {
		$total_cgst = $total_cgst + $tax->getPB2bBillCgstAmount ( $poid, $tax->id, 'cgst_amt' );
		$total_sgst = $total_sgst + $tax->getPB2bBillCgstAmount ( $poid, $tax->id, 'sgst_amt' );
		$total_cess = $total_cess + $tax->getPB2bBillCgstAmount ( $poid, $tax->id, 'cess_amt' );
		$total_igst = $total_igst + $tax->getPB2bBillCgstAmount ( $poid, $tax->id, 'igst_amt' );
		?>

<tr>
												<td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"><?php echo $tax->getPB2bBillAmount($poid,$tax->id);?></td>
												<td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"><?php echo $tax->title;?></td>
												<td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"><?php echo $tax->hrn_code;?></td>
											
												<td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;">
												<?php echo $tax->getPB2bBillCgstAmount($poid,$tax->id,'cgst_amt');?></td>
												
												<td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"><?php echo $tax->getPB2bBillCgstAmount($poid,$tax->id,'sgst_amt');?></td>
											
												<td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;">
												<?php


												echo $tax->getPB2bBillCgstAmount($poid,$tax->id,'cess_amt');?></td>
											<?php if($billDetail_->getGSTTrue($poid)== false){?>	
												<td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"><?php echo $tax->getPB2bBillCgstAmount($poid,$tax->id,'igst_amt');?></td>
											<?php } ?>
											</tr>

<?php }?>
					
				
				 </tbody>
				</table>
<?php } ?>
					</td>
                    
					<td colspan="6" style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"><b>Add: CGST</b></td>
				
                    <td colspan="8" style="font-size:10px; padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; text-align: right; border: solid 1px #646464;"><?php echo $_billDetail->getTotalCgstAmt();  ?></td>
                <!--</tr>-->
				<tr>
                	
					<td colspan="6" style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"><b>Add: SGST</b></td>
					<!--<td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"></td>
                    <td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"></td>
					<td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"></td>-->
                    <td colspan="8" style="font-size:10px; padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; text-align: right; border: solid 1px #646464;"><?php echo $_billDetail->getTotalSgstAmt();  ?></td>
                </tr>
				<tr>
                	
					<td colspan="6" style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"><b>Add: CESS</b></td>
					<!--<td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"></td>
                    <td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"></td>
					<td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"></td>-->
                    <td colspan="8" style="font-size:10px; padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; text-align: right; border: solid 1px #646464;"><?php echo $_billDetail->getTotalCessAmt(); ?></td>
                </tr>
				
				<tr>
                	
					<td colspan="6" style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"><?php if($billDetail_->getGSTTrue($poid)== false){?>	<b>Add: IGST</b> <?php } ?></td>
					<!--<td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"></td>
                    <td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"></td>
					<td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"></td>-->
                    <td colspan="8" style="font-size:10px; padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; text-align: right; border: solid 1px #646464;"><?php if($billDetail_->getGSTTrue($poid)== false){  echo $_billDetail->getTotalIgstAmt(); } ?></td>
                </tr>
				<tr>
                	
					<td colspan="6" style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"><b>Total Tax Amount</b></td>
					<!--<td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"></td>
                    <td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"></td>
					<td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"></td>-->
                    <td colspan="8" style="font-size:10px; padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; text-align: right; border: solid 1px #646464;"><?php echo $_billDetail->getTotalTaxAmt();  ?></td>
                </tr>
				<tr>
                	
					<td colspan="6" style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"><b>Total Amount after Tax:</b></td>
					<!--<td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"></td>
                    <td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"></td>
					<td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"></td>-->
                    <td colspan="8" style="font-size:11px; padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; text-align: right; border: solid 1px #646464;"><b>
					<?php //echo round($_billDetail->getTotalTaxAmt() + $_billDetail->getTotalNetAmt());?>
					<?php echo round( $_billDetail->getTotalNetAmt());?>
					</b></td>
                </tr>
			
			<tr>
					<td colspan="5" style="padding-top:5px; text-align: center; background-color: #cee1ff; font-weight: bold; padding-bottom:5px; padding-left:5px; padding-right:5px; border: solid 1px #646464;">Bank Details</td>
                    <td colspan="3" style="padding-top:10px; padding-bottom:10px; background-color: #cee1ff; padding-left:15px; padding-right:15px; border: solid 1px #646464;">Attachment  </td>
					<td colspan="6" style="padding-top:10px; padding-bottom:10px; background-color: #cee1ff; padding-left:15px; padding-right:15px; border: solid 1px #646464;">GST on Reverse Charge  </td>
					<td colspan="6" style="padding-top:10px; text-align: right; padding-bottom:10px; padding-left:15px; padding-right:15px; border: solid 1px #646464;">0</td>
                </tr>
				<tr>
					<td colspan="5" style="padding-top:10px; text-align: left; font-weight: bold; padding-bottom:10px; padding-left:15px; padding-right:15px; border: solid 1px #646464;">HDFC BANK</td>
                    <td colspan="3" rowspan="3" style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464; text-align: center;">NA  </td>
					<td colspan="12" style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464; text-align: center;">Ceritified that the particulars given above are true and correct  </td>
                </tr>
				<tr>
					<td colspan="5" style="padding-top:10px; text-align: left; font-weight: bold; padding-bottom:10px; padding-left:15px; padding-right:15px; border: solid 1px #646464;">Bank A/C: 50200020998900 </td>
					<td colspan="2" style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"></td>
                    <td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"></td>
					<td colspan="2"  style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"></td>
                    <td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"></td>
					<td colspan="2"  style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"></td>
                    <td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"></td>
					<td colspan="2"  style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"></td>
                    <td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"></td>
                </tr>
				<tr>
					<td colspan="5" style="padding-top:10px; text-align: left; font-weight: bold; padding-bottom:10px; padding-left:15px; padding-right:15px; border: solid 1px #646464;">Bank IFSC:HDFC0000107</td>
					<td colspan="12" style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464; text-align: center;">For _____________________________________  </td>
                </tr>
				<tr>
					<td colspan="5" rowspan="2" height="50" style="padding-top:10px; text-align: left; font-weight: bold; padding-bottom:10px; padding-left:15px; padding-right:15px; border: solid 1px #646464; text-align: center;">Terms & conditions </td>
					<td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"></td>
                    <td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"></td>
					<td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"></td>
					<td colspan="12" rowspan="3" style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"></td>
                </tr>
				<tr>
					<td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"></td>
                    <td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"></td>
					<td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;"></td>
                </tr>
				<tr>
					<td colspan="5" rowspan="2" height="50" style="padding-top:10px; text-align: left; font-weight: bold; padding-bottom:10px; padding-left:15px; padding-right:15px; border: solid 1px #646464; text-align: center;">NA </td>
					<td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;">&nbsp;</td>
                    <td style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;">&nbsp;</td>
					<td   style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464;">&nbsp;</td>
                </tr>
				<tr>
					<td colspan="3" style="padding-top:10px; text-align: center; padding-bottom:10px; padding-left:15px; padding-right:15px; border: solid 1px #646464; font-weight: bold;">Company Seal</td>
                    <td colspan="12" style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464; text-align: center; font-weight: bold;">Authorised signatory</td>
                </tr>
				
				<tr>
					<td colspan="20" style="padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:5px; border: solid 1px #646464; font-style: italic; ">Note 1: Invoice No. should not contoin more than 16 characters</td>
                </tr>
            </tbody>
          </table>
		
          <!-- Table End-->
</div>
</body>
</html>
