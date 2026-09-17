<?php

 
/**
 * @property integer $id
 * @property string $title
 * @property string $item_code
 * @property string $description
 * @property string $image_file
 * @property integer $item_type
 * @property integer $status
 * @property integer $type_id
 * @property integer $is_tax
 * @property integer $is_discount
 * @property integer $category_id
 * @property integer $sub_company_id
 * @property integer $company_id
 * @property string $create_time
 * @property integer $create_user_id
 * @property integer $updated_by
 */
Yii::import('application.models._base.BaseItem');
class Item extends BaseItem
{
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
	public function getOpeningQuantity(){
		$qty = 0;
		if(Yii::app ()->session ['stock_start_date'] == date('Y-m-d')){
			$criteria = new CDbCriteria();
			$criteria->addCondition('item_id ='.$this->id);
			$criteria->addCondition('date(create_time) <"'.date('Y-m-d').'"');
			$criteria->order = 'id desc';
			$stock = StockLog::model()->find($criteria);
			if($stock){
				$qty = $stock->current_qty;
			}
		}else{
			$criteria = new CDbCriteria();
			$criteria->addCondition('item_id ='.$this->id);
			// With no start date in the session this built
			// `date(create_time) < ""`. MySQL 5.7 treated that as a warning and
			// matched nothing; MySQL 8 rejects it outright (error 1525,
			// Incorrect DATE value). Returning 0 without running the query is
			// the same answer 5.7 gave, without the error.
			if (Yii::app ()->session ['stock_start_date'] === null
					|| Yii::app ()->session ['stock_start_date'] === '') {
				return $qty;
			}
			$criteria->addCondition('date(create_time) <"'.Yii::app ()->session ['stock_start_date'].'"');
			$criteria->order = 'id desc';
			$stock = StockLog::model()->find($criteria);
			if($stock){
				$qty = $stock->current_qty;
			}
			
		}
		if($qty == ''){
			$qty = '0.000';
		}
		
		return $qty;
	}
	public function getAdjustmentQuantity(){
		$qty = 0;
		
			$criteria = new CDbCriteria();
			$criteria->addCondition('item_id ='.$this->id);
			$criteria->addBetweenCondition('date',Yii::app ()->session ['stock_start_date'],Yii::app ()->session ['stock_end_date']);
			$criteria->select = 'sum(adjusted) as adjusted';
			$stock = StockAdjustLog::model()->find($criteria);
			if($stock){
				$qty = $stock->adjusted;
			}
			if($qty == ''){
				$qty = '0.000';
			}
		
		return $qty;
	}
	public function getAddedQuantity(){
		$qty = 0;
	
		$criteria = new CDbCriteria();
		$criteria->addCondition('item_id ='.$this->id);
		$criteria->addBetweenCondition('date(create_time)',Yii::app ()->session ['stock_start_date'],Yii::app ()->session ['stock_end_date']);
		$criteria->select = 'sum(approved_qty) as approved_qty';
		$stock = PurchaseBillDetail::model()->find($criteria);
		if($stock){
			$qty = $stock->approved_qty;
		}
		if($qty == ''){
			$qty = '0.000';
		}
	
		return $qty;
	}
	public function getReturnQuantity(){
		$qty = 0;
	
		$criteria = new CDbCriteria();
		$criteria->addCondition('item_id ='.$this->id);
		$criteria->addBetweenCondition('date(create_time)',Yii::app ()->session ['stock_start_date'],Yii::app ()->session ['stock_end_date']);
		$criteria->select = 'sum(qty) as qty';
		$stock = ItemReturnItem::model()->find($criteria);
		if($stock){
			$qty = $stock->qty;
		}
		if($qty == ''){
			$qty = '0.000';
		}
	
		return $qty;
	}
	public function getSoldQuantity(){
		$qty = 0;
	
		$criteria = new CDbCriteria();
		$criteria->addCondition('item_id ='.$this->id);
		$criteria->addBetweenCondition('date(create_time)',Yii::app ()->session ['stock_start_date'],Yii::app ()->session ['stock_end_date']);
		$criteria->select = 'sum(qty) as qty';
		$stock = OrderItem::model()->find($criteria);
		if($stock){
			$qty = $stock->qty;
		}
		if($qty == ''){
			$qty = '0.000';
		}
	
		return $qty;
	}
	public function getRefundQuantity(){
		$qty = 0;
	
		$criteria = new CDbCriteria();
		$criteria->addCondition('item_id ='.$this->id);
		$criteria->addBetweenCondition('date(create_time)',Yii::app ()->session ['stock_start_date'],Yii::app ()->session ['stock_end_date']);
		$criteria->select = 'sum(qty) as qty';
		$stock = OrderRefundItem::model()->find($criteria);
		if($stock){
			$qty = $stock->qty;
		}
		if($qty == ''){
			$qty = '0.000';
		}
	
		return $qty;
	}
	public function getExpiryQuantity(){
		$qty = 0;
	
		$criteria = new CDbCriteria();
		$criteria->addCondition('item_id ='.$this->id);
		$criteria->addBetweenCondition('date(create_time)',Yii::app ()->session ['stock_start_date'],Yii::app ()->session ['stock_end_date']);
		$criteria->select = 'sum(qty) as qty';
		$stock = ItemExpireItem::model()->find($criteria);
		if($stock){
			$qty = $stock->qty;
		}
		if($qty == ''){
			$qty = '0.000';
		}
	
		return $qty;
	}
	public function getStockRemainingQuantity(){
		$qty = 0;
	
		$criteria = new CDbCriteria();
		$criteria->addCondition('item_id ='.$this->id);
		$criteria->addCondition('date(create_time) <="'.Yii::app ()->session ['stock_end_date'].'"');
		$criteria->order = 'id desc';
		$stock = StockLog::model()->find($criteria);
		Yii::log ( CVarDumper::dumpAsString ( $stock ), CLogger::LEVEL_WARNING, '$stock' );
		if($stock){
			$qty = $stock->current_qty;
		}
		if($qty == ''){
			$qty = '0.000';
		}
	
		return $qty;
	}
	
	public function toonlineArray() {
		$model = $this;
		$json_entry = null;
		if ($model) {
	
			$batch_no =  array();
			$default_img = 'default.png';
			$json_entry = array ();
			$json_entry ['Code'] = isset($model->item_code)?$model->item_code:"";
			$json_entry ['Name'] = isset($model->title)?$model->title:"";
			$json_entry ['Short_x0020_Name'] = isset($model->short_name)?$model->short_name:"";
			$json_entry ['Barcode'] = $model->getItemBarcodes();
			$json_entry ['Tax'] = $model->getMainItemTax();
			$json_entry ['MRP'] = isset($model->mrp)?$model->mrp:"";
			
			$json_entry ['PRICE'] = isset($model->sale_price)?$model->sale_price:"";
			$json_entry ['OPStock'] = $model->getTotalRemainingQuantity();
			$json_entry ['Pur_x0020_Price'] = isset($model->purchase_price)?$model->purchase_price:"";
			$json_entry ['Pur_x0020_Value'] = isset($model->purchase_price)?$model->purchase_price:"";
			$json_entry ['Weight'] = isset($model->weight)?$model->weight:"";
			$json_entry ['Department'] =isset($model->category)?$model->category->title:"";
			$json_entry ['Company'] = isset($model->company)?$model->company->title:"";
			$subcat = isset($model->subcategory)?$model->subcategory->title:"";
			if($subcat == '<ADD NEW>'){
				$subcat = '';
			}
			$json_entry ['Sub_x0020_Category'] = $subcat;
			$json_entry ['ProdEx1'] = '';
			$json_entry ['ProdEx2'] = '';
			$json_entry ['ProdEx3'] = '';
			$json_entry ['ProdEx4'] = '';
			$json_entry ['Active'] = $model->getStatusOptions($model->status);
	
		}
		return $json_entry;
	}
	public function toArray() {
		$model = $this;
		$json_entry = null;
		if ($model) {
			$default_img = 'default.png';
			$json_entry = array ();
			$json_entry ['id'] = $model->id;
			$json_entry ['item_code'] = isset ( $model->item_code ) ? $model->item_code : '';
			$json_entry ['hsn_code'] = isset ( $model->hsn_code ) ? $model->hsn_code : '';
			$json_entry ['description'] = isset ( $model->description ) ? $model->description : '';
			$json_entry ['min_qty'] = isset ( $model->min_qty ) ? $model->min_qty : '';
			$json_entry ['max_qty'] = isset ( $model->max_qty ) ? $model->max_qty : '';
			$json_entry ['reorder_qty'] = isset ( $model->reorder_qty ) ? $model->reorder_qty : '';
			$json_entry ['mrp'] = isset ( $model->mrp ) ? $model->mrp : '';
			$json_entry ['sale_price'] = isset ( $model->sale_price ) ? $model->sale_price : '';
			$json_entry ['weight'] = isset ( $model->weight ) ? $model->weight : '';
			$json_entry ['purchase_price'] = isset ( $model->purchase_price ) ? $model->purchase_price : '';
			$json_entry ['whole_sale'] = isset ( $model->whole_sale ) ? $model->whole_sale : '';
			$json_entry ['is_stockable'] =  $model->getStockOptions($model->is_stockable);
			$json_entry ['movement_type'] =  $model->getMovementTypeOptions($model->movement_type);
			$json_entry ['opening_stock'] = isset ( $model->opening_stock ) ? $model->opening_stock : '';
			$json_entry ['unit'] = isset ( $model->unit ) ? $model->unit : '';
			$json_entry ['image_file'] = isset ( $this->image_file ) ? Yii::app ()->getBaseUrl ( true ) . '/wdir/uploads/' . $this->image_file : Yii::app ()->getBaseUrl ( true ) . '/wdir/uploads/' . $default_img;
			$json_entry ['is_tax'] =  $model->getTypeOptions($model->item_type);
			$json_entry ['is_discount'] =  isset ( $model->is_discount ) ? $model->is_discount : '';
			$json_entry ['is_coupon'] = isset ( $model->is_coupon ) ? $model->is_coupon : '';
			$json_entry ['item_type'] =  isset ( $model->item_type ) ? $model->item_type : '';
			$json_entry ['category'] = isset ( $model->category ) ? $model->category->title : '';
			$json_entry ['company'] = isset ( $model->company ) ? $model->company->title : '';
			$json_entry ['subCompany'] = isset ( $model->subCompany ) ? $model->subCompany->title : '';
			$json_entry ['create_username'] = isset ( $model->createUser ) ? $model->createUser->full_name : '';
			$json_entry ['create_user_id'] = isset ( $model->create_user_id ) ? $model->create_user_id : '';
			$json_entry ['create_time'] = isset ( $model->create_time ) ? $model->create_time : '';
	
	
		}
		return $json_entry;
	}
	public function getBarcodeList(){
		$list = array();
		$criteria = new CDbCriteria();
		$criteria->order = 'id asc';
		$criteria->addCondition('item_id ='.$this->id);
		$criteria->addCondition('status ='.ItemDetail::STATUS_ACTIVE);
		$item_details = ItemDetail::model()->findAll($criteria);
		if($item_details){
			foreach($item_details as $item_detail){
				$list[$item_detail->id] = $item_detail->bar_code;
			}
		}
		
		return $list;
	}
	
