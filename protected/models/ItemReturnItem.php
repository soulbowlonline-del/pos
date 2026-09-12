<?php

/**
 * Company: ToXSL Technologies Pvt. Ltd. < www.toxsl.com >
 * Author : Shiv Charan Panjeta < shiv@toxsl.com >
 */
 
/**
 * @property integer $id
 * @property integer $item_id
 * @property integer $item_detail_id
 * @property string $mrp
 * @property double $price
 * @property string $sale_rate
 * @property integer $free
 * @property integer $qty
 * @property double $discount
 * @property double $discount_amt
 * @property double $discount1
 * @property double $discount_amt1
 * @property double $cgst_per
 * @property double $sgst_per
 * @property double $cess_per
 * @property double $cgst_amt
 * @property double $sgst_amt
 * @property double $cess_amt
 * @property double $igst_per
 * @property double $igst_amt
 * @property integer $tax_id
 * @property double $other_charge
 * @property string $total_amt
 * @property integer $vendor_id
 * @property integer $outlet_id
 * @property integer $status
 * @property integer $type_id
 * @property integer $return_id
 * @property string $create_time
 * @property integer $create_user_id
 * @property integer $updated_by
 */
Yii::import('application.models._base.BaseItemReturnItem');
class ItemReturnItem extends BaseItemReturnItem
{
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
	public function getGstTrue($vendor_id,$outlet_id){
		$gst = true;
		if($vendor_id !='' && $outlet_id != ''){
			$vendor = Vendor::model()->findByPk($vendor_id);
			if($vendor){
				$outlet = Outlet::model()->findByPk($outlet_id);
				
				if($outlet){
				
					if($vendor->state_id != $outlet->state_id){
						$gst = false;
					}
				}
			}
		}
		return $gst;
	}
		public function getVendorTAXNO(){
		$tax_no = '';
		if($this->itemReturn){
			if($this->itemReturn->vendor){
				$tax_no = $this->itemReturn->vendor->tax_no;
			}
		}
		return $tax_no;
	}
	
	public function getTaxPercentage($col){
		$checkgst = true;
		if($col == 'igst_per' || $col == 'igst_amt' ){
			$checkgst = false;
		}
		if($this->getReturnGstTrue($this->return_id) == $checkgst){
			return $this->$col;
		}else{
			return '0.00';
		}
		
		
	}
	
	public function getReturnGstTrue($poid){
		$gst = true;
		if($poid){
			$mrs = ItemReturn::model()->findByAttributes(array('id'=>$poid));
			if($mrs){
				$outlet = Outlet::model()->findByPk($mrs->outlet_id);
				if($outlet){
					$vendor = Vendor::model()->findByPk($mrs->vendor_id);
					if($vendor->state_id != $outlet->state_id){
						$gst = false;
					}
				}
			}
		}
		//Yii::log ( CVarDumper::dumpAsString ( $gst ), CLogger::LEVEL_WARNING, '$$gst' );
		return $gst;
	}

	public function getItemReturnCgstAmount($vendorId, $outletId, $taxId, $col)
	{
		$amount = '0.00';
		$itemReturnItemDetails = ItemReturnItem::model()->findAllByAttributes(array(
			'vendor_id' => $vendorId,
			'outlet_id' => $outletId,
			'tax_id' => $taxId,
			'status' => ItemReturn::STATUS_PENDING
		));
		if ($itemReturnItemDetails) {
			foreach ($itemReturnItemDetails as $itemReturnItemDetail) {
				if ($itemReturnItemDetail->getGstTrue($vendorId, $outletId) == true && $itemReturnItemDetail->free == 0) {
					$amount = $amount + $itemReturnItemDetail->$col;
				}
			}
		}
		return $amount;
	}

	public function getAllTaxOptions($id = null, $vendorId = null, $outletId = null) {
		$taxType = null;
		$condition = array('status'=>Tax::STATUS_ACTIVE);
		if($vendorId && $outletId) {
			$taxType = $this->getGstTrue($vendorId, $outletId) == true ? 0 : 1;
			$condition['type_id'] = $taxType;
		}

		$list = array();
		$taxes = Tax::model()->findAllByAttributes($condition);
		if($taxes){
			foreach($taxes as $tax){
				$list[$tax->id] = $tax->title;
			}
		}
		return $list;
		if ($id == null)
			return $list;
			if (is_numeric ( $id ))
				return $list [$id];
				return $id;
	}

