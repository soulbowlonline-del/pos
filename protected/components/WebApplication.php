<?php
class WebApplication extends CWebApplication
{
	
public function runController($route)
	{
		if(($ca=$this->createController($route))!==null)
		{
			list($controller,$actionID)=$ca;
			$oldController=$this->_controller;
			$this->_controller=$controller;
			$controller->init();
			$controller->run($actionID);
			$this->_controller=$oldController;
		}
		else
			throw new CHttpException(404,Yii::t('yii','dfgdfgdfgt "{route}".',
				array('{route}'=>$route===''?$this->defaultController:$route)));
	}
}