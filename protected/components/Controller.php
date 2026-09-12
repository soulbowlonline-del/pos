<?php
/**
 * Controller is the customized base controller class.
 * All controller classes for this application should extend from this base class.
 */
class Controller extends CController
{
	/**
	 * @var string the default layout for the controller view. Defaults to '//layouts/column1',
	 * meaning using a single column layout. See 'protected/views/layouts/column1.php'.
	 */
	public $layout='//layouts/column2';
	/**
	 * @var array context menu items. This property will be assigned to {@link CMenu::items}.
	 */
	public $menu=array();
	/**
	 * @var array the breadcrumbs of the current page. The value of this property will
	 * be assigned to {@link CBreadcrumbs::links}. Please refer to {@link CBreadcrumbs::links}
	 * for more details on how to specify this property.
	*/
	public $breadcrumbs=array();

	public $actions = array();
	public $menu_top = array();
	public $menu_left = array();


	public function sendJSONResponse( $arr)
	{
		header('Content-type: application/json');
		echo json_encode($arr);
		Yii::app()->end();
	}

	private $_pageCaption = 'Ride4Ride';
	private $_pageDescription = " Order a taxi in  short time";

	private $_pageKeywords = "Get a Taxi,Taxi,travel,pick a taxi,Get a Ride";

	public function getPageCaption() {
		if($this->_pageCaption!==null)
			return $this->_pageCaption;
		else
		{
			$name=ucfirst(basename($this->getId()));
			if($this->getAction()!==null && strcasecmp($this->getAction()->getId(),$this->defaultAction))
				return $this->_pageCaption=$name.''.ucfirst($this->getAction()->getId());
			else
				return $this->_pageCaption=$name;
		}
	}

	public function setPageCaption($value) {
		$this->_pageCaption = $value;
	}
	public function behaviors() {
		return array(
				'exportableGrid' => array(
						'class' => 'application.components.ExportableGridBehavior',
						'filename' => 'export.csv',
						'csvDelimiter' => ',', //i.e. Excel friendly csv delimiter
				));
	}
	/**
	 * @return string the page description (or subtitle). Defaults to the page title + 'page' suffix.
	 */
	public function getPageDescription() {
		if($this->_pageDescription!==null)
			return $this->_pageDescription;
		else
		{
			return Yii::app()->name . ' ' . $this->getPageCaption() ;
		}
	}
	/**
	 * @param string $value the page description (or subtitle)
	 */
	public function setPageKeywords($value) {
		if ( !empty($value) ) $this->_pageKeywords = $value . ', ' . $this->_pageKeywords;
	}
	public function getPageKeywords() {
		if($this->_pageKeywords!==null)
		{
			$list = explode ( ',', $this->_pageKeywords);
			array_map('trim', $list);
			array_unique( $list);
			$this->_pageKeywords = implode ( ',', $list );
			return $this->_pageKeywords;
		}
		else
		{
			return Yii::app()->name . ', ' . $this->getPageCaption();
		}
	}


	protected function processSEO($model)
	{

			
		if ( $model && !$model->isNewRecord)
		{
			if ( $model->hasAttribute('id')) $this->pageCaption 		= GxHtml::encode($model->label()) . '' .GxHtml::encode(GxHtml::valueEx($model));
			$this->pageTitle 		= $this->pageCaption;
			if ( $model->hasAttribute('content')) $this->pageDescription 	= substr(strip_tags($model->content), 0,150);
			//if ( $model->hasAttribute('title')) $this->pageDescription 	= substr(strip_tags($model->title), 0,150);
				
		}
		else
		{
			$this->pageCaption 		= GxHtml::encode($model->label() . '' . $this->action->id);
			$this->pageTitle 		= $this->pageCaption;
			//$this->pageDescription 	= $this->_pageDescription;
			$this->pageKeywords 	= $this->_pageKeywords;
		}
	}
	public function init()
	{
		parent::init();
	}

	public function renderNavBar()
	{
		$this->menu_top = array(


				array(
						'class'=>'bootstrap.widgets.TbMenu',
						'htmlOptions'=>array('class'=>'pull-right'),
						'items'=>array(
								array('label'=>'My Account ('.Yii::app()->user->name.')', 'url'=>array('/user/view','id'=>Yii::app()->user->id),'icon'=>'icon-asterisk icon-white','visible'=>!Yii::app()->user->isGuest,
								),
								array('label'=>'Journey', 'url'=>array('/journey/admin'),'visible'=>!Yii::app()->user->isGuest,
								),
								array('label'=>'Drivers', 'url'=>array('/driver/index'),'visible'=>!Yii::app()->user->isGuest,
								),
									array('label'=>'Login', 'url'=>array('/user/login'),'icon'=>'icon-asterisk icon-white','visible'=>Yii::app()->user->isGuest,
								),
										array('label'=>'Register', 'url'=>array('/user/create'),'icon'=>'icon-asterisk icon-white','visible'=>Yii::app()->user->isGuest,
								),
											array('label'=>'Logout', 'url'=>array('/user/logout'),'icon'=>'icon-asterisk icon-white','visible'=>!Yii::app()->user->isGuest,
								),
						),
				),
		);

		$this->widget('bootstrap.widgets.TbNavbar', array(
				'type'=>'inverse', // null or 'inverse'
				'brand'=>'Ride4Ride',
				'brandUrl'=>Yii::app()->createAbsoluteUrl('user/index'),
				'collapse'=>true, // requires bootstrap-responsive.css
				'items'=>$this->menu_top,
				'fixed'=> false,

		));
	}



	public function beforeAction($event)
	{
		
		if(!Yii::app()->user->isGuest)
		{
				$this->layout= 'admin_layout';
			
		}
		AuthSession::authenticateSession();
		return parent::beforeAction($event);
	}

}