	public function getChangeItemReturnCgstAmount($id,$col,$return_item_ids){
		$amount = 0;
		if(!empty($return_item_ids)){
			foreach($return_item_ids as $return_item_id){
				$array = json_decode(($return_item_id), true);
				$itemReturnItem = ItemReturnItem::model()->findByPk($array['id']);
				if(isset($array['cgst']) && ($col == 'cgst_amt')&&($array['tax_id'] == $id)&& ($itemReturnItem->free == 0)){
					$amount = $amount + $array['cgst'];
				}else if(isset($array['sgst']) && ($col == 'sgst_amt')&&($array['tax_id'] == $id)){
					$amount = $amount + $array['sgst'];
				}else if(isset($array['cess']) && ($col == 'cess_amt')&&($array['tax_id'] == $id)){
					$amount = $amount + $array['cess'];
				}else if(isset($array['igst']) && ($col == 'igst_amt')&&($array['tax_id'] == $id)){
					$amount = $amount + $array['igst'];
				}
			}
		}
		return $amount;
	
	
	}
	public function getChangeItemReturnAmount($id,$return_item_ids){
	//	$array = json_decode(json_encode($return_item_ids), true);
		$amount = 0;
		if(!empty($return_item_ids)){
			foreach($return_item_ids as $return_item_id){
				$array = json_decode(($return_item_id), true);
				$itemReturnItem = ItemReturnItem::model()->findByPk($array['id']);
				if($array['tax_id'] == $id && $itemReturnItem->free == 0){
					$amount = $amount + (($array['qty'] * $array['price'])-($array['discount']+$array['discount1']));
				}
			}
		}
		return $amount;
		
	
	}


	public function getItemReturnItemAmount($vendorId, $outletId,$id){
		$amount = '0.00';
		$itemReturnItemDetails = ItemReturnItem::model()->findAllByAttributes(array(
			'vendor_id' => $vendorId,
			'outlet_id' => $outletId,
			'tax_id'=>$id,
			'status' => ItemReturn::STATUS_PENDING
		));
		if($itemReturnItemDetails){
			foreach($itemReturnItemDetails as $itemReturnItemDetail){
				if($itemReturnItemDetail->free == 0){
					$amount = $amount + (($itemReturnItemDetail->qty * $itemReturnItemDetail->price)-($itemReturnItemDetail->discount_amt+$itemReturnItemDetail->discount_amt1));
				}
			}
				
		}
		return $amount;
	
	}
	