	public function getVendorNames(){
		$list = array();
		$criteria = new CDbCriteria();
		$criteria->order = 'id desc';
		$criteria->addCondition('item_id ='.$this->id);
		$item_details = ItemDetail::model()->findAll($criteria);
		if($item_details){
			foreach($item_details as $item_detail){
				$list[$item_detail->id] = $item_detail->bar_code;
			}
		}
	
		return $list;
	}
	public function getItemBarcodes(){
		$str = '';
		$criteria = new CDbCriteria();
		$criteria->order = 'id asc';
		$criteria->addCondition('item_id ='.$this->id);
		$criteria->addCondition('status ='.ItemDetail::STATUS_ACTIVE);
		$item_detail = ItemDetail::model()->find($criteria);
		if($item_detail){
			$str = $item_detail->bar_code;
		}
		return $str;
	}
	public static function getItemMainBarcodes($id){
		$str = '';
		$criteria = new CDbCriteria();
		$criteria->order = 'id asc';
		$criteria->addCondition('item_id ='.$id);
		$item_detail = ItemDetail::model()->find($criteria);
		if($item_detail){
			$str = $item_detail->bar_code;
		}
		return $str;
	}

		public static function getItemMainMargin($id){
			$str = '';
			$criteria = new CDbCriteria();
			$criteria->order = 'id asc';
			$criteria->addCondition('item_id ='.$id);
			$item_detail = ItemDetail::model()->find($criteria);
			$item = Item::model()->findByPk($id);
			if($item_detail && $item && $item->mrp > 0 && $item->purchase_price > 0){
				$price = $item->purchase_price;
				$tax =isset($item_detail->tax)? ($price * $item_detail->tax->tax_val1/100) + ($price * $item_detail->tax->tax_val2/100) + ($price * $item_detail->tax->tax_val3/100) + ($price * $item_detail->tax->tax_val4/100) : 0;
				$str = $item->purchase_price * ($item->mrp - ($price + $tax)) * 100 / ($price + $tax) / 100;
			}
			return $str;
		}

		public static function getItemMainGSTNewMRP($id, $tax) {
			$str = '';
			$item = Item::model()->findByPk($id);
			$margin = static::getItemMainMargin($id);
			if($item->purchase_price > 0){
				$str = ($item->purchase_price + $margin) * (100 + $tax) / 100;
			}
			return $str;
		}

		public static function getItemMainGSTNewActualTax($id, $hsn_code) {
			$str = '';
			$item = Item::model()->findByPk($id);
			$margin = static::getItemMainMargin($id);
			if($item->purchase_price > 0){
				$tax = 0;
				$newTax = TblItemNewTax::model()->findByAttributes(array('hsn_code'=>$hsn_code));
				if ($newTax ) {
					return $newTax->tax;
				}
			}
			return $str;
		}
		public static function getItemMainGSTNewActualMRPOld($id, $hsn_code) {
			$str = '';
			$item = Item::model()->findByPk($id);
			$margin = static::getItemMainMargin($id);
			if($item->purchase_price > 0){
				$tax = 0;
				$newTax = TblItemNewTax::model()->findByAttributes(array('hsn_code'=>$hsn_code));
				if ($newTax ) {
					$tax = $newTax->tax;
					$str = ($item->purchase_price + $margin) * (100 + $tax) / 100;
				}
			}
			return $str;
		}

		public static function getItemMainGSTNewActualMRP($id, $tax) {
			$str = '';
			$item = Item::model()->findByPk($id);
			$margin = static::getItemMainMargin($id);
			if($item->purchase_price > 0){
				
				if ($tax >= 0 ) {
					$str = ($item->purchase_price + $margin) * (100 + $tax) / 100;
				}
			}
			return $str;
		}

