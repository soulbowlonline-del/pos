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
  $taxes = Tax::model()->findAllByAttributes(array('status'=>Tax::STATUS_ACTIVE));
  ?>
  <?php foreach ($taxes as $tax) {

    $total_cgst = $total_cgst + $model->getChangeItemReturnCgstAmount($tax->id, 'cgst_amt', $return_item_ids);
    $total_sgst = $total_sgst + $model->getChangeItemReturnCgstAmount($tax->id, 'sgst_amt', $return_item_ids);
    $total_cess = $total_cess + $model->getChangeItemReturnCgstAmount($tax->id, 'cess_amt', $return_item_ids);
    $total_igst = $total_igst + $model->getChangeItemReturnCgstAmount($tax->id, 'igst_amt', $return_item_ids);
    ?>

    <tr>
      <td><?php echo $model->getChangeItemReturnAmount($tax->id, $return_item_ids); ?></td>
      <td><?php echo $tax->title; ?></td>
      <td><?php echo $tax->hrn_code; ?></td>
      <td><?php echo $tax->tax_val1; ?></td>
      <td><?php echo $model->getChangeItemReturnCgstAmount($tax->id, 'cgst_amt', $return_item_ids) ?></td>
      <td><?php echo $tax->tax_val2; ?></td>
      <td><?php echo $model->getChangeItemReturnCgstAmount($tax->id, 'sgst_amt', $return_item_ids) ?></td>
      <td><?php echo $tax->tax_val3; ?></td>
      <td><?php echo $model->getChangeItemReturnCgstAmount($tax->id, 'cess_amt', $return_item_ids) ?></td>
      <td><?php echo $tax->tax_val4; ?></td>
      <td><?php echo $model->getChangeItemReturnCgstAmount($tax->id, 'igst_amt', $return_item_ids) ?></td>
    </tr>

  <?php } ?>
  <tr>
    <td></td>
    <td></td>
    <td></td>
    <td>Total Cgst</td>
    <td><b><?php echo $total_cgst; ?></b></td>
    <td>Total Sgst</td>
    <td><b><?php echo $total_sgst; ?></b></td>
    <td>Total Cess</td>
    <td><b><?php echo $total_cess; ?></b></td>
    <td>Total Igst</td>
    <td><b><?php echo $total_igst; ?></b></td>
  </tr>
</table>