<?php


/**
 *
 * @property integer $id
 * @property integer $req_qty
 * @property integer $approved_qty
 * @property integer $bal_qty
 * @property integer $status
 * @property integer $type_id
 * @property string $remarks
 * @property string $create_time
 * @property string $update_time
 * @property integer $create_user_id
 * @property integer $updated_by
 * @property integer $item_detail_id
 * @property integer $mrn_id
 * @property integer $outlet_id
 */
Yii::import ( 'application.models._base.BaseMrnDetail' );
class MrnDetail extends BaseMrnDetail {
	public static function model($className = __CLASS__) {
		return parent::model ( $className );
	}
	public function getMrnVendorOptions(){
		$list = [];
		$criteria = new CDbCriteria();
		$criteria->addCondition('status ='.Mrn::STATUS_UNAPPROVED);
		$mrss = Mrn::model()->findAll($criteria);
		if($mrss){
			foreach($mrss as $mrs){
				$vendor = Vendor::model()->findByPk($mrs->vendor_id);
				if($vendor){
					$list[$vendor->id] = $vendor->name;
				}
			}
		}
		asort($list);
		return $list;
	}
	public function getVendorOptions() {
		$list = array ();
		$item_vendors = ItemVendor::model ()->findAllByAttributes ( array (
				'item_detail_id' => $this->item_id 
		) );
		if ($item_vendors) {
			foreach ( $item_vendors as $item_vendor ) {
				$vendor = Vendor::model ()->findByPk ( $item_vendor->vendor_id );
				if ($vendor) {
					$list [$vendor->id] = $vendor->name;
				}
			}
		}
		return $list;
	}
	public function getMrnOptions($id = null) {
		$list = array ();
		$user = Yii::app()->user->model;
		if ($user) {
			$role_id = $user->role_id;
			$role = UserRole::model()->findByAttributes(array('title'=>'Vendor'));

			if ($id != null) {
				if ($role_id == $role->id) {
					$criteria = new CDbCriteria();
					$criteria->addCondition('vendor_id ='.$id);
					$criteria->addCondition('status ='.Mrn::STATUS_UNAPPROVED);
					$mrslist = Mrn::model ()->findAll($criteria);
					
				} else {
					$criteria = new CDbCriteria();
					$criteria->addCondition('status ='.Mrn::STATUS_UNAPPROVED);
					$mrslist = Mrn::model ()->findAll ($criteria);
				}
				if ($mrslist) {
					foreach ( $mrslist as $mrs ) {
						$list [$mrs->id] = $mrs->id;
					}
				}
			}
		}
		return $list;
	}
	public function getAllMrnOptions($id = null) {
		$list = array ();
		$user = Yii::app()->user->model;
		if ($user) {
			$role_id = $user->role_id;
			$role = UserRole::model()->findByAttributes(array('title'=>'Vendor'));
			
			if ($id != null) {
				if ($role_id == $role->id) {
					$criteria = new CDbCriteria();
					$criteria->addCondition('vendor_id ='.$id);
					$criteria->addCondition('status ='.Mrn::STATUS_UNAPPROVED);
					$mrslist = Mrn::model ()->findAll($criteria);
				}else {
					$criteria = new CDbCriteria();
					$criteria->addCondition('status ='.Mrn::STATUS_UNAPPROVED);
					$mrslist = Mrn::model ()->findAll ($criteria);
				}
			if ($mrslist) {
				foreach ( $mrslist as $mrs ) {
					$list [] = $mrs->id;
				}
			}
			}
		}
		return $list;
	}
	public function getGstTrue($mrnid){
		$gst = true;
		if($mrnid){
			$mrs = Mrn::model()->findByAttributes(array('id'=>$mrnid));
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
		return $gst;
	}
	
	/* public function getCssClass($model)
	{
		$cssClass;
		Yii::log ( CVarDumper::dumpAsString ( $model ), CLogger::LEVEL_WARNING, '$model' );
		if($this->approved_qty > $this->req_qty)
		{
			$cssClass='rup';
		}
		elseif($this->approved_qty < $this->req_qty)
		{
			$cssClass='rdown';
		}
		else
		{
			$cssClass='requal';
		}
	
		return $cssClass;
	} */
	
	public function getPurchaseAmount(){
		$mrs = Mrn::model()->findByPk($this->mrn_id);
		$amount = '0';
		$criteria1 = new CDbCriteria();
		$criteria1->addCondition('t.item_id ='.$this->item_id);
		$criteria1->select = 'sum(t.amount) as amount';
		$orderitem = PurchaseBillDetail::model()->find($criteria1);
		if($orderitem){
			$amount = $orderitem->amount;
		}
		if($amount == ''){
			$amount = '0';
		}
		//Yii::log ( CVarDumper::dumpAsString ( $amount ), CLogger::LEVEL_WARNING, '$amount' );
		return $amount;
	}
	public function getSaleAmount(){
		$amount = '0';
		$criteria1 = new CDbCriteria();
		$criteria1->addCondition('t.item_id ='.$this->item_id);
		$criteria1->select = 'sum(t.total_amt) as total_amt';
		$orderitem = OrderItem::model()->find($criteria1);
		if($orderitem){
			$amount = $orderitem->total_amt;
		}
		if($amount == ''){
			$amount = '0';
		}
		//Yii::log ( CVarDumper::dumpAsString ( $amount ), CLogger::LEVEL_WARNING, '$saleamount' );
		return $amount;
	}
	public function getPurchaseQty(){
		$mrs = Mrn::model()->findByPk($this->mrn_id);
		$qty = '0';
		$criteria1 = new CDbCriteria();
		$criteria1->addCondition('t.item_id ='.$this->item_id);
		$criteria1->select = 'sum(t.approved_qty) as approved_qty';
		$orderitem = PurchaseBillDetail::model()->find($criteria1);
		if($orderitem){
			$qty = $orderitem->approved_qty;
		}
		if($qty == ''){
			$qty = '0';
		}
		Yii::log ( CVarDumper::dumpAsString ( $this->item_id ), CLogger::LEVEL_WARNING, '$this->item_id' );
		Yii::log ( CVarDumper::dumpAsString ( $qty ), CLogger::LEVEL_WARNING, '$purqty' );
		return $qty;
	}
	public function getSaleQty(){
		$qty = '0';
		$criteria1 = new CDbCriteria();
		$criteria1->addCondition('t.item_id ='.$this->item_id);
		$criteria1->select = 'sum(t.qty) as qty';
		$orderitem = OrderItem::model()->find($criteria1);
		if($orderitem){
			$qty = $orderitem->qty;
		}
		if($qty == ''){
			$qty = '0';
		}
		Yii::log ( CVarDumper::dumpAsString ( $this->item_id ), CLogger::LEVEL_WARNING, '$this->item_id' );
		Yii::log ( CVarDumper::dumpAsString ( $qty ), CLogger::LEVEL_WARNING, '$saleqty' );
		return $qty;
	}
	public function getCssClass()
	{
		$cssClass;
		$purchase_amount = $this->getPurchaseAmount();
		$sale_amount = $this->getSaleAmount();
		$purchase_qty = $this->getPurchaseQty();
		$per_purchase_qty = (($this->getPurchaseQty()) - (10/100) * ($this->getPurchaseQty()));
		$sale_qty = $this->getSaleQty();
		if($purchase_amount > $sale_amount){
			$cssClass='mrsred';
		}else if($sale_qty > $per_purchase_qty){
			$cssClass='mrsgreen';
		}else if($this->margin < 10){
			$cssClass='mrsorange';
		}else{
			$cssClass='';
		}
		Yii::log ( CVarDumper::dumpAsString ( $cssClass ), CLogger::LEVEL_WARNING, '$cssClass' );
		
		return $cssClass;
	}
}