	public function getMainItemTax(){
		$str = '';
		
		$criteria = new CDbCriteria();
		$criteria->order = 'id asc';
		$criteria->addCondition('item_id ='.$this->id);
		$item_detail = ItemDetail::model()->find($criteria);
		if($item_detail){
			$str = isset($item_detail->tax)?$item_detail->tax->title:"";
		}
		return $str;
	}
	public static function getStaticMainItemTax($id){
		$str = '';
	
		$criteria = new CDbCriteria();
		$criteria->order = 'id asc';
		$criteria->addCondition('item_id ='.$id);
		$item_detail = ItemDetail::model()->find($criteria);
		if($item_detail){
			$str = isset($item_detail->tax)?$item_detail->tax->title:"";
		}
		return $str;
	}
	public function getAscBarCodeTotalRemainingQuantity()
	{
	
		$remaining_quantity = 0;
		$criteria = new CDbCriteria();
		$criteria->order = 'id asc';
		$criteria->addCondition('status ='.ItemDetail::STATUS_ACTIVE);
		$criteria->addCondition('item_id ='.$this->id);
		$item_detail = ItemDetail::model()->find($criteria);
		if($item_detail){
			$remaining_quantity = '0.000';
			$add_quantity = '0.000';
			$sub_quantity = '0.000';
			$criteria = new CDbCriteria();
			$criteria->addCondition('item_detail_id ='.$item_detail->id );
			$criteria->order =  'id asc';
			$criteria->addCondition ( "balance_qty > 0.000");
			$criteria->addCondition('item_detail_id IS NOT NULL');
			$stocks = ItemStock::model()->findAll($criteria);
			
			if(!empty($stocks))
			{
				foreach ($stocks as $stock)
				{
					$add_quantity = ($add_quantity) + ($stock->balance_qty);
			
				}
			}
			$criteria1 = new CDbCriteria();
			$criteria1->addCondition('item_detail_id ='.$item_detail->id );
			$criteria1->order =  'id asc';
			$criteria1->addCondition ( "balance_qty < 0.000");
			$criteria1->addCondition('item_detail_id IS NOT NULL');
			$stocks = ItemStock::model()->findAll($criteria1);
			
			if(!empty($stocks))
			{
				foreach ($stocks as $stock)
				{
					$sub_quantity = ($sub_quantity) + abs($stock->balance_qty);
			
				}
			}
			$remaining_quantity = bcsub($add_quantity,$sub_quantity,3);
	
			/* if(!empty($item_detail))
			 {
			 	
			 $remaining_quantity += $item_detail->open_stock_qty;
			 	
			 } */
		}
		/* if($remaining_quantity < 0)
			{
			$remaining_quantity =0;
			} */
		return $remaining_quantity;
	}
	public function getBarCodeTotalRemainingQuantity()
	{
	
		$remaining_quantity = 0;
		$criteria = new CDbCriteria();
		$criteria->order = 'id desc';
		$criteria->addCondition('status ='.ItemDetail::STATUS_ACTIVE);
		$criteria->addCondition('item_id ='.$this->id);
		$item_detail = ItemDetail::model()->find($criteria);
		if($item_detail){
			$remaining_quantity = '0.000';
			$add_quantity = '0.000';
			$sub_quantity = '0.000';
			$criteria = new CDbCriteria();
			$criteria->addCondition('item_detail_id ='.$item_detail->id );
			$criteria->order =  'id asc';
			$criteria->addCondition ( "balance_qty > 0.000");
			$criteria->addCondition('item_detail_id IS NOT NULL');
			$stocks = ItemStock::model()->findAll($criteria);
				
			if(!empty($stocks))
			{
				foreach ($stocks as $stock)
				{
					$add_quantity = ($add_quantity) + ($stock->balance_qty);
						
				}
			}
			$criteria1 = new CDbCriteria();
			$criteria1->addCondition('item_detail_id ='.$item_detail->id );
			$criteria1->order =  'id asc';
			$criteria1->addCondition ( "balance_qty < 0.000");
			$criteria1->addCondition('item_detail_id IS NOT NULL');
			$stocks = ItemStock::model()->findAll($criteria1);
				
			if(!empty($stocks))
			{
				foreach ($stocks as $stock)
				{
					$sub_quantity = ($sub_quantity) + abs($stock->balance_qty);
						
				}
			}
			$remaining_quantity = bcsub($add_quantity,$sub_quantity,3);
	
		/* if(!empty($item_detail))
		{
			
				$remaining_quantity += $item_detail->open_stock_qty;
			
		} */
		}
		/* if($remaining_quantity < 0)
		{
			$remaining_quantity =0;
		} */
		return $remaining_quantity;
	}
	public function getTotalRemainingQuantity()
	{
		
		$remaining_quantity = '0.000';
		$add_quantity = '0.000';
		$sub_quantity = '0.000';
		$criteria = new CDbCriteria();
		$criteria->addCondition('item_id ='.$this->id);
		$criteria->order =  'id asc';
		$criteria->addCondition ( "balance_qty > 0.000");
		$criteria->addCondition('item_detail_id IS NOT NULL');
		$stocks = ItemStock::model()->findAll($criteria);
		
		if(!empty($stocks))
		{
			foreach ($stocks as $stock)
			{
				$add_quantity = ($add_quantity) + ($stock->balance_qty);
				
			}
		}
		$criteria1 = new CDbCriteria();
		$criteria1->addCondition('item_id ='.$this->id);
		$criteria1->order =  'id asc';
		$criteria1->addCondition ( "balance_qty < 0.000");
		$criteria1->addCondition('item_detail_id IS NOT NULL');
		$stocks = ItemStock::model()->findAll($criteria1);
		
		if(!empty($stocks))
		{
			foreach ($stocks as $stock)
			{
				$sub_quantity = ($sub_quantity) + abs($stock->balance_qty);
		
			}
		}
		$remaining_quantity = bcsub($add_quantity,$sub_quantity,3);
		/* $details = $this->itemDetails;
		
		if(!empty($details))
		{
			foreach ($details as $detail)
			{
				$remaining_quantity += $detail->open_stock_qty;
			}
		} */
		
// 		if($remaining_quantity < 0)
// 		{
// 			$remaining_quantity =0;
// 		}
		return $remaining_quantity;
	}
	public static function getMainTotalRemainingQuantity($id)
	{
	
		$remaining_quantity = 0;
		$criteria = new CDbCriteria();
		$criteria->addCondition('item_id ='.$id);
		$criteria->addCondition('item_detail_id IS NOT NULL');
		$stocks = ItemStock::model()->findAll($criteria);
	
		if(!empty($stocks))
		{
			foreach ($stocks as $stock)
			{
				$remaining_quantity += $stock->balance_qty;
			}
		}
		
		return $remaining_quantity;
	}
	public function getOutletTotalRemainingQuantity($id,$outlet)
	{
	
		$remaining_quantity = 0;
		$criteria = new CDbCriteria();
		$criteria->addCondition('item_id ='.$this->id);
		$criteria->addCondition('item_detail_id ='.$id);
		$criteria->addCondition('outlet_id ='.$outlet);
		$stocks = ItemStock::model()->findAll($criteria);
	
		if(!empty($stocks))
		{
			foreach ($stocks as $stock)
			{
				$remaining_quantity += $stock->balance_qty;
			}
		}
		/* $details = $this->itemDetails;
	
		if(!empty($details))
		{
		foreach ($details as $detail)
		{
		$remaining_quantity += $detail->open_stock_qty;
		}
		} */
	
		
		return $remaining_quantity;
	}
	public function getTaxList(){
		$list = array();
		$criteria = new CDbCriteria ();
	   $criteria->addCondition ( 'status ='.Tax::STATUS_ACTIVE);
		$taxes = Tax::model ()->findAll ( $criteria);
		if($taxes){
			foreach($taxes as $tax){
				$list[$tax->id] = $tax->title;
			}
		}
		return $list;
	}
	public function getParentCategorys(){
		$list = array();
		$criteria = new CDbCriteria ();
		$criteria->addCondition ( 'parent_id IS  NULL');
		$criteria->order = 'title asc';
		$criteria->addCondition ( 'status ='.ItemCategory::STATUS_ACTIVE);
		$cats = ItemCategory::model ()->findAll ( $criteria);
		if($cats){
			foreach($cats as $cat){
				$list[$cat->id] = $cat->title;
			}
		}
		return $list;
	}
	public function getSubCategorys(){
		$list = array();
		$criteria = new CDbCriteria ();
		$criteria->addCondition ( 'parent_id IS  NOT NULL');
		$criteria->order = 'title asc';
		$criteria->addCondition ( 'status ='.ItemCategory::STATUS_ACTIVE);
		$cats = ItemCategory::model ()->findAll ( $criteria);
		if($cats){
			foreach($cats as $cat){
				$list[$cat->id] = $cat->title;
			}
		}
		return $list;
	}
	public function getParentCompanys(){
		$list = array();
		$criteria = new CDbCriteria ();
		$criteria->addCondition ( 'parent_id IS  NULL');
		$criteria->order = 'title asc';
		$criteria->addCondition ( 'status ='.ItemCompany::STATUS_ACTIVE);
		$cats = ItemCompany::model ()->findAll ( $criteria);
		if($cats){
			foreach($cats as $cat){
				$list[$cat->id] = $cat->title;
			}
		}
		return $list;
	}
	
	public static function getAllVendors($id = null) {
		$user = Yii::app()->user->model;
		$vendor_arr = array ();
		$exist = [];
		if($id != null){
			$criteria1 = new CDbCriteria ();
			$criteria1->addCondition ( 'item_detail_id =' . $id );
			$itemvendors = ItemVendor::model ()->findAll ( $criteria1 );
			if($itemvendors){
				foreach($itemvendors as $itemvendor){
					$exist[] = $itemvendor->vendor_id;
				}
			}
		}
		$criteria = new CDbCriteria ();
		if($user->role_id == 1){
		$criteria->addNotInCondition('id', $exist);
		}else{
			$criteria->addCondition('create_user_id ='.$user->id);
		}
		$criteria->order = 'name asc';
		$criteria->addCondition ( 'status =' . Vendor::STATUS_ACTIVE );
		$vendors = Vendor::model ()->findAll ( $criteria );
		if ($vendors != null) {
			foreach ( $vendors as $vendor ) {
				$vendor_arr [$vendor->id] = $vendor->name;
			}
		}
	
		return $vendor_arr;
	}
	public static function getAllActiveVendors() {
	
		$outlet_arr	= array();
		$criteria = new CDbCriteria ();
		$criteria->order = 'name asc';
		$criteria->addCondition ( 'status =' . Vendor::STATUS_ACTIVE );
		$outlets = Vendor::model ()->findAll ( $criteria );
		if ($outlets != null) {
			foreach ( $outlets as $outlet ) {
				$outlet_arr [$outlet->id] = $outlet->name;
			}
		}
	
		return $outlet_arr;
	}
	public static function getAllOutlets() {
		
		$outlet_arr	= array();	
		$criteria = new CDbCriteria ();
		$criteria->order = 'title asc';
		$criteria->addCondition ( 'status =' . Outlet::STATUS_ACTIVE );
		$outlets = Outlet::model ()->findAll ( $criteria );
		if ($outlets != null) {
			foreach ( $outlets as $outlet ) {
				$outlet_arr [$outlet->id] = $outlet->title;
			}
		}
	
		return $outlet_arr;
	}
	public function getSelectedVendors(){
		$vendor_ids = array();
		$vendors = ItemVendor::model()->findAllByAttributes(array('item_detail_id'=>$this->id));
		if($vendors != null)
		{
			foreach($vendors as $vendor)
			{
				$vendor_ids[] = $vendor->vendor_id;
			}
		}
	
		return $vendor_ids;
	}
	
