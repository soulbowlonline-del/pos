<?php

// uncomment the following to define a path alias
Yii::setPathOfAlias('ext-dev',dirname(__FILE__).'/../ext-dev'); 
Yii::setPathOfAlias('bootstrap',dirname(__FILE__).'/../ext-prod/bootstrap');
Yii::setPathOfAlias('ext-prod',dirname(__FILE__).'/../ext-prod');
// This is the main Web application configuration. Any writable
// CWebApplication properties can be configured here.
return array(
		'basePath'=>dirname(__FILE__).'/../protected',
		'runtimePath'=>dirname(__FILE__).'/../wdir/runtime',
		'name'=>'DASPOS',
		'theme'=>'bar',
		'defaultController'=> 'site',

		'preload'=>array('session','urlManager','user','bootstrap'),

		// autoloading model and component classes
		'import'=>array(
				'application.models.*',
				'application.components.*',
				'application.controllers.*',
				'ext.phpmailer.JPhpMailer',
		),

		'modules'=>array(
				'debugger','api',
		'gii' => array(
						'class' => 'system.gii.GiiModule',
						'password' => 'gii',
						// If removed, Gii defaults to localhost only. Edit carefully to taste.
						'ipFilters' => array($_SERVER['REMOTE_ADDR']),
						'generatorPaths' => array(
								'bootstrap.gii',
						),
				), 
				'backup'=> array('path' => dirname(__FILE__).'/../wdir/_backup/'),
				
		),
		// application components
		'components'=>array(
				'interaktApi' => array(
            'class' => 'application.components.InteraktApi',
            'apiKey' => (getenv('POS_INTERAKT_API_KEY') !== false) ? getenv('POS_INTERAKT_API_KEY') : '',
        ),
				'Smtpmail'=>array(
						'class'=>'application.extensions.smtpmail.PHPMailer',
						'Host'=>"smtp.gmail.com",
						'Username'=>'marketing@soulbowl.in',
						'Password'=>(getenv('POS_SMTP_PASSWORD') !== false) ? getenv('POS_SMTP_PASSWORD') : '',
						'Mailer'=>'smtp',
						'Port'=>587,
						'SMTPAuth'=>true,
						'SMTPSecure'=>'tls'
				),
			'geoIP'=>array(
					'class'=>'ext.EGeoIP',
			),
		'bootstrap' => array(
			'class' => 'ext-prod.bootstrap.components.Bootstrap',
		//'responsiveCss' => true,
		),
							'ePdf' => array(
						'class'         => 'ext.yii-pdf.EYiiPdf',
						'params'        => array(
								'mpdf'     => array(
										'librarySourcePath' => 'ext-prod.mpdf.*',
										'constants'         => array(
												'_MPDF_TEMP_PATH' => Yii::getPathOfAlias('application.runtime'),
										),
										'class'=>'mpdf', // the literal class filename to be loaded from the vendors folder
										/*'defaultParams'     => array( // More info: http://mpdf1.com/manual/index.php?tid=184
										 'mode'              => '', //  This parameter specifies the mode of the new document.
												'format'            => 'A4', // format A4, A5, ...
												'default_font_size' => 0, // Sets the default document font size in points (pt)
												'default_font'      => '', // Sets the default font-family for the new document.
												'mgl'               => 15, // margin_left. Sets the page margins for the new document.
												'mgr'               => 15, // margin_right
												'mgt'               => 16, // margin_top
												'mgb'               => 16, // margin_bottom
												'mgh'               => 9, // margin_header
												'mgf'               => 9, // margin_footer
												'orientation'       => 'P', // landscape or portrait orientation
										)*/
								),
								'HTML2PDF' => array(
										'librarySourcePath' => 'application.vendors.html2pdf.*',
										'classFile'         => 'html2pdf.class.php', // For adding to Yii::$classMap
										/*'defaultParams'     => array( // More info: http://wiki.spipu.net/doku.php?id=html2pdf:en:v4:accueil
										 'orientation' => 'P', // landscape or portrait orientation
												'format'      => 'A4', // format A4, A5, ...
												'language'    => 'en', // language: fr, en, it ...
												'unicode'     => true, // TRUE means clustering the input text IS unicode (default = true)
												'encoding'    => 'UTF-8', // charset encoding; Default is UTF-8
												'marges'      => array(5, 5, 5, 8), // margins by default, in order (left, top, right, bottom)
										)*/
								)
						),
				),


		// Enables DB schema caching (see schemaCachingDuration in the db config) so
		// Yii stops re-reading table metadata for all ~77 tables on every request.
		// File cache lives in tmpfs (/dev/shm), not the slow macOS bind mount.
		'cache'=> array(
			'class'=>'system.caching.CFileCache',
			'cachePath'=>'/dev/shm/yii_cache',
		),
		'user'=>array(
			'class'=>'application.components.WebUser',
			'allowAutoLogin'=>true,
			'loginUrl' => array('user/login'),
		),

		// uncomment the following to enable URLs in path-format
	'urlManager'=>array(
			'urlFormat'=>'path',
			'showScriptName'=>false,
			'rules'=>array(
			'home'=>'site',
			'<controller:\w+>/<id:\d+>'=>'<controller>/view',
			'<controller:\w+>/<action:\w+>/<id:\d+>'=>'<controller>/<action>',
			'<controller:\w+>/<action:\w+>/<id:\d+>'=>'<controller>/<action>',
			'<controller:\w+>/<action:\w+>'=>'<controller>/<action>',
	
			),
		),
			
			
		'db'=> require(DB_CONFIG_FILE_PATH),

		'errorHandler'=>array(
			'errorAction'=>'site/error',
			),
		),

		// application-level parameters that can be accessed
		// using Yii::app()->params['paramName']
		'params'=>require(dirname(__FILE__).'/params.php'),

);

