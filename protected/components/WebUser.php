<?php
class WebUser extends CWebUser
{
	private $_model = null;

	public function getModel() {

		if($this->isGuest) return null;

		if($this->_model instanceof User)
			return $this->_model;

		$this->_model = User::model()->findByPk($this->id);
		return $this->_model;
	}

	public function checkAccess($operation, $params=array(), $allowCaching=true)
	{
		return parent::checkAccess($operation, $params, $allowCaching);
	}
	/**
	 * Return loggedin name.
	 * @return boolean
	 */
	public function loggedInAs() {
		if($this->isGuest)
			return Yii::t('Guest');
		else
			return $this->getModel()->full_name;
	}
	/**
	 * Return admin status.
	 * @return boolean
	 */
	/* public function getIsAdmin() {
		if($this->isGuest)
			return false;
		else
			return $this->getModel()->getIsAdmin();
	}
	public function getIsMerchant() {
		if($this->isGuest)
			return false;
			else
				return $this->getModel()->getIsMerchant();
	}
	
	public function getIsUser() {
		if($this->isGuest)
			return false;
		else
			return $this->getModel()->getIsUser();
	} */

	/**
	 * Session key under which login state is published for the Yii 2 application.
	 *
	 * Yii 1 keys its own session state with md5('Yii.'.get_class($this).'.'.appId),
	 * which is an internal detail and awkward to reproduce from outside the
	 * framework. Rather than have Yii 2 recompute that, Yii 1 mirrors the few
	 * facts Yii 2 needs under a fixed, framework-neutral key. Both applications
	 * run in the same container and therefore share PHP's session storage.
	 */
	const POS_BRIDGE_KEY = 'pos_bridge_auth';

	public function init()
	{
		parent::init();
		// Runs on every request, so sessions established before the bridge existed
		// are picked up too rather than only those created by a fresh login.
		$this->syncBridgeState();
	}

	/**
	 * Mirrors (or clears) the current login state for the Yii 2 side.
	 */
	public function syncBridgeState()
	{
		if (!isset($_SESSION)) {
			return;
		}
		if ($this->getIsGuest()) {
			unset($_SESSION[self::POS_BRIDGE_KEY]);
			return;
		}
		$_SESSION[self::POS_BRIDGE_KEY] = array(
			'id'   => $this->getId(),
			'name' => $this->getName(),
			'ts'   => time(),
		);
	}

	public function afterLogin($fromCookie)
	{
		if(Yii::app()->user->model)Yii::app()->user->model->updateLastVisit();
		$this->syncBridgeState();
	}
	public function beforeLogout()
	{
		AuthSession::logoutSession();
		unset($_SESSION[self::POS_BRIDGE_KEY]);
		return parent::beforeLogout();
	}
}
?>