	public static function RemoveVendors($id){
		ItemVendor::model()->deleteAllByAttributes(array('item_detail_id'=>$id));
		return true;
	}
	public function setAllNewValues($rows) {
	
		$output = 0;
		$count = count($rows);
	
			
		if ($count > 1) {
	
			$o = explode(',', $rows[0]);
			$arrays = array_flip($o);
			$set = true;
			$transaction = Yii::app()->db->beginTransaction();
			try {
				$item = null;
				for ($i = 1; $i < $count; $i++) {
					$itemcat_values = explode(',', $rows[$i]);
					if (isset($arrays['Title']) || isset($arrays['﻿"Title"']) || isset($arrays['���"Title"'])) {
						$criteria = new CDbCriteria();
						if (isset($arrays['Title'])) {
							$criteria->compare('title',$itemcat_values[$arrays['Title']]);
					} else if(isset($arrays['﻿"Title"'])) {
							$criteria->compare('title',$itemcat_values[$arrays['﻿"Title"']]);
						
						}else{
							$criteria->compare('title',$itemcat_values[$arrays['���"Title"']]);
						}
						$item = Item::model()->find($criteria);
					}
					
	  
	               if($item == null){
					$item = new Item();
	               }
	
					if (isset($arrays['Title']) || isset($arrays['﻿"Title"']) || isset($arrays['���"Title"'])) {
	
						if (isset($arrays['Title'])) {
							$item->title = $itemcat_values[$arrays['Title']];
	
						} else if(isset($arrays['﻿"Title"'])) {
							$item->title = $itemcat_values[$arrays['﻿"Title"']];
						}else{
							$item->title = $itemcat_values[$arrays['���"Title"']];
						}
					}
					if (isset($arrays['Short Name']) && ($itemcat_values[$arrays['Short Name']] != '')) {
					
						$item->short_name =$itemcat_values[$arrays['Short Name']];
					}else{
					
					$small = substr($item->title, 0, 10);
					$item->short_name =$small;
					}
					/* if (isset($arrays['Bill Description'])) {
	
						$item->description =$itemcat_values[$arrays['Bill Description']];
					} */
						
					//if (isset($arrays['Product Code'])) {
							
						$item->item_code = User::randomBarcode('5');
					//}
					if (isset($arrays['HSN Code'])) {
							
						$item->hsn_code =$itemcat_values[$arrays['HSN Code']];
					}
				
							
						$item->item_type = 0;
					
					
							
						$item->status = 0;
					
					/* if (isset($arrays['Item Type'])) {
							
						$item->item_type = Item:: getTypeKeyOptions($itemcat_values[$arrays['Item Type']]);
					}
					if (isset($arrays['Status'])) {
							
						$item->status = Item:: getStatusKeyOptions($itemcat_values[$arrays['Status']]);
					} */
					if (isset($arrays['Item Category']) && ($arrays['Item Category']) != '') {
						$criteria = new CDbCriteria();
						$criteria->compare('title',$itemcat_values[$arrays['Item Category']]);
						$category = ItemCategory::model()->find($criteria);
						if($category){
							$item->category_id =$category->id;
						}
	
					}
					if (isset($arrays['Item SubCategory']) && ($arrays['Item SubCategory']) != '') {
						$criteria = new CDbCriteria();
						$criteria->compare('title',$itemcat_values[$arrays['Item SubCategory']]);
						$category = ItemCategory::model()->find($criteria);
						if($category){
							$item->category_id =$category->id;
						}
					
					}
					if (isset($arrays['Item Company']) && $arrays['Item Company']) {
						$criteria = new CDbCriteria();
						$criteria->compare('title',$itemcat_values[$arrays['Item Company']]);
						$category = ItemCompany::model()->find($criteria);
						if($category){
							$item->company_id =$category->id;
						}
							
					}
					if (isset($arrays['Sub Category'])  && $arrays['Sub Category']) {
						$criteria = new CDbCriteria();
						$criteria->compare('title',$itemcat_values[$arrays['Sub Category']]);
						$category = ItemCompanyCategory::model()->find($criteria);
						if($category){
							$item->sub_company_id =$category->id;
						}
							
					}
					if (isset($arrays['MRP'])) {
							
						$item->mrp = $itemcat_values[$arrays['MRP']];
					}
					if (isset($arrays['Sale Price'])) {
							
						$item->sale_price = $itemcat_values[$arrays['Sale Price']];
					}
					if (isset($arrays['Purchase Price'])) {
							
						$item->purchase_price = $itemcat_values[$arrays['Purchase Price']];
					}
					if (isset($arrays['Weight'])) {
							
						$item->weight = $itemcat_values[$arrays['Weight']];
					}
					if (isset($arrays['Whole Sale'])) {
							
						$item->whole_sale = $itemcat_values[$arrays['Whole Sale']];
					}
					if (isset($arrays['Opening Stock'])) {
							
						$item->opening_stock = $itemcat_values[$arrays['Opening Stock']];
					}
					if (isset($arrays['Minimum Quantity'])) {
							
						$item->min_qty = $itemcat_values[$arrays['Minimum Quantity']];
					}
					if (isset($arrays['Maximum Quantity'])) {
							
						$item->max_qty = $itemcat_values[$arrays['Maximum Quantity']];
					}
					if (isset($arrays['Reorder Quantity'])) {
							
						$item->reorder_qty = $itemcat_values[$arrays['Reorder Quantity']];
					}
					$role = UserRole::model()->findByAttributes(array('title'=>'Admin'));
					$user = Yii::app()->user->model;
					if($user->role_id != $role->id ){
						$item->state_id = Item::STATUS_INACTIVE;
					}
				
					if ($item->save()) {
						$itemdetail = new ItemDetail();
						if (isset($arrays['Tax'])) {
							$criteria = new CDbCriteria();
							$criteria->compare('title',$itemcat_values[$arrays['Tax']]);
							$tax = Tax::model()->find($criteria);
							if($tax){
								$itemdetail->tax_id =$tax->id;
							}
								
						}
						if (isset($arrays['Opening Stock'])) {
								
							$itemdetail->open_stock_qty = $itemcat_values[$arrays['Opening Stock']];
						}
						if (isset($arrays['Barcode'])) {
							$itemdetail->bar_code =$itemcat_values[$arrays['Barcode']];
						}
						$itemdetail->outlet_id = 5;
						$itemdetail->item_id = $item->id;
						
						if($itemdetail->save()){
							
							$batch_no =  User::randomBarcode('5');
							
								$itemstock = ItemStock ::model()->findByAttributes(array('batch_number'=>$batch_no,'outlet_id'=>$itemdetail->outlet_id ,
										'vendor_id'=>'0'
								));
								if($itemstock == null){
									$itemstock = new ItemStock;
								}
							
								$itemstock->balance_qty = $itemdetail->open_stock_qty;
								$itemstock->purchase_qty = $itemdetail->open_stock_qty;
								$itemstock->outlet_id = $itemdetail->outlet_id ;
								$itemstock->vendor_id = 0;
								$itemstock->mrp = $itemdetail->getItemDetailMrp();
								$itemstock->base_price = $itemdetail->getItemDetailSaleRate();
								$itemstock->batch_number = $batch_no;
								$itemstock->item_id = $item->id;
								$itemstock->item_detail_id = $itemdetail->id;
								if($itemstock->save()){
										
								}else{
									print_r($itemstock->getErrors());exit;
								}
							
							
						}else{
							print_R($itemdetail->getErrors());
							exit;
						}
						
					} else {
						print_R($item->getErrors());
						exit;
						$set = false;
					}
				}
				if ($set == true) {
					$transaction->commit();
					return 1;
				}
			} catch (Exception $e) {
				$transaction->rollback();
			}
		}
		return $output;
	}
	public function setAllValues($rows) {
	
		$output = 0;
		$count = count($rows);
	
			
		if ($count > 1) {
	
			$o = explode(',', $rows[0]);
			$arrays = array_flip($o);
			$set = true;
			$item = null;
			$transaction = Yii::app()->db->beginTransaction();
			try {
				$new = false;
				for ($i = 1; $i < $count; $i++) {
					$itemcat_values = explode(',', $rows[$i]);
					Yii::log ( CVarDumper::dumpAsString ( $itemcat_values ), CLogger::LEVEL_WARNING, 'item_values' );
	
					if (isset($arrays['Title']) || isset($arrays['﻿"Title"']) || isset($arrays['���"Title"'])) {
						$criteria = new CDbCriteria();
						if (isset($arrays['Title'])) {
							$title = str_replace(";",",",$itemcat_values[$arrays['Title']]);
							$title = str_replace("!",".",$title);
							$criteria->compare('title',$title);
						} else if(isset($arrays['﻿"Title"'])) {
							$title = str_replace(";",",",$itemcat_values[$arrays['﻿"Title"']]);
							$title = str_replace("!",".",$title);
							$criteria->compare('title',$title);
					
						}else{
							$title = str_replace(";",",",$itemcat_values[$arrays['���"Title"']]);
							$title = str_replace("!",".",$title);
							$criteria->compare('title',$title);
							
						}
						
						$item = Item::model()->find($criteria);
					}
					Yii::log ( CVarDumper::dumpAsString ( $item ), CLogger::LEVEL_WARNING, '$alreadyitem' );
					
					if($item == null){
						$new = true;
						$item = new Item();
					}
						
					if (isset($arrays['Title']) || isset($arrays['﻿"Title"']) || isset($arrays['���"Title"'])) {
					
						if (isset($arrays['Title'])) {
							$item->title = str_replace(";",",",$itemcat_values[$arrays['Title']]);
							$item->title = str_replace("!",".",$item->title);
						} else if(isset($arrays['﻿"Title"'])) {
							
							$item->title = str_replace(";",",",$itemcat_values[$arrays['﻿"Title"']]);
							$item->title = str_replace("!",".",$item->title);
						}else{
							$item->title =str_replace(";",",",$itemcat_values[$arrays['���"Title"']]);
							$item->title = str_replace("!",".",$item->title);
						}
					}
					$small = substr($item->title, 0, 10);
					$item->short_name =$small;
					if (isset($arrays['Bill Description'])) {
						
						$item->description =$itemcat_values[$arrays['Bill Description']];
					}
					
					if (isset($arrays['Product Code'])) {
					
						$item->item_code =$itemcat_values[$arrays['Product Code']];
					}else{
						$item->item_code = User::randomBarcode(5);
					}
					if (isset($arrays['Short Name'])) {
						if($itemcat_values[$arrays['Short Name']] != ''){
						$com = str_replace("!",".",$itemcat_values[$arrays['Short Name']]);
						$item->short_name =$itemcat_values[$arrays['Short Name']];
						}else{
							$item->short_name = substr($item->title,10);
						}
					}
					if (isset($arrays['HSN Code'])) {
					
						$item->hsn_code =$itemcat_values[$arrays['HSN Code']];
					}
					if (isset($arrays['Item Type'])) {
							
						$item->item_type = Item:: getTypeKeyOptions($itemcat_values[$arrays['Item Type']]);
					}
					if (isset($arrays['Status'])) {
							
						$item->status = Item:: getStatusKeyOptions($itemcat_values[$arrays['Status']]);
					}
					if (isset($arrays['Item Category'])) {
						$cat = str_replace(";",",",$itemcat_values[$arrays['Item Category']]);
						$criteria = new CDbCriteria();
						$criteria->compare('title',$cat);
						$category = ItemCategory::model()->find($criteria);
						if($category){
							$item->category_id =$category->id;
						}
	
					}
					if (isset($arrays['Sub Category'])) {
						$sbcat = str_replace(";",",",$itemcat_values[$arrays['Sub Category']]);
						$criteria = new CDbCriteria();
						$criteria->compare('title',$sbcat);
						$category = ItemCategory::model()->find($criteria);
						if($category){
							$item->sub_category_id =$category->id;
						}
					
					}
					if (isset($arrays['Item Company'])) {
						$com = str_replace(";",",",$itemcat_values[$arrays['Item Company']]);
						$criteria = new CDbCriteria();
						$criteria->compare('title',$com);
						$category = ItemCompany::model()->find($criteria);
						if($category){
							$item->company_id =$category->id;
						}
					
					}
					if (isset($arrays['Item Company Category'])) {
						$comcat = str_replace(";",",",$itemcat_values[$arrays['Item Company Category']]);
						$criteria = new CDbCriteria();
						$criteria->compare('title',$comcat);
						$category = ItemCompanyCategory::model()->find($criteria);
						if($category){
							$item->sub_company_id =$category->id;
						}
							
					}
					
					if (isset($arrays['MRP'])) {
							
						$item->mrp = $itemcat_values[$arrays['MRP']];
					}else{
						if($new == true){
						$item->mrp = '20.00';
						}
					}
					if (isset($arrays['Sale Price'])) {
							
						$item->sale_price = $itemcat_values[$arrays['Sale Price']];
					}else{
						if($new == true){
						$item->sale_price = '20.00';
						}
					}
					if (isset($arrays['Purchase Price'])) {
							
						$item->purchase_price = $itemcat_values[$arrays['Purchase Price']];
					}
					if (isset($arrays['Weight'])) {
							
						$item->weight = $itemcat_values[$arrays['Weight']];
					}
					if (isset($arrays['Whole Sale'])) {
							
						$item->whole_sale = $itemcat_values[$arrays['Whole Sale']];
					}
					if (isset($arrays['Opening Stock'])) {
							
						$item->opening_stock = $itemcat_values[$arrays['Opening Stock']];
					}
					if (isset($arrays['Minimum Quantity'])) {
					if( $itemcat_values[$arrays['Minimum Quantity']] != ''){
						$item->min_qty = $itemcat_values[$arrays['Minimum Quantity']];
					}
					}
					if (isset($arrays['Maximum Quantity'])) {
						if( $itemcat_values[$arrays['Maximum Quantity']] != ''){
						$item->max_qty = $itemcat_values[$arrays['Maximum Quantity']];
						}
					}
					if (isset($arrays['Reorder Quantity'])) {
						if( $itemcat_values[$arrays['Reorder Quantity']] != ''){
						$item->reorder_qty = $itemcat_values[$arrays['Reorder Quantity']];
						}
					}
					$role = UserRole::model()->findByAttributes(array('title'=>'Admin'));
					$user = Yii::app()->user->model;
					if($user->role_id != $role->id ){
						$item->state_id = Item::STATUS_INACTIVE;
					}
					Yii::log ( CVarDumper::dumpAsString ( $item ), CLogger::LEVEL_WARNING, '$item' );
					if ($item->save()) {
						/* $itemDetailoldbars = ItemDetail::model ()->findAllByAttributes ( array (
								'item_id' =>  $item->id
								
						) );
						if($itemDetailoldbars){
							foreach($itemDetailoldbars as $itemDetailoldbar){
								$itemDetailoldbar->delete();
							}
						} */
						if (isset($arrays['Barcode']) && ($itemcat_values[$arrays['Barcode']] != '')) {
							$criteria = new CDbCriteria();
							$outlet = Outlet::model()->find($criteria);
							$itemDetailbars = ItemDetail::model ()->findAllByAttributes ( array (
									'bar_code' =>  $itemcat_values[$arrays['Barcode']],
								//	'outlet_id' => $outlet->id,
								//	'item_id' => $item->id,
							) );
							
							if($itemDetailbars){
								foreach($itemDetailbars as $itemDetailbar){
									$itemDetailbar->delete();
								}
							}
							$itemDetail = new ItemDetail();
							
							Yii::log ( CVarDumper::dumpAsString ( $itemDetail ), CLogger::LEVEL_WARNING, '$itemDetail' );
							$barcodestr = $itemcat_values[$arrays['Barcode']];
							$stringlength = strlen($barcodestr);
							if($stringlength < 9){
								$itemDetail->company_bar_code = ItemDetail::IS_COMPANY;
								$itemDetail->bar_code = $itemcat_values[$arrays['Barcode']];
							}else{
								$itemDetail->company_bar_code = ItemDetail::IS_NOT_COMPANY;
							$itemDetail->bar_code = $itemcat_values[$arrays['Barcode']];
							}
							$itemDetail->mrp = $item->mrp;
							$itemDetail->open_stock_qty = $item->opening_stock;
							if($outlet)
							$itemDetail->outlet_id = $outlet->id;
							$itemDetail->item_id = $item->id;
							if (isset($arrays['Tax'])) {
								$criteria = new CDbCriteria();
								$criteria->compare('title',$itemcat_values[$arrays['Tax']]);
								$category = Tax::model()->find($criteria);
								if($category){
									$itemDetail->tax_id = $category->id;
									
								}else{
									$newtax = new Tax();
									$newtax->title = $itemcat_values[$arrays['Tax']];
									if($newtax->save()){
										$itemDetail->tax_id = $newtax->id;
									}
								}
							}
							if($itemDetail->save()){
								$batch_no =  User::randomBarcode('5');
									
								$itemstock = ItemStock ::model()->findByAttributes(array('outlet_id'=>$itemDetail->outlet_id ,
										'vendor_id'=>'0','item_detail_id'=>$itemDetail->id ,'item_id'=>$item->id 
								));
								if($itemstock == null){
									$itemstock = new ItemStock;
								}
									
								$itemstock->balance_qty = trim($itemDetail->open_stock_qty);
								$itemstock->purchase_qty = trim($itemDetail->open_stock_qty);
								$itemstock->outlet_id = $itemDetail->outlet_id ;
								$itemstock->vendor_id = 0;
								$itemstock->mrp = $item->mrp;
								$itemstock->base_price = $item->sale_price;
								$itemstock->batch_number = $batch_no;
								$itemstock->item_id = $item->id;
								$itemstock->item_detail_id = $itemDetail->id;
								if($itemstock->save()){
								
								}else{
									print_r($itemstock->getErrors());exit;
								}
								if ($itemDetail->tax_id != null) {
									
										$itemtax= new ItemTax();
										$itemtax->item_detail_id =$itemDetail->id;
										$itemtax->tax_id =$itemDetail->tax_id;
										$itemtax->save();
									
								}
							}else{
								
									print_R($itemDetail->getErrors());
									exit;
									$set = false;
								
							}
							
						}
					} else {
						echo '<pre>';
						print_R($item);
						print_R($item->getErrors());
						exit;
						$set = false;
					}
				}
				if ($set == true) {
					$transaction->commit();
					return 1;
				}
			} catch (Exception $e) {
				$transaction->rollback();
			}
		}
		return $output;
	}
	
