<style>
body {
	-webkit-print-color-adjust: exact;
	font-family: "Calibri";
	font-size:12px;
}
table {
	margin: 5px 0px;
}
td, th {
	border: solid 1px #EAEAEA;
}

.border-none {border: none !important;}
.heading-h1 {  display:block; margin-top:5px; margin-bottom:5px; }
.margin-bottom { margin-bottom:10px !important; }
@media print {
body {
	-webkit-print-color-adjust: exact;
	-moz-print-color-adjust: exact;
}
table {
	margin: 5px 0px;
}

}
</style>



<div class="main" style=" width:95%; margin:0 auto;">
<!--New Table Start-->
  <table border="0" cellspacing="0" cellpadding="0" width="100%" style="margin-bottom:5px;">
    <tbody>
      <tr>
        <td colspan="2"  align="left" style="text-align:left; margin-bottom:5px;" class="border-none"><img src="http://61.2.241.71/autoservice/email-logo.png" style="width:314px; height:110px;"></td>
      </tr>
      <tr><td height="10" class="border-none">&nbsp;</td></tr>
      <tr>
        <td class="border-none"><h1 style="font-weight:bold; font-size:12px;" class="heading-h1">Hello, <?php echo $order->getCustomerName()?></h1>
          <p style="height:5px;">&nbsp;</p>
          <p style="margin-bottom:0; padding-bottom:0;"> Thank you for your order from Soul Bowl. Once your package is ready we will let you know via phone/email/sms. You can check the status of your order by <a href="#">logging into your account</a>. If you have any questions about your order please contact us at <a href="#">cs@soulbowl.in</a> or call us at 91 9914770022 Monday - Sunday, 10am - 8pm IST. </p>
          <p style="margin-bottom:0; padding-bottom:0;">Your order confirmation is below. Thank you again for your business.</p>
          </td>
      </tr>
    </tbody>
  </table>
  <!--New Table End-->
  <!--New Table Start-->
  <table border="0" cellspacing="0" cellpadding="0" width="100%" style="margin-bottom:5px;">
    <tbody>
    <tr>
        <td colspan="3" class="border-none margin-bottom"><h2 style="font-size:12px;">Your Order #<?php echo $order->order_id;?> (placed on <?php echo $order->order_date;?>)</h2> </td> 
      </tr>
      <tr><td height="10" class="border-none">&nbsp;</td></tr>
      <tr>
        <td width="49%" style="border:1px solid #EAEAEA !important; vertical-align:top;">
        <!--New Table Start-->
          <table border="0" cellspacing="0" cellpadding="0" width="100%" style="margin-bottom:0; margin-top:0;">
          	<tr>
            	<td style="background:#EAEAEA; font-weight:bold; text-align:left; padding-top:5px; padding-bottom:5px;  padding-left:15px; padding-right:15px;">Billing Information:</td>
            </tr>
            <tbody>
				<tr>
                	<td style="padding:5px;">
                     <?php echo $order->getCustomerName();?>  <br>
                   <?php echo $order->street;?> <br>
                    <?php echo $order->city;?> , <?php echo $order->zip_code;?> <br>
                    <?php echo $order->country;?> <br>
                    T:  <?php echo $order->telephone;?> <br> 
                    M:  <?php echo $order->mobile;?> <br>
                    </td>
                </tr>
            </tbody>
          </table> 
           <!--New Table End--> 
      </td>
      <td width="2%">&nbsp;</td>
        <td width="49%" style="border:1px solid #EAEAEA !important; vertical-align:top;">
          <!--New Table Start-->
          <table border="0" cellspacing="0" cellpadding="0" width="100%" style="margin-bottom:0; margin-top:0;">
			 <tr>
            	<td style="background:#EAEAEA; font-weight:bold; text-align:left; padding-top:5px; padding-bottom:5px;  padding-left:15px; padding-right:15px;">Payment Method:</td>
            </tr>
            <tbody>
				<tr>
                	<td style="padding:5px;">
                      <p style="margin:0; padding:0; font-weight:bold;"><?php echo $order->payment_method;?></p>
                    </td>
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
  <table border="0" cellspacing="0" cellpadding="0" width="100%" style="margin-bottom:5px;">
    <tbody>
      <tr>
        <td width="49%" style="border:1px solid #EAEAEA !important; vertical-align:top;">
        <!--New Table Start-->
          <table border="0" cellspacing="0" cellpadding="0" width="100%" style="margin-bottom:0; margin-top:0;">
          	<tr>
            	<td style="background:#EAEAEA; font-weight:bold; text-align:left; padding-top:5px; padding-bottom:5px;  padding-left:15px; padding-right:15px;">Shipping Information:</td>
            </tr>
            <tbody>
				<tr>
                	<td style="padding:5px;">
                     <?php echo $order->getCustomerName();?>  <br>
                   <?php echo $order->street;?> <br>
                    <?php echo $order->city;?> , <?php echo $order->zip_code;?> <br>
                    <?php echo $order->country;?> <br>
                    T:  <?php echo $order->telephone;?> <br> 
                    M:  <?php echo $order->mobile;?> <br>
                    </td>
                </tr>
            </tbody>
          </table> 
           <!--New Table End--> 
      </td>
       <td width="2%">&nbsp;</td>
        <td width="49%" style="border:1px solid #EAEAEA !important; vertical-align:top;">
          <!--New Table Start-->
          <table border="0" cellspacing="0" cellpadding="0" width="100%" style="margin-bottom:0; margin-top:0;">
			 <tr>
            	<td style="background:#EAEAEA; font-weight:bold; text-align:left; padding-top:5px; padding-bottom:5px;  padding-left:15px; padding-right:15px;">Shipping Method:</td>
            </tr>
            <tbody>
				<tr>
                	<td style="padding:5px;">
                      <?php echo $order->delivery_method;?>  
                    </td>
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
  <table border="0" cellspacing="0" cellpadding="0" width="100%" style="margin-bottom:5px;">
    <tbody>
      <tr>
        <td colspan="2" width="100%" style="border:1px solid #EAEAEA !important; vertical-align:top;">
          <!--New Table Start-->
          <table border="0" cellspacing="0" cellpadding="0" width="100%" style="margin-bottom:0; margin-top:0;">
			 <tr>
            	<td style="background:#EAEAEA; font-weight:bold; text-align:left; padding-top:5px; padding-bottom:5px;  padding-left:5px; padding-right:15px;">Delivery Slot Details:</td>
            </tr>
            <tbody>
				<tr>
                	<td style="padding:5px;">
                      <span style="font-weight:bold;">SSN:</span>  <?php echo $order->delivery_slot;?>
                    </td>
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
  <table border="0" cellspacing="0" cellpadding="0" width="100%" style="margin-bottom:5px;">
    <tbody>
      <tr>
        <td colspan="2" width="100%" style="border:1px solid #EAEAEA !important; vertical-align:top;">
          <!--New Table Start-->
          <table border="0" cellspacing="0" cellpadding="0" width="100%" style="margin-bottom:0; margin-top:0;">
			 <tr>
            	<td style="background:#EAEAEA; font-weight:bold; text-align:left; padding-top:5px; padding-bottom:5px;  padding-left:15px; padding-right:15px;">Delivery Date:</td>
            </tr>
            <tbody>
				<tr>
                	<td style="padding:5px;">
                      <span style="font-weight:bold;">Delivery Date:</span> <?php echo date('Y-m-d',strtotime($order->order_date));?>
                    </td>
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
  <table border="0" cellspacing="0" cellpadding="0" width="100%" style="margin-bottom:5px;">
    <tr style="background:#EAEAEA; font-weight:bold;">
            	<td style="text-align:left; padding-top:5px; padding-bottom:5px;  padding-left:15px; padding-right:15px;">Item</td>
                <td style="text-align:left; padding-top:5px; padding-bottom:5px;  padding-left:15px; padding-right:15px;">Sku</td>
                <td style="text-align:left; padding-top:5px; padding-bottom:5px;  padding-left:15px; padding-right:15px;">Qty</td>
                <td style="text-align:left; padding-top:5px; padding-bottom:5px;  padding-left:15px; padding-right:15px;">Subtotal</td>
            </tr>
           
             <?php $orderitems = OnlineOrderItem::model ()->findAllByAttributes(array('order_id'=>$order->id));?>
               <?php 
               $total = 0;
               if($orderitems){
               foreach($orderitems as $orderitem){
               	$total = $total + $orderitem->total;
               	?>
               	<tr>
                	<td style="padding-top:5px; padding-bottom:5px;  padding-left:15px; padding-right:15px; "><?php echo $orderitem->name;?></td>
                    <td style="padding-top:5px; padding-bottom:5px;  padding-left:15px; padding-right:15px; "><?php echo $orderitem->product_code;?></td>
                    <td style="padding-top:5px; padding-bottom:5px;  padding-left:15px; padding-right:15px; "><?php echo $orderitem->qty;?></td>
                    <td style="padding-top:5px; padding-bottom:5px;  padding-left:15px; padding-right:15px; ">Rs <?php echo $orderitem->total;?></td>
                </tr>
               	<?php 
               }
               }?>
				
                <?php $total = round($total);
                $shipping_charges = $order->grand_total - $total;
                $shipping_charges = round($shipping_charges);
                ?>
                <tr>
                  <td colspan="2" style="padding-top:5px; padding-bottom:5px; padding-left:15px; padding-right:15px;"></td>
                  <td style="padding-top:5px; padding-bottom:5px; padding-left:15px; padding-right:15px; text-align:right; font-size:12px;">SUBTOTAL</td>
                  <td style="padding-top:5px; padding-bottom:5px; padding-left:15px; padding-right:15px; font-size:12px;">Rs <?php echo $total;?> </td>
                </tr>
                 <tr>
                  <td colspan="2" style="padding-top:5px; padding-bottom:5px; padding-left:15px; padding-right:15px;"></td>
                  <td style="padding-top:5px; padding-bottom:5px; padding-left:15px; padding-right:15px; text-align:right; font-size:12px;">Shipping &amp; Handling </td>
                  <td style="padding-top:5px; padding-bottom:5px; padding-left:15px; padding-right:15px; font-size:12px;">Rs <?php echo $shipping_charges;?> </td>
                </tr>
                 <tr>
                  <td colspan="2" style="padding-top:5px; padding-bottom:5px; padding-left:15px; padding-right:15px;"></td>
                  <td style="padding-top:5px; padding-bottom:5px; padding-left:15px; padding-right:15px; font-weight:bold; text-align:right; font-size:12px;">Grand Total</td>
                  <td style="padding-top:5px; padding-bottom:5px; padding-left:15px; padding-right:15px; font-weight:bold; font-size:12px;">Rs <?php echo $order->grand_total;?></td>
                </tr>
           
          <!-- Table End-->
         
  </table>
  <!--New Table End-->
  <!--New Table Start-->
  <table border="0" cellspacing="0" cellpadding="0" width="100%" style="margin-bottom:5px;">
    <tfoot>
      <tr>
        <td  align="center" colspan="2" style="background:#EAEAEA; text-align:center; padding-top:5px; padding-bottom:5px;  padding-left:15px; padding-right:15px;">
        	<p style="margin:0; padding:0;">Thank you, <span style="font-weight:bold;">&copy; 2020 SOUL BOWL. All Rights Reserved</span></p>
         </td>
      </tr>
    </tfoot>
  </table>
  <!--New Table End-->
</div>

