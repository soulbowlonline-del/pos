<?php

/**
 * GridView Export Behavior
* Requirements:
*    Yii 1.1.3 or greater
*    An explicit rule on the action that exports the csv like: '<controller:\w+>/<action:\w+>' => '<controller>/<action>'
* Usage:
* <pre>
* //(Step 1) Add this behavior to the controller like
public function behaviors() {
return array(
		'exportableGrid' => array(
				'class' => 'application.components.ExportableGridBehavior',
		));
}
* //(Step 2) On actionAdmin() , add this line before render('admin') method
if ($this->isExportRequest()) {
$this->exportCSV(array('EXPORT DATE ',date('Y-m-d')), null, false);
$this->exportCSV($model, array_keys($model->attributeLabels()), false, 3);
$this->exportCSV($model->search(), array('id', 'comment', 'date', 'user.name', 'user.id'));
}
* //(Step 3) On the view that renders the grid, add the Export button like this
$this->renderExportGridButton($gridWidget,'Export Grid Results',array('class'=>'btn btn-info pull-right'));
* // (Optional) If you need to export a very large dataset, you need to add this before exportCSV()
set_time_limit(0);
* </pre>
* @version 2.0
* @author Geronimo Oñativia / http://www.estudiokroma.com
* @link http://www.yiiframework.com/extension/exportablegridbehavior
*/
class ExportableGridBehavior extends CBehavior {

	public $buttonId = 'export-button';		//Id used on the export link
	public $exportParam = 'exportCSV';		//Param to be appended on url to fire an export
	public $csvDelimiter = ',';				//Columns delimiter
	public $csvEnclosure = '"';				//Enclosure used on data when needed
	public $filename = 'export.csv';		//Filename suggested to user when downloading
	public $addUTF8BOM = true; 				//Adds \xEF\xBB\xBF to output
	private $headersSent = false;


	/**
	 * @param mixed $data A Traversable of CModel or a CModel where data will be fetch from
	 * @param array $attributes Attribute names of CModel to be exported.
	 * @param bool $endApplication Application will be ended if true. false to keep going and export more data. Defautls to TRUE.
	 * @param integer $endLineCount Number of newlines to append below this data. Defaults to 0.
	 */
	public function exportCSV($data, $attributes = array(), $endApplication = true, $endLineCount = 0) {
		if ($this->isExportRequest()) {

			$this->sendHeaders();
			$fileHandle = fopen('php://output', 'w');
			if ($data instanceof CActiveDataProvider) {
				$this->csvRowHeaders($fileHandle, $attributes, $data->model);
				$this->csvRowModels($fileHandle, new CDataProviderIterator($data, 150), $attributes);
			} else if (is_array($data) && current($data) instanceof CModel) {
				$this->csvRowHeaders($fileHandle, $attributes, current($data));
				$this->csvRowModels($fileHandle, $data, $attributes);
			} else if (is_array($data) && is_string(current($data))) {
				fputcsv($fileHandle, $data, $this->csvDelimiter, $this->csvEnclosure);
			} else if ($data instanceof CModel) {
				$this->csvModel($fileHandle, $data, $attributes);
			}
			fprintf($fileHandle, str_repeat("\n", $endLineCount));
			fclose($fileHandle);

			if ($endApplication) {
				Yii::app()->end(0, false);
				exit(0);
			}
		}
	}

	private function csvRowHeaders($fileHandle, $attributes, CModel $model)
	{
		$row = array();
		foreach ($attributes as $attr) {
			if (is_array($attr)) {
				if (isset($attr['label'])) {
					$row[] = $attr['label'];
				} elseif (isset($attr['name'])) {
					$row[] = $model->getAttributeLabel($attr['name']);
				} else {
					$row[] = '';
				}
			} else {
				$row[] = $model->getAttributeLabel($attr);
			}
		}
		fputcsv($fileHandle, $row, $this->csvDelimiter, $this->csvEnclosure);
	}
	
	private function csvRowModels($fileHandle, Traversable $cModels, $attributes)
	{
		foreach ($cModels as $cModel) {
			$row = array();
			foreach ($attributes as $attr) {
				if (is_array($attr)) {
					if (isset($attr['name'])) {
						$row[] = CHtml::value($cModel, $attr['name']);
					} elseif (isset($attr['value'])) {
						$row[] = is_object($attr['value']) && get_class($attr['value']) === 'Closure' ? call_user_func($attr['value'], $cModel) : $attr['value'];
					} else {
						$row[] = '';
					}
				} else {
					$row[] = CHtml::value($cModel, $attr);
				}
			}
			fputcsv($fileHandle, $row, $this->csvDelimiter, $this->csvEnclosure);
		}
	}

