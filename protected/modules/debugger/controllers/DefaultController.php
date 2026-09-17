<?php

class DefaultController extends CController
{



	/**
	 * @return array action filters
	 */
	public function filters()
	{
		return array(
				'accessControl', // perform access control for CRUD operations
		);
	}

	/**
	 * Specifies the access control rules.
	 * This method is used by the 'accessControl' filter.
	 * @return array access control rules
	 */
	public function accessRules()
	{
		return array(
				array('allow',  // Authenticated users only: 'showlogs' exposes application logs (stack
				// traces, query errors) and 'deleteAssets' is destructive. These were
				// previously open to '*' (anonymous).
						'actions'=>array('index','showlogs','deleteAssets'),
						'users'=>array('@'),
				),
				array('allow', // allow authenticated user to perform 'create' and 'update' actions
						'actions'=>array(),
						'users'=>array('@'),
				),
				array('allow', // allow admin user to perform 'admin' and 'delete' actions
						'actions'=>array(),
						'users'=>array('admin'),
				),
				array('deny',  // deny all users
						'users'=>array('*'),
				),
				
		);
	}
	
	
	public function actionIndex() {
	    $this->render('index');
	}
	public function actionShowLogs(){
		$this->layout = 'admin_layout';
	    $url = Yii::app()->runtimePath.'/application.log';
	
	    if(file_exists($url)){
	       
	        $myfile = fopen($url, 'r');
	        while(!feof($myfile)) {
	            echo nl2br(fgets($myfile));
	        }
	        fclose($myfile);
	       
	    }else{
	        echo "<span style='color:green;'>No Recent Logs</span>";
	    }
	    Yii::app()->end();
	}
	public function actionDeleteAssets()
	{
	    $path = Yii::app()->getAssetManager()->basePath;
	    self::rrmdir($path);
	    $runtime = Yii::app()->runtimePath;
	    self::rrmdir($runtime);	    
	    echo "<span style='color:green;'>Deleted ..</span>";
	    Yii::app()->end();
	}
	
	public static function rrmdir($dir, $delete = false)
	{
		if (is_dir($dir)) {
			$objects = scandir($dir);
			foreach ($objects as $object) {
				if ($object != "." && $object != "..") {
					if (filetype($dir . "/" . $object) == "dir") {
						self::rrmdir($dir . "/" . $object, true);
					} else
						if ($object != 'assets') {
	
							if (unlink($dir . "/" . $object))
								echo '<p style="color:red">Removed File : ' . $dir . "/" . $object . '<br /></p>';
						}
				}
			}
			reset($objects);
	
			if ($delete) {
				if (rmdir($dir))
					echo '<p style="color:grey">Removed Directory :' . $dir . '<br /></p>';
			}
		}
	}
	
}