	public static function getHsnCodeList(){
		$list = array();
		$items = Item::model()->findAll();
		if($items){
			foreach($items as $item){
				$list[$item->id] = $item->hsn_code;
			}
		}
		return $list;
	}
	
	public static function getActiveItems() {
	
		$outlet_arr	= array();
		$criteria = new CDbCriteria ();
		$criteria->order = 'title asc';
		$criteria->addCondition ( 'status =' . Item::STATUS_ACTIVE );
		$items = Item::model ()->findAll ( $criteria );
		if ($items != null) {
			foreach ( $items as $item ) {
				$outlet_arr [$item->id] = $item->title;
			}
		}
	
		return $outlet_arr;
	}
	public static function getActiveBarcodeItems() {
	
		$outlet_arr	= array();
		$criteria = new CDbCriteria ();
		$criteria->limit = '1000';
		$criteria->addCondition ( 'status =' . Item::STATUS_ACTIVE );
		$items = ItemDetail::model ()->findAll ( $criteria );
		if ($items != null) {
			foreach ( $items as $item ) {
				$outlet_arr [$item->id] = $item->item->title.'('.$item->bar_code.')';
			}
		}
	
		return $outlet_arr;
	}
	public static function getMainLatestVendorName($id){
		$name = '';
		$criteria = new CDbCriteria ();
		$criteria->order = 'id desc';
		$criteria->limit = '1';
		$criteria->addCondition ( 'item_detail_id =' . $id );
		$itemvendor = ItemVendor::model ()->find( $criteria );
		if($itemvendor){
			$vendor = Vendor::model()->findByPk($itemvendor->vendor_id);
			if($vendor){
				$name = $vendor->name;
			}
		}
		return $name;
	}
	public function getLatestVendorName(){
		$name = '';
		$criteria = new CDbCriteria ();
		$criteria->order = 'id desc';
		$criteria->limit = '1';
		$criteria->addCondition ( 'item_detail_id =' . $this->id );
		$itemvendor = ItemVendor::model ()->find( $criteria );
		if($itemvendor){
			$vendor = Vendor::model()->findByPk($itemvendor->vendor_id);
			if($vendor){
				$name = $vendor->name;
			}
		}
		return $name;
	}
	public function setAllVendorValues($rows) {
	
		$output = 0;
		$count = count($rows);
	
			
		if ($count > 1) {
	
			$o = explode(',', $rows[0]);
			$arrays = array_flip($o);
			$set = true;
			$transaction = Yii::app()->db->beginTransaction();
			try {
				for ($i = 1; $i < $count; $i++) {
					$item_values = explode(',', $rows[$i]);
	
	
					$itemvendor = new ItemVendor();
	
					if (isset($arrays['Title']) || isset($arrays['﻿"Title"']) || isset($arrays['���"Title"'])) {
	
						if (isset($arrays['Title'])) {
							$criteria = new CDbCriteria();
							$criteria->compare('title', $item_values[$arrays['Title']]);
							$item = Item::model()->find($criteria);
							Yii::log ( CVarDumper::dumpAsString ( $item ), CLogger::LEVEL_WARNING, '$$$$item' );
							if($item){
								$itemvendor->item_detail_id = $item->id;
							}
	
						} else if(isset($arrays['﻿"Title"'])) {
							$criteria = new CDbCriteria();
							$criteria->compare('title',$item_values[$arrays['﻿"Title"']]);
							$item = Item::model()->find($criteria);
							if($item){
								$itemvendor->item_detail_id = $item->id;
							}
						}else{
							$criteria = new CDbCriteria();
							$criteria->compare('title',$item_values[$arrays['���"Title"']]);
							$item = Item::model()->find($criteria);
							if($item){
								$itemvendor->item_detail_id = $item->id;
							}
							
						}
					}
	
	
					
					if (isset($arrays['Vendor'])) {
						$criteria = new CDbCriteria();
						$criteria->compare('name',$item_values[$arrays['Vendor']]);
						$vendor = Vendor::model()->find($criteria);
						Yii::log ( CVarDumper::dumpAsString ( $vendor ), CLogger::LEVEL_WARNING, '$$vendor' );
						if($vendor){
							$itemvendor->vendor_id = $vendor->id;
						}
					}
					$Alreadyitemvendor = ItemVendor::model()->findByAttributes(array('item_detail_id'=>$itemvendor->item_detail_id,
							'vendor_id'=>$itemvendor->vendor_id
					));
					Yii::log ( CVarDumper::dumpAsString ( $Alreadyitemvendor ), CLogger::LEVEL_WARNING, '$Alreadyitemvendor' );
	                if($Alreadyitemvendor == null && $itemvendor->vendor_id != null && $itemvendor->item_detail_id != null){
					if ($itemvendor->save()) {
	
							
					} else {
						print_R($itemvendor->getErrors());
						exit;
						$set = false;
					}
					}
				}
				if ($set == true) {
					$transaction->commit();
					return 1;
				}
			} catch (Exception $e) {
				$transaction->rollback();
			}
		}
		return $output;
	}
	