	public static function value($model,$attribute,$defaultValue=null)
	{

		
		if(is_scalar($attribute) || $attribute===null)
			foreach(explode('.',$attribute) as $name)
			{
				
				
				
					if(is_object($model) && isset($model->$name))
					{
						if($name == 'password')
						{
							$model = '';
						}
						
						
						else
						$model = $model->$name;
					}
						elseif(is_array($model) && isset($model[$name]))
						{
							Yii::log(CVarDumper::dumpAsString('hddddjkhjk') ,CLogger::LEVEL_WARNING,'attribdddute');
						$model=$model[$name];
						}
						else
						{
							Yii::log(CVarDumper::dumpAsString('hddddjkhk.kjjk') ,CLogger::LEVEL_WARNING,'attribkj.kjdddute');
							return $defaultValue;
						}
				
			}
		else
		{
			
			return call_user_func($attribute,$model);
		}
	
			return $model;
	}
/* 	public static function value($model,$attribute,$defaultValue=null)
	{
		 
		$rbank = property_exists($model, 'rbank');
		$rusername = property_exists($model, 'rusername');
		$rbankaccount = property_exists($model, 'rbankaccount');
		$rifsc = property_exists($model, 'rifsc');
	
		if(is_scalar($attribute) || $attribute===null)
			foreach(explode('.',$attribute) as $name)
			{
				if(get_class($model) == 'UserPointsMapping')
				{
					if(is_object($model) && isset($model->$name))
					{

						$model= UserPointsMapping::getValue($model,$name);
							
					}
					elseif(is_array($model) && isset($model[$name]))
					{
						$model = UserPointsMapping::getValue($model,$name);

					}
				
					else
					{
						
						$model = UserPointsMapping::getValue($model,'rusername');
					
					}
				}else {
					if(is_object($model) && isset($model->$name))
						$model = $model->$name;
						elseif(is_array($model) && isset($model[$name]))
						$model=$model[$name];
						else
							return $defaultValue;
				}
			}
		else
			return call_user_func($attribute,$model);

			return $model;
	} */


	private function csvModel($fileHandle, CModel $cModel, $attributes) {
		foreach ($attributes as $attr) {
			$row = array();
			$row[] = $cModel->getAttributeLabel($attr);
			$row[] = CHtml::value($cModel, $attr);
			fputcsv($fileHandle, $row, $this->csvDelimiter, $this->csvEnclosure);
		}
	}

	private function sendHeaders() {
		if ($this->headersSent === false) {
			$this->headersSent = true;
			// disable caching
			$now = gmdate("D, d M Y H:i:s");
			header("Expires: Tue, 01 Jan 2013 00:00:00 GMT");
			header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
			header("Last-Modified: {$now} GMT");
			// force download
			header("Content-Type: application/force-download");
			header("Content-Type: application/octet-stream");
			header("Content-Type: application/download");
			// disposition / encoding on response body
			header("Content-Disposition: attachment;filename=\"{$this->filename}\"");
			header("Content-Transfer-Encoding: binary");
			if ($this->addUTF8BOM)
				print "\xEF\xBB\xBF";
		}
	}

	public function isExportRequest() {
		return Yii::app()->request->getParam($this->exportParam, false);
	}

	public function renderExportGridButton(CGridView $grid, $label = 'Export', $htmlOptions = array()) {
		if (!isset($htmlOptions['id'])) {
			$htmlOptions['id'] = $this->buttonId;
		}
		echo CHtml::link($label, '#', $htmlOptions);
		Yii::app()->clientScript->registerScript('exportgrid'.$htmlOptions['id'], "$('#" . $htmlOptions['id'] . "').on('click',function() {
    var downloadUrl=$('#" . $grid->id . "').yiiGridView('getUrl');
				downloadUrl+=((downloadUrl.indexOf('?')==-1)?'?':'&');
				downloadUrl+='{$this->exportParam}=1';
				window.open( downloadUrl ,'_blank');
	});");
	}

}

?>
