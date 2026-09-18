<?php
/**
 * Ported from protected/views/item/_pdf.php.
 */


?>
<div style="width: 100%; font-size: 10px; line-height: normal; font-family: Arial, sans-serif;">
  <!-- Header -->
  <div style="margin-bottom: 5px;">
    <div>Retail Bill- ORIGINAL</div>
    <div>AUTO SERVICE STATION</div>
    <div>In & Out</div>
    <div>Sector 4 Petrol Pump Chandigarh</div>
  </div>

  <!-- Bill Information -->
  <div style="margin-bottom: 5px;">
    <div>GST No: <?php echo isset($gstNumber) ? $gstNumber : '04ABZPS6311A1Z0'; ?></div>
    <div>FSSAI No: <?php echo isset($fssaiNumber) ? $fssaiNumber : '13014001000560'; ?></div>
    <?php 
      // calculate this date
      $currentYear = date('Y');
      $currentMonth = date('m');
      if ($currentMonth > 3) {
          $nextYear = $currentYear + 1;
          $fiscalYear = "{$currentYear}-{$nextYear}";
      } else {
          $previousYear = $currentYear - 1;
          $fiscalYear = "{$previousYear}-{$currentYear}";
      }
    ?>
    <div>Bill No : <?php echo isset($billNo) ? 'Gst ' . $fiscalYear . '/' . $billNo : ''; ?></div>
    <div>Customer Name : <?php echo isset($customer) ? $customer->name : ''; ?></div>
    <div>User : <?php echo isset($username) ? $username : ''; ?></div>
    <div>Date : <?php echo date('j/n/Y g:i:s A'); ?></div>
  </div>

  <div style="border-top: 1px dashed #000; margin: 3px 0;"></div>

  <!-- Items Header -->
  <div style="font-size: 10px; margin-bottom: 3px;">
    <table style="width: 100%; border-collapse: collapse; font-size: 10px;">
      <tr>
        <td style="text-align: left; width: 30%;">Name of the product</td>
        <td style="text-align: center; width: 15%;">QTY.</td>
        <td style="text-align: right; width: 15%;">MRP.......</td>
        <td style="text-align: right; width: 15%;">Rate....</td>
        <td style="text-align: right; width: 15%;">Total...</td>
      </tr>
    </table>
  </div>

  <!-- Items List -->
  <?php if (isset($billData['item_details']) && is_array($billData['item_details'])): ?>
    <?php foreach ($billData['item_details'] as $item): ?>
      <div style="margin-bottom: 2px;">
        <table style="width: 100%; border-collapse: collapse; font-size: 10px;">
          <tr>
            <td style="text-align: left; width: 30%;">
              <?php echo isset($item['product_name']) ? $item['product_name'] : 'PRODUCT NAME'; ?></td>
            <td style="text-align: center; width: 15%;"><?php echo isset($item['qty']) ? $item['qty'] : '1'; ?></td>
            <td style="text-align: right; width: 15%;">
              <?php echo isset($item['mrp']) ? number_format($item['mrp'], 2) : '0.00'; ?></td>
            <td style="text-align: right; width: 15%;">
              <?php echo isset($item['sale_rate']) ? number_format($item['sale_rate'], 2) : '0.00'; ?></td>
            <td style="text-align: right; width: 15%;">
              <?php echo isset($item['total_amount']) ? number_format($item['total_amount'], 2) : '0.00'; ?></td>
          </tr>
        </table>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <div style="border-top: 1px dashed #000; margin: 3px 0;"></div>

  <!-- Tax Details Table -->
  <div style="font-size: 9px; margin-bottom: 3px;">
    <table style="width: 100%; border-collapse: collapse; font-size: 9px;">
      <tr>
        <td style="text-align: left; width: 12%;">HSN</td>
        <td style="text-align: center; width: 10%;">TAX%</td>
        <td style="text-align: center; width: 10%;">QTY</td>
        <td style="text-align: center; width: 10%;">UQC</td>
        <td style="text-align: right; width: 12%;">TXBLE</td>
        <td style="text-align: right; width: 12%;">CGST</td>
        <td style="text-align: right; width: 12%;">UTGST</td>
        <td style="text-align: right; width: 10%;">CESS</td>
        <td style="text-align: right; width: 12%;">TOTAL TX</td>
      </tr>
    </table>
  </div>

  <?php if (isset($billData['item_details']) && is_array($billData['item_details'])): ?>
    <?php 
    $totalQty = 0;
    $totalTaxable = 0;
    $totalCGST = 0;
    $totalUTGST = 0;
    $totalCESS = 0;
    $totalTax = 0;
    ?>
    <?php foreach ($billData['item_details'] as $item): ?>
      <?php
      $totalQty += isset($item['qty']) ? $item['qty'] : 0;
      $totalTaxable += isset($item['taxable_amount']) ? $item['taxable_amount'] : 0;
      $totalCGST += isset($item['cgst_amt']) ? $item['cgst_amt'] : 0;
      $totalUTGST += isset($item['sgst_amt']) ? $item['sgst_amt'] : 0;
      $totalCESS += isset($item['cess_amt']) ? $item['cess_amt'] : 0;
      $totalTax += isset($item['tax_amt']) ? $item['tax_amt'] : 0;
      ?>
      <div style="font-size: 9px; margin-bottom: 1px;">
        <table style="width: 100%; border-collapse: collapse; font-size: 9px;">
          <tr>
            <td style="text-align: left; width: 12%;"><?php echo isset($item['hsn_code']) ? $item['hsn_code'] : '00000000'; ?></td>
            <td style="text-align: center; width: 10%;">
              <?php echo isset($item['tax_percent']) ? $item['tax_percent'] : '0'; ?>%</td>
            <td style="text-align: center; width: 10%;">
              <?php echo isset($item['qty']) ? number_format($item['qty'], 1) : '0.0'; ?></td>
            <td style="text-align: center; width: 10%;"><?php echo isset($item['unit_name']) ? explode("-", $item['unit_name'])[0] : 'PCS'; ?></td>
            <td style="text-align: right; width: 12%;">
              <?php echo isset($item['taxable_amount']) ? number_format($item['taxable_amount'], 2) : '0.0'; ?></td>
            <td style="text-align: right; width: 12%;">
              <?php echo isset($item['cgst_amt']) ? number_format($item['cgst_amt'], 2) : '0.00'; ?></td>
            <td style="text-align: right; width: 12%;">
              <?php echo isset($item['sgst_amt']) ? number_format($item['sgst_amt'], 2) : '0.00'; ?></td>
            <td style="text-align: right; width: 10%;">
              <?php echo isset($item['cess_amt']) ? number_format($item['cess_amt'], 2) : '0.00'; ?></td>
            <td style="text-align: right; width: 12%;">
              <?php echo isset($item['tax_amt']) ? number_format($item['tax_amt'], 2) : '0.00'; ?></td>
          </tr>
        </table>
      </div>
    <?php endforeach; ?>
    
    <!-- Totals Row -->
    <div style="font-size: 9px; margin-bottom: 1px; border-top: 1px dashed #000; padding-top: 1px;">
      <table style="width: 100%; border-collapse: collapse; font-size: 9px;">
        <tr style="font-weight: bold;">
          <td style="text-align: left; width: 12%;">Totals</td>
          <td style="text-align: center; width: 10%;"></td>
          <td style="text-align: center; width: 10%;"><?php echo number_format($totalQty, 0); ?></td>
          <td style="text-align: center; width: 10%;"></td>
          <td style="text-align: right; width: 12%;"><?php echo number_format($totalTaxable, 2); ?></td>
          <td style="text-align: right; width: 12%;"><?php echo number_format($totalCGST, 2); ?></td>
          <td style="text-align: right; width: 12%;"><?php echo number_format($totalUTGST, 2); ?></td>
          <td style="text-align: right; width: 10%;"><?php echo number_format($totalCESS, 2); ?></td>
          <td style="text-align: right; width: 12%;"><?php echo number_format($totalTax, 2); ?></td>
        </tr>
      </table>
    </div>
  <?php endif; ?>

  <div style="border-top: 1px dashed #000; margin: 3px 0;"></div>

  <!-- Totals Section -->
  <div style="font-size: 10px; margin-bottom: 3px;">
    <div><b>Total Sale Value</b> : <?php echo isset($billData['totalSaleValue']) ? $billData['totalSaleValue'] : '0.00'; ?></div>
    <div><b>NET Amount</b> : <?php echo isset($billData['netAmount']) ? $billData['netAmount'] : '0.00'; ?></div>
    <div><b>Total Saving</b> : <?php echo isset($billData['saving']) ? $billData['saving'] : '0.00'; ?></div>
  </div>

  <!-- Footer -->
  <div style="font-size: 10px; text-align: left;">
    <div>PLEASE CHECK EXPIRY, MRP, BILL</div>
    <div>Shop Online on SOULBOWL.IN</div>
    <div>BILL AMOUNT INCLUSIVE OF UTGST & CGST</div>
    <div>PH : <?php echo isset($phone) ? $phone : '0172-2740064'; ?>
      M:<?php echo isset($mobile) ? $mobile : '9814740064'; ?></div>
    <div style="font-weight: bold;">GOODS ONCE SOLD WILL NOT BE TAKEN BACK</div>
  </div>
</div>