	public function setAllStockValues($rows){
		$output = 0;
		$count = count($rows);
		
			
		if ($count > 1) {
		
			$o = explode(',', $rows[0]);
			$arrays = array_flip($o);
			$set = true;
			$transaction = Yii::app()->db->beginTransaction();
			try {
				for ($i = 1; $i < $count; $i++) {
					$item_values = explode(',', $rows[$i]);
		
		
					$itemstock = new ItemStock();
					$product_code = null;
					$product_title = null;
					if (isset($arrays['Prod Code']) || isset($arrays['﻿"Prod Code"']) || isset($arrays['���"Prod Code"'])) {
		
						if (isset($arrays['Prod Code'])) {
							$product_code = $item_values[$arrays['Prod Code']];
						} else if(isset($arrays['﻿"Prod Code"'])) {
							$product_code = $item_values[$arrays['﻿"Prod Code"']];
						}else{
							$product_code = $item_values[$arrays['���"Prod Code"']];
						}
					}
		
		
						
					if (isset($arrays['Product'])) {
						$product_title = $item_values[$arrays["Product"]];
					}
					
					if($product_title  != null && $product_code != null){
					$criteria = new CDbCriteria();
					$criteria->compare('title',$product_title);
					//$criteria->compare('item_code',$product_code);
					$getItem = Item::model()->find($criteria);
					Yii::log ( CVarDumper::dumpAsString ( $getItem ), CLogger::LEVEL_WARNING, '$getItem' );
					if($getItem){
						$stocks = ItemStock::model()->deleteAllByAttributes(array('item_id'=>$getItem->id));
						$barcode = $getItem->getItemBarcodes();
						if($barcode){
							$criteria1 = new CDbCriteria();
							$criteria1->compare('item_id',$getItem->id);
							$criteria1->compare('bar_code',$barcode);
							$getItemDetail = ItemDetail::model()->find($criteria1);
							Yii::log ( CVarDumper::dumpAsString ( $getItemDetail ), CLogger::LEVEL_WARNING, '$getItemDetail' );
							if($getItemDetail){
								$batch_no =  User::randomBarcode('5');
						$itemstock = new ItemStock();
						if (isset($arrays['Total'])) {
						$itemstock->balance_qty =  $item_values[$arrays["Total"]];
						}
						if (isset($arrays['Opening'])) {
						$itemstock->purchase_qty =  $item_values[$arrays["Opening"]];
						}
						$itemstock->outlet_id = $getItemDetail->outlet_id;
						$itemstock->vendor_id = 0;
					
						if (isset($arrays['MRP'])) {
							$itemstock->mrp = $item_values[$arrays["MRP"]];
						}else{
							if($getItemDetail){
							$itemstock->mrp = $getItemDetail->mrp;
							}else{
								$itemstock->mrp = $getItem->mrp;
							}
						}
						$itemstock->base_price = $getItem->sale_price;
						$itemstock->batch_number = $batch_no;
						$itemstock->item_id = $getItem->id;
						$itemstock->item_detail_id = $getItemDetail->id;

						if ($itemstock->save()) {
						
						
						} else {
							print_R($itemstock->getErrors());
							exit;
							$set = false;
						}
						}
						}
						
					}
					}
					
					
					
				}
				if ($set == true) {
					$transaction->commit();
					return 1;
				}
			} catch (Exception $e) {
				$transaction->rollback();
			}
		}
		return $output;
	}
	
	public function getStockQuantity(){
		
	}
		public function getItemAdjustedStock(){
		$qty = '';
		
		$criteria = new CDbCriteria();
		$criteria->compare('status',MrsAdjust::STATUS_PENDING);
		$criteria->compare('item_id',$this->id);
		$criteria->order = 'id desc';
		$mrsadjust = MrsAdjust::model()->find($criteria);
		if($mrsadjust){
			$qty = $mrsadjust->qty;
		}
		return $qty;
	}
	public function getItemAdjustedType(){
		$type = '0';
	
		$criteria = new CDbCriteria();
		$criteria->compare('status',MrsAdjust::STATUS_PENDING);
		$criteria->compare('item_id',$this->id);
		$criteria->order = 'id desc';
		$mrsadjust = MrsAdjust::model()->find($criteria);
		if($mrsadjust){
			$type = $mrsadjust->type_id;
		}
		return $type;
	}
	
