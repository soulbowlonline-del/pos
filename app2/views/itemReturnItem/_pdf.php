<?php
/**
 * Ported from protected/views/itemReturnItem/_pdf.php.
 */

use app\models\ItemReturn;
use app\models\ItemReturnItem;
use app\models\Outlet;
use app\models\User;
use app\models\Vendor;
?>
<style>
  body {
    -webkit-print-color-adjust: exact;
    /*font-family:Segoe, "Segoe UI", "DejaVu Sans", "Trebuchet MS", Verdana, sans-serif;*/
    font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
    font-size: 12px;
  }

  @media print {
    body {
      -webkit-print-color-adjust: exact;
      -moz-print-color-adjust: exact;
    }



    table.print-friendly tr td,
    table.print-friendly tr th {

      padding: 2px;
    }

    table.print-friendly tr th {
      background: #747474;
    }


  }

  table {
    border-spacing: 0;
    border-collapse: collapse;
  }

  .table-bordered>tbody>tr>td,
  .table-bordered>tbody>tr>th,
  .table-bordered>tfoot>tr>td,
  .table-bordered>tfoot>tr>th,
  .table-bordered>thead>tr>td,
  .table-bordered>thead>tr>th {
    border: 1px solid #ddd;
  }
</style>


<!--<div style="margin:0 auto 10px auto; text-align: center;">
  <input type="button" value="Print" class="no-print" onclick="window.print();" />
</div>-->
<table border="0" cellspacing="0" cellpadding="0" width="100%" style="margin-bottom:5px;">
  <tbody>
    <tr>
      <td colspan="4" align="center">
        <h4 style="margin:0; padding:0; font-size:20px;">Item Return Order</h4> <br>

      </td>
    </tr>
    <?php

    $vendor = Vendor::findOne($vendor_id);
    $outlet = Outlet::findOne($outlet_id);
    // $user = User::findOne( $po->create_user_id );
    ?>
    <tr>
      <td>

        <strong>Buyer Details</strong><br>
        Auto Service Station<br>
        In&Out Store<br>
        Sector 4 Petrol Pump<br>
        Chandigarh - 160001<br>
        Mobile : 9814740064<br>
        GSTIN : 04ABZPS6311A1ZO<br>
        <!-- Order Placed by : <?php echo $user->full_name; ?><br>
                      Purchase Order No:  <?php echo $po->id; ?><br> -->
        <!-- Date :  <?php echo date('m/d/Y', strtotime($po->create_time)); ?><br>
                      </td> -->

      <td>&nbsp;

      </td>
      <td>&nbsp;

      </td>

      <td valign="top">



        <strong>Supplier Details</strong><br>

        <?php echo $vendor->name; ?><br>
        <?php echo $vendor->primary_address; ?><br>

        Mobile: <?php echo $vendor->contact_no; ?><br>
        GSIN: <?php echo $vendor->tax_no; ?><br>

      </td>

    </tr>
  </tbody>
</table>


<table border="1" cellspacing="0" cellpadding="0" width="100%" class="print-friendly">
  <?php $query = ItemReturnItem::find();
  // $criteria->with = 'item';
  // $criteria4->order = 'item.title asc';
  Criteria::compare($query, 'outlet_id', $outlet_id);
  Criteria::compare($query, 'vendor_id', $vendor_id);
  Criteria::compare($query, 'status', ItemReturn::STATUS_PENDING);
  // $criteria->addCondition('purchase_order_id =' . $po->id);
  $details = $query->all();
  $totalAmt = 0;
  // echo "<pre>"; print_r($details); die;
  if ($details) {
    $i = 0; ?>

    <tr>
      <th colspan="5" align="center"><strong>Detail</strong></th>
    </tr>
    <tr>
      <th align="center"><strong>S.No.</strong></th>
      <th align="center"><strong>Item</strong></th>
      <th align="center"><strong>Qty</strong></th>
      <th align="center"><strong>Sale Price</strong></th>
      <th align="center"><strong>Mrp</strong></th>
      <!-- <th align="center"><strong>Purchase Price</strong></th> -->

    </tr>
    <?php foreach ($details as $detail) {
      $i = $i + 1; 
      $totalAmt = $totalAmt + $detail->cgst_amt + $detail->sgst_amt + $detail->cess_amt + $detail->igst_amt + ($detail->qty * $detail->price);
      ?>
      <tr>
        <td><?php echo $i; ?></td>
        <td><?php echo isset($detail->item) ? $detail->item : ""; ?></td>
        <td><?php echo $detail->qty; ?></td>
        <td><?php echo $detail->price; ?></td>
        <td><?php echo $detail->mrp; ?></td>
        <?php /*?><td><?php echo $detail->price;?></td>*/ ?>

      </tr>
    <?php } ?>

  <?php } ?>

</table>
<table border="0" cellspacing="0" cellpadding="0" width="100%" style="margin-bottom:5px;">
  <?php if ($details) { ?>
    <tr>
      <td colspan="4" align="right">
        <table border="0" cellspacing="0" cellpadding="0" style="margin-bottom:5px;">
          <tbody>
            <tr>
              <td style="padding:0;"><strong>Bill Amount</strong></td>
              <td>:</td>
              <td><strong><?php echo round($totalAmt); ?></strong></td>
            </tr>
          </tbody>
        </table>
      </td>
    </tr>
  <?php } ?>
  <tr>
    <td style="font-size: 10px;" colspan="4">
      <b> Terms and Conditions: </b> <br>
      1. Payment will be made within 10 days of receipt of correct bill. <br>
      2. Payment will be made online via NEFT.<br>
      3. If Bill is not according to Purchase Order then Items maybe sent back and bill will be outstanding till
      corrected bill is not sent or items not received as per Purchase Order.<br>
      4. All disputes subject to Chandigarh Jurisdiction only. <br>

    </td>
  </tr>


  <tr>
    <td colspan="2">
      <table border="0" cellspacing="0" cellpadding="0" style="margin-bottom:5px;">
        <tbody>
          <tr>
            <td style="padding:0;">...............................................................................</td>
          </tr>
          <tr>
            <td style="padding:0; padding-top:5px;"><strong>Signature of Buyer</strong></td>
          </tr>
        </tbody>
      </table>
    </td>
    <td colspan="2" align="right">
      <table border="0" cellspacing="0" cellpadding="0" style="margin-bottom:5px;">
        <tbody>
          <tr>
            <td style="padding:0;">...............................................................................</td>
          </tr>
          <tr>
            <td style="padding:0; padding-top:5px;"><strong>Signature of Supplier</strong></td>
          </tr>
        </tbody>
      </table>
    </td>
  </tr>

  </tbody>
</table>