	public function getColumns($selectcolumns = array()) {
		if (! empty ( $selectcolumns )) {
			$selected = $selectcolumns;
		} else {
			
		$selected = array (
				//	'grn_date',
					'start_date',
					'vendor_id' ,
					'tax_id',
						'gst_no' ,
					'credit_note_no',
				'credit_note_date',
				'debit_note_date',
					'invoice_no',
					'gst_per',
					'cgst_per',
					'sgst_per',
					'igst_per',
					'cess_per',
					'net_amount',
					'basic_value',
					'discount',
					'gst_amt' ,
					'cgst_amt',
					'sgst_amt',
					'igst_amt',
					'cess_amt',
					'grn_no' 
					
			);
		}
		
		if ($selected) {
			foreach ( $selected as $select ) {
				/*if ($select == 'grn_date') {
					$columns [] = array (
							'label' => 'grn_date',
							'value' => function ($data) {
							return date("Y-m-d",strtotime($data->create_time));
							}
							);
				} else*/ if ($select == 'start_date') {
					 $columns[] = array(
                        'label' => 'Date',
                        'value' => function ($data) {
                        return $data->getGRNDateData();
                            //return date("d/m/Y", strtotime($data->create_time));
                        }
                    );
                    
                    $columns[] = array(
                        'label' => 'Bill Date',
                        'value' => function ($data) {
                        return $data->getBillDateData();
                        //return date("d/m/Y", strtotime($data->create_time));
                        }
                        );
				} else if ($select == 'vendor_id') {
					$columns [] = array (
							'label' => 'Vendor',
							'value' => function ($data) {
							return isset($data->itemReturn)?$data->itemReturn->vendor:"";
							}
							);
				}else if ($select == 'tax_id') {
					$columns [] = array (
							'label' => 'HSN Code',
							'value' => function ($data) {
							return isset($data->tax)?$data->tax->hrn_code:"";
							}
							);
				} else if ($select == 'bill_no') {
					$columns [] = array (
							'label' => 'Bill No.',
							'value' => function ($data) {
							return isset ( $data->itemReturn ) ? $data->itemReturn->bill_no: "";
							}
							);
				} else if ($select == 'gst_no') {
					$columns [] = array (
							'label' => 'GST NO',
							'value' => function ($data) {
							return $data->getVendorTAXNO();
							}
							);
				}else if ($select == 'gst_per') {
					$columns [] = array (
							'label' => 'GST%',
							'value' => function ($data) {
							return $data->getTotalGstPer();
							}
							);
				} else if ($select == 'cgst_per') {
					$columns [] = array (
							'label' => 'CGST (%age)',
							'value' => function ($data) {
							return $data->cgst_per;
							}
							);
				} else if ($select == 'sgst_per') {
					$columns [] = array (
							'label' => 'SGST (%age)',
							'value' => function ($data) {
							return $data->sgst_per;
							}
							);
				} else if ($select == 'cess_per') {
					$columns [] = array (
							'label' => 'CESS (%age)',
							'value' => function ($data) {
							return $data->cess_per;
							}
							);
				} else if ($select == 'igst_per') {
					$columns [] = array (
							'label' => 'IGST (%age)',
							'value' => function ($data) {
							return $data->igst_per;
							}
							);
				} else if ($select == 'credit_note_no') {
					$columns [] = array (
							'label' => 'Credit Note No.',
							'value' => function ($data) {
							return isset ( $data->itemReturn ) ? $data->itemReturn->credit_note_no: "";
							}
							);
				}else if ($select == 'credit_note_date') {
					$columns [] = array (
							'label' => 'Credit Note Date',
							'value' => function ($data) {
							return isset ( $data->itemReturn ) ? $data->itemReturn->credit_note_date: "";
							}
							);
				}else if ($select == 'debit_note_date') {
					$columns [] = array (
							'label' => 'Debit Note Date',
							'value' => function ($data) {
							return isset ( $data->itemReturn ) ? $data->itemReturn->grn_save_date: "";
							}
							);
				} else if ($select == 'invoice_no') {
					$columns [] = array (
							'label' => 'Supplier Invoice No.',
							'value' => function ($data) {
							return isset ( $data->itemReturn ) ? $data->itemReturn->invoice_no: "";
							}
							);
				}else if ($select == 'net_amount') {
					$columns [] = array (
							'label' => 'Net Amount',
							'value' => function ($data) {
							return isset ( $data->itemReturn ) ? $data->itemReturn->total_amt: "";
						
							}
							);
					
				}else if ($select == 'basic_value') {
					$columns [] = array (
							'label' => 'Basic Value',
							'value' => function ($data) {
							return ($data->price * $data->qty) - $data->discount_amt;
							}
							);
					
				}else if ($select == 'discount') {
					$columns [] = array (
							'label' => 'Discount',
							'value' => function ($data) {
							// return isset ( $data->itemReturn ) ? $data->itemReturn->discount_amt: "";
							return $data->discount_amt;
							}
							);
				}else if ($select == 'gst_amt') {
					$columns [] = array (
							'label' => 'GST',
							'value' => function ($data) {
							return $data->cgst_amt + $data->sgst_amt + $data->cess_amt + $data->igst_amt;
							}
							);
				}else if ($select == 'cgst_amt') {
					$columns [] = array (
							'label' => 'CGST',
							'value' => function ($data) {
							return $data->cgst_amt;
							}
							);
				}else if ($select == 'sgst_amt') {
					$columns [] = array (
							'label' => 'SGST',
							'value' => function ($data) {
							return $data->sgst_amt;
							}
							);
				}else if ($select == 'igst_amt') {
					$columns [] = array (
							'label' => 'IGST',
							'value' => function ($data) {
							return $data->igst_amt;
							}
							);
				}  else if ($select == 'cess_amt') {
					$columns [] = array (
							'label' => 'CESS Amount',
							'value' => function ($data) {
							return  $data->cess_amt;
							}
							);
				}else if ($select == 'grn_no') {
					$columns [] = array (
							'label' => 'GRN NUMBER',
							'value' => function ($data) {
							return isset ( $data->itemReturn ) ? $data->itemReturn->grn_no: "";
							//return 'Gr-'.$data->purchase_bill_id;
							}
							);
				}
				else {
					$columns [] = $select;
				}
			}
		}
	
		return $columns;
	}
	public function getTotalGstPer(){
		$data = $this;
		$cgst = $data->getTaxPercentage("cgst_per");
		$sgst = $data->getTaxPercentage("sgst_per");
		$cess = $data->getTaxPercentage("cess_per");
		$igst = $data->getTaxPercentage("igst_per");
		$total = $cgst + $sgst + $cess + $igst;
		return $total;
	}
	public function getVendorName(){
		$vendor_name = '';
if(isset($this->itemReturn)){
	if(isset($this->itemReturn->vendor)){
		$vendor = $this->itemReturn->vendor;
		$vendor_name = $this->itemReturn->vendor->name;
		if($this->itemReturn->vendor->parent_id != null){
			$vendor_name = $vendor->parentvendor->name;
		}
		
	}
}
return $vendor_name;
	}
	
