<html>
<body >
<!-- Hidden Preheader -->
<style>

.btn {
	-moz-user-select: none;
	background-image: none;
	border: 1px solid transparent;
	border-radius: 4px;
	cursor: pointer;
	display: inline-block;
	font-size: 14px;
	font-weight: 400;
	line-height: 1.42857;
	margin-bottom: 0;
	padding: 6px 12px;
	text-align: center;
	vertical-align: middle;
	white-space: nowrap;
}
.btn-success {
	background-color: #3c8dbc;
	border-color: #367fa9;
	color:#ffffff;
}
</style>
<table align="center" bgcolor="#F8F8F8" border="0" cellpadding="0"
		cellspacing="0" style="background-color: #f8f8f8; width: 100%;">
		<tbody>
		<tr>
		<td align="center" class="full" style="padding: 15px;"><table
		align="center" bgcolor="#FFFFFF" border="0" cellpadding="0"
				cellspacing="0" class="responsive-table"
						style="background-color: #ffffff; width: 600px;">
						<!-- Inner Containter Table -->
						<tbody>
						<tr>
						<td class="full"
								style="border: 1px solid #e8e8e8; font-family: Helvetica, Arial, sans-serif; color: #000000;"><table
								align="center" bgcolor="#FFFFFF" border="0" cellpadding="0"
										cellspacing="0"
												style="border-collapse: collapse; background-color: #ffffff; width: 100%;">
												<tbody>


												<tr>
												<td align="left"
														style="padding: 5px; margin: 5px; display: block; font-family: Helvetica, Arial, sans-serif; font-size: 14px; line-height: 24px;">
														<h2
														style="font-size: 24px; text-transform: uppercase; color: #080024;">
															
														<center>
														<h3>Welcome to Auto Service Station,
														<br/>In & Out Store,
														<br/>Sector 4 Petrol Pump Chandigarh </h3>
														</center>
															
														</h2>
														<?php $vendor = Vendor::model()->findByAttributes(array('id'=>$pomodel->vendor_id));
														if($vendor){
															$user = User::model()->findByAttributes(array('id'=>$vendor->create_user_id));
														}
														?>
														<p
														style="font-size: 18px; color: #333333; line-height: 32px; word-break: break-all;">
														Dear <?php 
														if($vendor){
														echo $vendor->name;
														}
														$link = Yii::app()->createAbsoluteUrl('purchaseOrder/pdf').'?id='.base64_encode($pomodel->id);
														?>,</p>
														<p
															style="font-size: 14px; color: #333333; line-height: 32px; ">
															A purchase order <?php echo $pomodel->id;?> is approved :</p>
      

                          <p>Please find Attachment for Purchase Order.</p>


				
														<p
															style="font-size: 18px; color: #333333; line-height: 32px; word-break: break-all;">
															Thanks & Regards, </p>
															<p
															style="font-size: 14px; color: #333333; line-height: 32px; ">Purchase Manager<br />Auto Service Station,<br />In & Out Store
															<br />Sector 4 Petrol Pump Chandigarh. <br />Landline : 0172-2740064. <br />For any queries please email us on : autoservicestationsec4@gmail.com<br />Or call us on 0172-2740064. 
														</p>
													</td>
												</tr>
												<tr>
													<td align="center"
														style="padding: 10px 0; margin: 5px 0 0; display: block; background-color: #080024; font-family: Helvetica, Arial, sans-serif; font-size: 14px; color: #fff; line-height: 24px;"><div
															id="Footer" class="mktEditable"
															style="color: #fff; font-size: 13px; line-height: 22px;">Copyright &copy; <?php echo date('Y')?> <?php echo Yii::app()->params['company'];?>. All rights reserved. </div></td>
												</tr>

												<!-- End Main Content -->
											</tbody>
										</table></td>
								</tr>

								<!-- End Inner Container Table -->
							</tbody>
						</table></td>
				</tr>
			</tbody>
		</table>
		<!-- End Outer Table -->

	</center>
</body>
</html>