	public function getPurchaseQty(){
		
		$qty = '0';
		$criteria1 = new CDbCriteria();
		$criteria1->addCondition('t.item_id ='.$this->id);
		$criteria1->select = 'sum(t.approved_qty) as approved_qty';
		$orderitem = PurchaseBillDetail::model()->find($criteria1);
		if($orderitem){
			$qty = $orderitem->approved_qty;
		}
		if($qty == ''){
			$qty = '0';
		}
		Yii::log ( CVarDumper::dumpAsString ( $this->id ), CLogger::LEVEL_WARNING, '$this->item_id' );
		Yii::log ( CVarDumper::dumpAsString ( $qty ), CLogger::LEVEL_WARNING, '$purqty' );
		return $qty;
	}
	public function getSaleQty(){
		$qty = '0';
		$criteria1 = new CDbCriteria();
		$criteria1->addCondition('t.item_id ='.$this->id);
		$criteria1->select = 'sum(t.qty) as qty';
		$orderitem = OrderItem::model()->find($criteria1);
		if($orderitem){
			$qty = $orderitem->qty;
		}
		if($qty == ''){
			$qty = '0';
		}
		Yii::log ( CVarDumper::dumpAsString ( $this->id ), CLogger::LEVEL_WARNING, '$this->item_id' );
		Yii::log ( CVarDumper::dumpAsString ( $qty ), CLogger::LEVEL_WARNING, '$saleqty' );
		return $qty;
	}
	public function getCssClass()
	{
		$cssClass='';
		
		$purchase_qty = $this->getPurchaseQty();
		$per_purchase_qty = (($this->getPurchaseQty()) - (10/100) * ($this->getPurchaseQty()));
		$sale_qty = $this->getSaleQty();
		Yii::log ( CVarDumper::dumpAsString ( $this ), CLogger::LEVEL_WARNING, '$mrs' );
		 if($sale_qty < $per_purchase_qty){
			$cssClass='mrsred';
		}
		return $cssClass;
	}
	public function getItemSaleQty($start_date,$end_date){
		$qty = '0';
		$criteria1 = new CDbCriteria();
		$criteria1->addCondition('t.item_id ='.$this->id);
		$criteria1->addBetweenCondition('create_date', $start_date, $end_date);
		$criteria1->select = 'sum(t.qty) as qty';
		$orderitem = OrderItem::model()->find($criteria1);
		if($orderitem){
			$qty = $orderitem->qty;
		}
		if($qty == ''){
			$qty = '0';
		}
		Yii::log ( CVarDumper::dumpAsString ( $this->id ), CLogger::LEVEL_INFO, '$this->item_id' );
		Yii::log ( CVarDumper::dumpAsString ( $qty ), CLogger::LEVEL_INFO, '$saleqty' );
		return $qty;
	}
	public function getMaximumQty(){
		return $this->max_qty;
		$max_qty = $this->max_qty;
		
		$criteria1 = new CDbCriteria();
		$criteria1->addCondition('item_id ='.$this->id);
		$criteria1->addCondition('status ='.MrsDetail::STATUS_DONE);
		$criteria1->order = 'id desc';
		$last_mrs_detail = MrsDetail::model()->find($criteria1);
		Yii::log ( CVarDumper::dumpAsString ( $last_mrs_detail ), CLogger::LEVEL_INFO, '$last_mrs_detail' );
		
		if($last_mrs_detail){
			$mrs = Mrs::model()->findByPk($last_mrs_detail->mrs_id);
			$mrs_date = date('Y-m-d',strtotime($mrs->mrs_date));
			$curent_date = date('Y-m-d');
			$sale_qty_till_date = $this->getItemSaleQty($mrs_date,$curent_date);
			Yii::log ( CVarDumper::dumpAsString ( $sale_qty_till_date ), CLogger::LEVEL_INFO, '$sale_qty_till_date' );
				
			$lastsale_qty_till_date = 0;
			$criteria2 = new CDbCriteria();
			$criteria2->addCondition('item_id ='.$this->id);
			$criteria2->addCondition('status ='.MrsDetail::STATUS_DONE);
			$criteria2->addCondition('mrs_id !='.$last_mrs_detail->mrs_id);
			$criteria2->order = 'id desc';
			$seclast_mrs_detail = MrsDetail::model()->find($criteria2);
			Yii::log ( CVarDumper::dumpAsString ( $seclast_mrs_detail ), CLogger::LEVEL_INFO, '$$seclast_mrs_detail' );
			
			if($seclast_mrs_detail){
				$lastmrs = Mrs::model()->findByPk($seclast_mrs_detail->mrs_id);
				$lastmrs_date = date('Y-m-d',strtotime($lastmrs->mrs_date));
				$lastsale_qty_till_date = $this->getItemSaleQty($lastmrs_date,$mrs_date);
			}
			Yii::log ( CVarDumper::dumpAsString ( $lastsale_qty_till_date ), CLogger::LEVEL_INFO, '$lastsale_qty_till_date' );
				
			if($sale_qty_till_date >$lastsale_qty_till_date && $lastsale_qty_till_date != 0){
				$inc_sale = $sale_qty_till_date - $lastsale_qty_till_date;
				$inc_sale_per = ($inc_sale/$lastsale_qty_till_date)*100;
				Yii::log ( CVarDumper::dumpAsString ( $inc_sale_per ), CLogger::LEVEL_INFO, '$inc_sale_per' );
					
				if($inc_sale_per > 0){
					$get_inc = $max_qty *($inc_sale_per/100);
					$max_qty = $max_qty + $get_inc;
					$max_qty = round($max_qty);
					Yii::log ( CVarDumper::dumpAsString ( $get_inc ), CLogger::LEVEL_INFO, '$get_inc' );
					Yii::log ( CVarDumper::dumpAsString ( $max_qty ), CLogger::LEVEL_INFO, '$max_qty' );
						
				}
				
			}else{
				if($lastsale_qty_till_date > $sale_qty_till_date){
				$dec_sale = $lastsale_qty_till_date - $sale_qty_till_date;
				$dec_sale_per = ($dec_sale/$lastsale_qty_till_date)*100;
				Yii::log ( CVarDumper::dumpAsString ( $dec_sale_per ), CLogger::LEVEL_INFO, '$dec_sale_per' );
					
				if($dec_sale_per > 0){
					$get_inc = $max_qty *($dec_sale_per/100);
					$max_qty = $max_qty - $get_inc;
					$max_qty = round($max_qty);
					Yii::log ( CVarDumper::dumpAsString ( $get_inc ), CLogger::LEVEL_INFO, '$get_inc' );
					Yii::log ( CVarDumper::dumpAsString ( $max_qty ), CLogger::LEVEL_INFO, '$max_qty' );
				
				}
			}
			}
		}
		if($max_qty == 0){
			$max_qty = $this->max_qty;
		}
		return $max_qty;
	}
	
	public function getMinimumQty(){
		return $this->min_qty;
		$max_qty = $this->min_qty;
	
		$criteria1 = new CDbCriteria();
		$criteria1->addCondition('item_id ='.$this->id);
		$criteria1->addCondition('status ='.MrsDetail::STATUS_DONE);
		$criteria1->order = 'id desc';
		$last_mrs_detail = MrsDetail::model()->find($criteria1);
		Yii::log ( CVarDumper::dumpAsString ( $last_mrs_detail ), CLogger::LEVEL_INFO, '$last_mrs_detail' );
	
		if($last_mrs_detail){
			$mrs = Mrs::model()->findByPk($last_mrs_detail->mrs_id);
			$mrs_date = date('Y-m-d',strtotime($mrs->mrs_date));
			$curent_date = date('Y-m-d');
			$sale_qty_till_date = $this->getItemSaleQty($mrs_date,$curent_date);
			Yii::log ( CVarDumper::dumpAsString ( $sale_qty_till_date ), CLogger::LEVEL_INFO, '$sale_qty_till_date' );
	
			$lastsale_qty_till_date = 0;
			$criteria2 = new CDbCriteria();
			$criteria2->addCondition('item_id ='.$this->id);
			$criteria2->addCondition('status ='.MrsDetail::STATUS_DONE);
			$criteria2->addCondition('mrs_id !='.$last_mrs_detail->mrs_id);
			$criteria2->order = 'id desc';
			$seclast_mrs_detail = MrsDetail::model()->find($criteria2);
			Yii::log ( CVarDumper::dumpAsString ( $seclast_mrs_detail ), CLogger::LEVEL_INFO, '$$seclast_mrs_detail' );
				
			if($seclast_mrs_detail){
				$lastmrs = Mrs::model()->findByPk($seclast_mrs_detail->mrs_id);
				$lastmrs_date = date('Y-m-d',strtotime($lastmrs->mrs_date));
				$lastsale_qty_till_date = $this->getItemSaleQty($lastmrs_date,$mrs_date);
			}
			Yii::log ( CVarDumper::dumpAsString ( $lastsale_qty_till_date ), CLogger::LEVEL_INFO, '$lastsale_qty_till_date' );
	
			if($sale_qty_till_date >$lastsale_qty_till_date && $lastsale_qty_till_date != 0){
				$inc_sale = $sale_qty_till_date - $lastsale_qty_till_date;
				$inc_sale_per = ($inc_sale/$lastsale_qty_till_date)*100;
				Yii::log ( CVarDumper::dumpAsString ( $inc_sale_per ), CLogger::LEVEL_INFO, '$inc_sale_per' );
					
				if($inc_sale_per > 0){
					$get_inc = $max_qty *($inc_sale_per/100);
					$max_qty = $max_qty + $get_inc;
					$max_qty = round($max_qty);
					Yii::log ( CVarDumper::dumpAsString ( $get_inc ), CLogger::LEVEL_INFO, '$get_inc' );
					Yii::log ( CVarDumper::dumpAsString ( $max_qty ), CLogger::LEVEL_INFO, '$max_qty' );
	
				}
	
			}else{
				if($lastsale_qty_till_date > $sale_qty_till_date){
					$dec_sale = $lastsale_qty_till_date - $sale_qty_till_date;
					$dec_sale_per = ($dec_sale/$lastsale_qty_till_date)*100;
					Yii::log ( CVarDumper::dumpAsString ( $dec_sale_per ), CLogger::LEVEL_INFO, '$dec_sale_per' );
						
					if($dec_sale_per > 0){
						$get_inc = $max_qty *($dec_sale_per/100);
						$max_qty = $max_qty - $get_inc;
						$max_qty = round($max_qty);
						Yii::log ( CVarDumper::dumpAsString ( $get_inc ), CLogger::LEVEL_INFO, '$get_inc' );
						Yii::log ( CVarDumper::dumpAsString ( $max_qty ), CLogger::LEVEL_INFO, '$max_qty' );
	
					}
				}
			}
		}
	
		return $max_qty;
	}
	
