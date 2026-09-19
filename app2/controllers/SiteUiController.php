<?php
namespace app\controllers;

use app\components\Ui;
use app\models\ContactForm;
use app\models\Site;
use app\models\User;
use Yii;
use yii\data\ActiveDataProvider;
use yii\helpers\Html;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Yii 2 port of protected/controllers/SiteController.php.
 *
 * Access matches Yii 1's accessRules(): signed in, nothing further. The
 * permission table decides what the views draw, not what the URL answers -
 * see docs/live-bugs-found.md.
 */
class SiteUiController extends BaseUiController
{
	/**
	 * Declares class-based actions.
	 */
	public function actions()
	{
		return [
				// captcha action renders the CAPTCHA image displayed on the contact page
				'captcha'=>[
						'class' => \yii\captcha\CaptchaAction::class,
						'backColor'=>0xFFFFFF,
				],
				// page action renders "static" pages stored under 'protected/views/site/pages'
				// They can be accessed via: index.php?r=site/page&view=FileName
				'page'=>[
						'class' => \yii\web\ViewAction::class,
				],
		];
	}

	/**
	 * This is the default 'index' action that is invoked
	 * when an action is not explicitly requested by users.
	 */
	
	public function actionIndex()
	{
		if(Yii::$app->user->isGuest)
		{
		return $this->redirect(['/user/login']);
		}
		else 
		{
			
				return $this->redirect(['/user/dashboard']);
			
		}

	}
/* 	public function actionIndexajax()
	{
		$this->layout = '//layouts/column1';
		return $this->renderPartial('index');
	} */
	public function actionSearch($q)
	{
		// renders the view file 'protected/views/site/index.php'
		// using the default layout 'protected/views/layouts/main.php'

		if (strpos($q,'.') !== false) {
			$list = explode(".",$q);

			$criteria = new CDbCriteria();
			// $user = User::findOne(array('full_name'=>$list[0]));
			$criteria->addCondition('title = \''.$list[1].'\'');
			$pointers=Pointer::model()->resetScope()->findAll($criteria);
			if($pointers)
			{
				$ids = [];
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
		return $this->render('search',['models'=>$models,'q'=>$q]);
	}

	/**
	 * This is the action to handle external exceptions.
	 */
	public function actionError()
	{
		if($error=Ui::errorArray())
		{
			if(Yii::$app->request->isAjax)
				echo $error['message'];
			else
				return $this->render('error', $error);
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

				mail(Yii::$app->params['adminEmail'],$subject,$model->body,$headers);
				Yii::$app->user->setFlash('contact','Thank you for contacting us. We will respond to you as soon as possible.');
				$this->refresh();
			}
		}
		return $this->render('contact',array('model'=>$model));
	}*/
	
	public function actionAbout()
	{
return $this->render('about');
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
				mail(Yii::$app->params['adminEmail'],$model->subject,$model->body,$headers);
				Yii::$app->user->setFlash('contact','Thank you for contacting us. We will respond to you as soon as possible.');
				$this->refresh();
			}
		}
		return $this->render('contact',['model'=>$model]);
	}
	

	/**
	 * Displays the login page
	 */
	}