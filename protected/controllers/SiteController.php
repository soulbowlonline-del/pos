<?php

class SiteController extends Controller
{
	/**
	 * Declares class-based actions.
	 */
	public function actions()
	{
		return array(
				// captcha action renders the CAPTCHA image displayed on the contact page
				'captcha'=>array(
						'class'=>'CCaptchaAction',
						'backColor'=>0xFFFFFF,
				),
				// page action renders "static" pages stored under 'protected/views/site/pages'
				// They can be accessed via: index.php?r=site/page&view=FileName
				'page'=>array(
						'class'=>'CViewAction',
				),
		);
	}

	/**
	 * This is the default 'index' action that is invoked
	 * when an action is not explicitly requested by users.
	 */
	
	public function actionIndex()
	{
		if(Yii::app()->user->isGuest)
		{
		$this->redirect(array('/user/login'));
		}
		else 
		{
			
				$this->redirect(array('/user/dashboard'));
			
		}

	}
/* 	public function actionIndexajax()
	{
		$this->layout = '//layouts/column1';
		$this->renderPartial('index');
	} */
	public function actionSearch($q)
	{
		// renders the view file 'protected/views/site/index.php'
		// using the default layout 'protected/views/layouts/main.php'

		if (strpos($q,'.') !== false) {
			$list = explode(".",$q);

			$criteria = new CDbCriteria();
			// $user = User::model()->findByAttributes(array('full_name'=>$list[0]));
			$criteria->addCondition('title = \''.$list[1].'\'');
			$pointers=Pointer::model()->resetScope()->findAll($criteria);
			if($pointers)
			{
				$ids = array();
				foreach($pointers as $pointer)
				{
					$ids[] = $pointer->locator_id;

				}
				$criteria = new CDbCriteria();
				$criteria->addInCondition('id ',$ids);
			}
		}
		else
		{
			$criteria = new CDbCriteria();
			$criteria->compare('address',$q, true) ;
				}
		$models=Locator::model()->findAll($criteria);
		//$dataProvider = new CActiveDataProvider('Locator',array('criteria'=>$criteria));
		$this->render('search',array('models'=>$models,'q'=>$q));
	}

	/**
	 * This is the action to handle external exceptions.
	 */
	public function actionError()
	{
		if($error=Yii::app()->errorHandler->error)
		{
			if(Yii::app()->request->isAjaxRequest)
				echo $error['message'];
			else
				$this->render('error', $error);
		}
	}

	/**
	 * Displays the contact page
	 */
/*	public function actionContact()
	{
		$model=new ContactForm;
		if(isset($_POST['ContactForm']))
		{
			$model->attributes=$_POST['ContactForm'];
			if($model->validate())
			{
				$name='=?UTF-8?B?'.base64_encode($model->name).'?=';
				$subject='=?UTF-8?B?'.base64_encode($model->subject).'?=';
				$headers="From: $name <{$model->email}>\r\n".
						"Reply-To: {$model->email}\r\n".
						"MIME-Version: 1.0\r\n".
						"Content-type: text/plain; charset=UTF-8";

				mail(Yii::app()->params['adminEmail'],$subject,$model->body,$headers);
				Yii::app()->user->setFlash('contact','Thank you for contacting us. We will respond to you as soon as possible.');
				$this->refresh();
			}
		}
		$this->render('contact',array('model'=>$model));
	}*/
	
	public function actionAbout()
	{
$this->render('about');
	}
	
public function actionContact()
	{
		$model=new ContactForm;
		if(isset($_POST['ContactForm']))
		{
			$model->attributes=$_POST['ContactForm'];
			if($model->validate())
			{
				$headers="From: {$model->email}\r\nReply-To: {$model->email}";
				mail(Yii::app()->params['adminEmail'],$model->subject,$model->body,$headers);
				Yii::app()->user->setFlash('contact','Thank you for contacting us. We will respond to you as soon as possible.');
				$this->refresh();
			}
		}
		$this->render('contact',array('model'=>$model));
	}
	

	/**
	 * Displays the login page
	 */
	}