	public function getReorderQty(){
		$max_qty = $this->reorder_qty;
	
		$criteria1 = new CDbCriteria();
		$criteria1->addCondition('item_id ='.$this->id);
		$criteria1->addCondition('status ='.MrsDetail::STATUS_DONE);
		$criteria1->order = 'id desc';
		$last_mrs_detail = MrsDetail::model()->find($criteria1);
		Yii::log ( CVarDumper::dumpAsString ( $last_mrs_detail ), CLogger::LEVEL_WARNING, '$last_mrs_detail' );
	
		if($last_mrs_detail){
			$mrs = Mrs::model()->findByPk($last_mrs_detail->mrs_id);
			$mrs_date = date('Y-m-d',strtotime($mrs->mrs_date));
			$curent_date = date('Y-m-d');
			$sale_qty_till_date = $this->getItemSaleQty($mrs_date,$curent_date);
			Yii::log ( CVarDumper::dumpAsString ( $sale_qty_till_date ), CLogger::LEVEL_WARNING, '$sale_qty_till_date' );
	
			$lastsale_qty_till_date = 0;
			$criteria2 = new CDbCriteria();
			$criteria2->addCondition('item_id ='.$this->id);
			$criteria2->addCondition('status ='.MrsDetail::STATUS_DONE);
			$criteria2->addCondition('mrs_id !='.$last_mrs_detail->mrs_id);
			$criteria2->order = 'id desc';
			$seclast_mrs_detail = MrsDetail::model()->find($criteria2);
			Yii::log ( CVarDumper::dumpAsString ( $seclast_mrs_detail ), CLogger::LEVEL_WARNING, '$$seclast_mrs_detail' );
				
			if($seclast_mrs_detail){
				$lastmrs = Mrs::model()->findByPk($seclast_mrs_detail->mrs_id);
				$lastmrs_date = date('Y-m-d',strtotime($lastmrs->mrs_date));
				$lastsale_qty_till_date = $this->getItemSaleQty($lastmrs_date,$mrs_date);
			}
			Yii::log ( CVarDumper::dumpAsString ( $lastsale_qty_till_date ), CLogger::LEVEL_WARNING, '$lastsale_qty_till_date' );
	
			if($sale_qty_till_date >$lastsale_qty_till_date && $lastsale_qty_till_date != 0){
				$inc_sale = $sale_qty_till_date - $lastsale_qty_till_date;
				$inc_sale_per = ($inc_sale/$lastsale_qty_till_date)*100;
				Yii::log ( CVarDumper::dumpAsString ( $inc_sale_per ), CLogger::LEVEL_WARNING, '$inc_sale_per' );
					
				if($inc_sale_per > 0){
					$get_inc = $max_qty *($inc_sale_per/100);
					$max_qty = $max_qty + $get_inc;
					$max_qty = round($max_qty);
					Yii::log ( CVarDumper::dumpAsString ( $get_inc ), CLogger::LEVEL_WARNING, '$get_inc' );
					Yii::log ( CVarDumper::dumpAsString ( $max_qty ), CLogger::LEVEL_WARNING, '$max_qty' );
	
				}
	
			}else{
				if($lastsale_qty_till_date > $sale_qty_till_date){
					$dec_sale = $lastsale_qty_till_date - $sale_qty_till_date;
					$dec_sale_per = ($dec_sale/$lastsale_qty_till_date)*100;
					Yii::log ( CVarDumper::dumpAsString ( $dec_sale_per ), CLogger::LEVEL_WARNING, '$dec_sale_per' );
						
					if($dec_sale_per > 0){
						$get_inc = $max_qty *($dec_sale_per/100);
						$max_qty = $max_qty - $get_inc;
						$max_qty = round($max_qty);
						Yii::log ( CVarDumper::dumpAsString ( $get_inc ), CLogger::LEVEL_WARNING, '$get_inc' );
						Yii::log ( CVarDumper::dumpAsString ( $max_qty ), CLogger::LEVEL_WARNING, '$max_qty' );
	
					}
				}
			}
		}
	if($max_qty == 0){
	$max_qty = $this->reorder_qty;
	}
		return $max_qty;
	}

	public function daysBetweenTwoDays($date1, $date2) {
		$date1 = strtotime($date1);
		$date2 = strtotime($date2);
		$datediff = $date1 - $date2;

		return round($datediff / (60 * 60 * 24));
	}
	
	public function getReorderQtyNew(){
		$safetyStock = $this->min_qty;
		$rop = 0;
	
		$criteria1 = new CDbCriteria();
		$criteria1->addCondition('item_id ='.$this->id);
		$criteria1->addCondition('status ='.MrsDetail::STATUS_DONE);
		$criteria1->order = 'id desc';
		$last_mrs_detail = MrsDetail::model()->find($criteria1);
		Yii::log ( CVarDumper::dumpAsString ( $last_mrs_detail ), CLogger::LEVEL_INFO, '$last_mrs_detail' );
	
		if($last_mrs_detail){
			$mrs = Mrs::model()->findByPk($last_mrs_detail->mrs_id);
			$mrs_date = date('Y-m-d',strtotime($mrs->mrs_date));
			$curent_date = date('Y-m-d');
			$sale_qty_till_date = $this->getItemSaleQty($mrs_date,$curent_date);
			Yii::log ( CVarDumper::dumpAsString ( $sale_qty_till_date ), CLogger::LEVEL_INFO, '$sale_qty_till_date' );

			$mrsBetweenDays = $this->daysBetweenTwoDays($curent_date, $mrs_date);
			Yii::log ( CVarDumper::dumpAsString ( $mrsBetweenDays ), CLogger::LEVEL_INFO, '$mrsBetweenDays' );

			$avgDailySales = round($sale_qty_till_date / $mrsBetweenDays, 2);
			Yii::log ( CVarDumper::dumpAsString ( $avgDailySales ), CLogger::LEVEL_INFO, '$avgDailySales' );

			$avgDeliveryTime = 0;

			$criteria = new CDbCriteria();
			$criteria->addCondition('item_id ='.$this->id);
			$criteria->addCondition('status ='.MrsDetail::STATUS_DONE);
			$criteria->order = 'id desc';
			$criteria->limit = 3;
			//$criteria->addCondition('item_id =' . $this->id);
			$mrs_details = MrsDetail::model()->findAll($criteria);
			$deliveryDays = 0;
			if (count($mrs_details) > 0) {
				$endDate = date('Y-m-d');
				foreach ($mrs_details as $key => $mrsDetail) {
					$mrs = Mrs::model()->findByPk($mrsDetail->mrs_id);
					$mrsCreateDate = date('Y-m-d',strtotime($mrs->mrs_date));
					$condition = new CDbCriteria();
					$condition->addCondition('type_id ='.StockLog::TYPE_ADDED);
					$condition->addCondition('item_id ='.$this->id);
					$condition->addBetweenCondition('DATE(create_time)', $mrsCreateDate, $endDate);
					$condition->order = 'id desc';
					$stockLog = StockLog::model()->find($condition);
					if ($stockLog) {
						$stockDate = date('Y-m-d',strtotime($stockLog->create_time));
						$stockBetweenDays = $this->daysBetweenTwoDays($mrsCreateDate, $stockDate);
						$deliveryDays += $stockBetweenDays;
					}
					$endDate = $mrsCreateDate;
				}
				Yii::log ( CVarDumper::dumpAsString ( $deliveryDays ), CLogger::LEVEL_INFO, '$deliveryDays' );
				Yii::log ( CVarDumper::dumpAsString ( count($mrs_details) ), CLogger::LEVEL_INFO, 'count($mrs_details)' );
				if ($deliveryDays > 0) {
					$avgDeliveryTime = round($deliveryDays / count($mrs_details));
				}
			}

			Yii::log ( CVarDumper::dumpAsString ( $avgDeliveryTime ), CLogger::LEVEL_INFO, '$avgDeliveryTime' );

			if ($avgDailySales > 0 && $avgDeliveryTime > 0) {
				$rop = ($avgDailySales * $avgDeliveryTime) + $safetyStock;
				Yii::log ( CVarDumper::dumpAsString ( $rop ), CLogger::LEVEL_INFO, '$rop' );
			}
		}
		if($rop > 0){
			return round($rop);
		}
		return $safetyStock;
	}
	
	
	
	 public function getAscBarCodeTotalRemainingQuantityIds($quantity_verify)
    {
		$item_detail_ids = [];
        $remaining_quantity = 0;
        $criteria = new CDbCriteria();
        $criteria->order = 'id asc';
        $criteria->addCondition('status =' . ItemDetail::STATUS_ACTIVE);
        //$criteria->addCondition('item_id =' . $this->id);
        $item_details = ItemDetail::model()->findAll($criteria);
		
		if(!empty($item_details))
		{
			
			foreach($item_details as $item_detail)
			{
				
			  if ($item_detail) {
            $remaining_quantity = '0.000';
            $add_quantity = '0.000';
            $sub_quantity = '0.000';
            $criteria = new CDbCriteria();
            $criteria->addCondition('item_detail_id =' . $item_detail->id);
            $criteria->order = 'id asc';
            $criteria->addCondition("balance_qty > 0.000");
            $criteria->addCondition('item_detail_id IS NOT NULL');
            $stocks = ItemStock::model()->findAll($criteria);

            if (! empty($stocks)) {
                foreach ($stocks as $stock) {
				
                    $add_quantity = ($add_quantity) + ($stock->balance_qty);
                }
            }
            $criteria1 = new CDbCriteria();
            $criteria1->addCondition('item_detail_id =' . $item_detail->id);
            $criteria1->order = 'id asc';
            $criteria1->addCondition("balance_qty < 0.000");
            $criteria1->addCondition('item_detail_id IS NOT NULL');
            $stocks = ItemStock::model()->findAll($criteria1);

            if (! empty($stocks)) {
                foreach ($stocks as $stock) {
                    $sub_quantity = ($sub_quantity) + abs($stock->balance_qty);
                }
            }
            $remaining_quantity = bcsub($add_quantity, $sub_quantity, 3);
			
			
			if($remaining_quantity == $quantity_verify)
			{
				
				$item_detail_ids [] = $item_detail->item_id;
				
			}
          
        }	
				
			}
		}
      

       return $item_detail_ids;
    }
	
	
}