	public function getGRNDateData()
	{
		
	$grn_date = '';
		
	$model = $this;

if($model)
{
	$bill_no = "";

if(isset($model->itemReturn)){
	$bill_no = isset ( $model->itemReturn ) ? $model->itemReturn->bill_no: "";
}
if($bill_no != ""){
	$purchasebill = PurchaseBill::model()->findByAttributes(array('bill_no'=>$bill_no));
	if($purchasebill){
		$grn_date = date("Y-m-d",strtotime($purchasebill->start_date));
		
	}
}
return $grn_date;


}	
	}
	
	
	public function getCustomGRNNumber()
	{
		
	$custom_grn = '';
	$model = $this;
if($model)
{
	$grn_no = isset ( $model->itemReturn ) ?  $model->itemReturn->grn_no: "";
	if($grn_no != "")
	{
		$custom_grn = "GR-" . $grn_no;
	}
	
}
return 	$custom_grn;    
	}
	
	public function getBillDateData()
	{
		
	$bill_date = '';
		
	$model = $this;

if($model)
{
	$bill_no = "";

if(isset($model->itemReturn)){
	$bill_no = isset ( $model->itemReturn ) ? $model->itemReturn->bill_no: "";
}
if($bill_no != ""){
	$purchasebill = PurchaseBill::model()->findByAttributes(array('bill_no'=>$bill_no));
	if($purchasebill){
		$bill_date = date("Y-m-d",strtotime($purchasebill->end_date));
		
	}
}
return $bill_date;


}	
	}
	
	public function toTallyArray() {
		$model = $this;
		$json_entry = null;
		if ($model) {
$vendor_name = '';
$bill_no = "";
$bill_date = '';
$grn_save_date = '';
if(isset($model->itemReturn)){
	if(isset($model->itemReturn->vendor)){
		$vendor = $model->itemReturn->vendor;
		$vendor_name = $model->itemReturn->vendor->name;
		if($model->itemReturn->vendor->parent_id != null){
			$vendor_name = $vendor->parentvendor->name;
		}
		
	}
	$bill_no = isset ( $model->itemReturn ) ? $model->itemReturn->bill_no: "";
	
	$grn_save_date = date("Y-m-d",strtotime( $model->itemReturn->grn_save_date ));
}

$grn_date = '';
if($bill_no != ""){
	$purchasebill = PurchaseBill::model()->findByAttributes(array('bill_no'=>$bill_no));
	if($purchasebill){
		$bill_date = $purchasebill->end_date;
		
		 $grn_date = date("Y-m-d",strtotime( $purchasebill->start_date ));
	}
}
			$batch_no =  array();
			$default_img = 'default.png';
			$json_entry = array ();
			$json_entry ['id'] = $model->id;
			  $json_entry ['grn_date'] = $grn_date;
            $json_entry['Bill Date'] = $bill_date;
            $json_entry['Date'] = $grn_save_date;
			
			 //$json_entry['Date'] = date("Y-m-d", strtotime($model->grn_save_date));
			$json_entry ['Vendor'] = $vendor_name;
			$json_entry ['HSN Code'] = isset($model->tax)?$model->tax->hrn_code:"";
			$json_entry ['GST NO'] = $model->getVendorTAXNO();
			$json_entry ['Credit Note No'] = isset ( $model->itemReturn ) ? $model->itemReturn->credit_note_no: "";
			$json_entry ['Credit Note Date'] = isset ( $model->itemReturn ) ? $model->itemReturn->credit_note_date: "";
			
			
			$json_entry ['Bill No'] = isset ( $model->itemReturn ) ? $model->itemReturn->bill_no: "";
			
			$json_entry ['Supplier Invoice No'] = isset ( $model->itemReturn ) ? $model->itemReturn->invoice_no: "";
			$json_entry ['GST Rate'] = $model->getTotalGstPer();
			$json_entry ['CGST Rate'] = $model->cgst_per;
			$json_entry ['SGST Rate'] = $model->sgst_per;
			$json_entry ['CESS Rate'] = $model->cess_per;
			$json_entry ['IGST Rate'] = $model->igst_per;
			$json_entry ['Net Amount'] =isset ( $model->itemReturn ) ? $model->itemReturn->total_amt: "";
			// $json_entry ['Basic Value'] =  $model->price * $model->qty;
			$json_entry ['Basic Value'] =  ($model->price * $model->qty) - $model->discount_amt;
			
			// $json_entry ['Discount'] = isset ( $model->itemReturn ) ? $model->itemReturn->discount_amt: "";
			// $json_entry ['Discount'] = $model->discount_amt;
			$json_entry ['Discount'] = 0;
			$json_entry ['GST'] = $model->cgst_amt + $model->sgst_amt + $model->cess_amt + $model->igst_amt;
			$json_entry ['CGST'] = $model->cgst_amt;
			$json_entry ['SGST'] = $model->sgst_amt;
			$json_entry ['IGST'] = $model->igst_amt;
			$json_entry ['CESS'] = $model->cess_amt;
			$json_entry ['GRN NUMBER'] =  $model->getCustomGRNNumber() ;
			
			//isset ( $model->itemReturn ) ? $model->itemReturn->grn_no: "";
	
		}
		return $json_entry;
	}
}