<?php

return CMap::mergeArray(
	require(dirname(__FILE__).'/main.php'),
	array(
	'modules'=>array(
					'mygiix',
					'gii'=>array(
							'class'=>'system.gii.GiiModule',
							'password'=>'gii',
							// If removed, Gii defaults to localhost only. Edit carefully to taste.
							'ipFilters'=>array('127.0.0.1','::1'),
							'generatorPaths'=>array(   'ext-dev.giix-core' ),
					),
			), 
	'preload'=>array('log'),
	'components'=>array(
		'log'=>array(
			'class'=>'CLogRouter',
			'routes'=>array(
						 	array(
								'class' => 'CFileLogRoute',
								'levels' => 'error, warning',
								'maxFileSize'=>'102400',
								'maxLogFiles'=>'20'
							),
							array(
								'class' => 'CFileLogRoute',
								'levels' => 'info',
								'logPath' => dirname(__FILE__).'/../wdir/runtime/',
								'logFile' => 'info-'.date('Y-m-d').'.log',
								'maxFileSize'=>'1024*2', // 2MB
								'maxLogFiles'=>'10'
							),
/* 						   array(
								'class' => 'CWebLogRoute',
								'levels' => 'trace',
							), */
							array(
								'class' => 'CProfileLogRoute',
								'report' => 'summary',
								'levels' => 'profile',
							),
		
						),					
				),

		),
	)
);
