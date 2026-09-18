<?php
/**
 * Ported from protected/views/purchaseBillDetail/_tax.php.
 */

use app\models\MrsDetail;
use app\models\Tax;
?>
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
<?php $total_cgst = '0.00';
$total_sgst = '0.00';
$total_cess = '0.00';
$total_igst = '0.00';
?>
<?php $taxes = Tax::findAll(['status'=>MrsDetail::STATUS_PENDING]);?>
<?php foreach($taxes as $tax){
	$total_cgst = $total_cgst + $tax->getChangePBillCgstAmount($poid,$tax->id,'cgst_amt',$detailid,$tax_id,$purchase_bill_ids);
	$total_sgst = $total_sgst + $tax->getChangePBillCgstAmount($poid,$tax->id,'sgst_amt',$detailid,$tax_id,$purchase_bill_ids);
	$total_cess = $total_cess +  $tax->getChangePBillCgstAmount($poid,$tax->id,'cess_amt',$detailid,$tax_id,$purchase_bill_ids);
	$total_igst = $total_igst + $tax->getChangePBillCgstAmount($poid,$tax->id,'igst_amt',$detailid,$tax_id,$purchase_bill_ids);
?>

<tr>
<td><?php echo $tax->getChangePBillAmount($poid,$tax->id,'cgst_amt',$detailid,$tax_id,$purchase_bill_ids);?></td>
<td><?php echo $tax->title;?></td>
<td><?php echo $tax->hrn_code;?></td>
<td><?php echo $tax->tax_val1;?></td>
<td><?php echo $tax->getChangePBillCgstAmount($poid,$tax->id,'cgst_amt',$detailid,$tax_id,$purchase_bill_ids);?></td>
<td><?php echo $tax->tax_val2;?></td>
<td><?php echo $tax->getChangePBillCgstAmount($poid,$tax->id,'sgst_amt',$detailid,$tax_id,$purchase_bill_ids);?></td>
<td><?php echo $tax->tax_val3;?></td>
<td><?php echo $tax->getChangePBillCgstAmount($poid,$tax->id,'cess_amt',$detailid,$tax_id,$purchase_bill_ids);?></td>
<td><?php echo $tax->tax_val4;?></td>
<td><?php echo $tax->getChangePBillCgstAmount($poid,$tax->id,'igst_amt',$detailid,$tax_id,$purchase_bill_ids);?></td>
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