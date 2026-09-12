<div class="page-header">
	<h1>
		<?php echo 'Search Results for '.$q; ?>
	</h1>
</div>
<style>
#EGMapContainer1 img {
	max-width: none;
}
</style>


<?php

Yii::import('ext.EGMap.*');

$gMap = new EGMap();
$gMap->setWidth('860px');

$gMap->setHeight('350px');
$gMap->zoom = 11;
$mapTypeControlOptions = array(
		'position'=> EGMapControlPosition::LEFT_BOTTOM,
		'style'=>EGMap::MAPTYPECONTROL_STYLE_DROPDOWN_MENU
);

$gMap->mapTypeControlOptions= $mapTypeControlOptions;

$rec = count( $models);
if( $rec > 0 )
{
	foreach ( $models as $model)
	{
		$lat =($model->latitude );
		$lon = ($model->longitude );
		$city = ($model->city );
		$criteria = new CDbCriteria ;
		$criteria->addCondition('locator_id  ='.$model->id);
		$pointer = Pointer::model()->resetScope()->find($criteria);
		$criteria=new CDbCriteria();
		$criteria->select = 'email , id,full_name';
		$user = User::model()->find($criteria);
		if(!$pointer)
		{
			$icon = new EGMapMarkerImage(Yii::app()->request->baseUrl.'/images/phone.png');

		}
		else
			$icon = new EGMapMarkerImage(Yii::app()->request->baseUrl.'/images/index.png');
		if (strpos($q,'.') !== false){
			if($q==$pointer->createUser.'.'.$pointer->title)
			{
				$html = $this->renderPartial('_infowindow', array('user'=>$user,'model'=>$model ,'pointer'=>$pointer,'q'=>$q ),true);
				$marker =  new EGMapMarkerWithLabel($lat,$lon,array('title' =>Yii::t('app','locator :').$model->id .' '.Yii::t('app','click to show more information'),'icon'=>$icon));
			}
			else{
				echo "No result found";
			}
		}
		else {
			$html = $this->renderPartial('_infowindow', array('user'=>$user,'model'=>$model ,'pointer'=>$pointer,'q'=>$q ),true);
			$marker =  new EGMapMarkerWithLabel($lat,$lon,array('title' =>Yii::t('app','locator :').$model->id .' '.Yii::t('app','click to show more information'),'icon'=>$icon));

		}

		$info_window_a = new EGMapInfoWindow($html);

		$marker->labelContent= '';
		$marker->labelClass='labels';
		$marker->setLabelAnchor(new EGMapPoint(22,0));

		$marker->addHtmlInfoWindow($info_window_a);

		$gMap->addMarker($marker);
	}

	$gMap->centerAndZoomOnMarkers();

	//$gMap->enableMarkerClusterer(new EGMapMarkerClusterer());
}

else{
echo "No result found";
$gMap->setCenter(10.843961080536246, 4.57168914843351 );
}

?>
<div>
	<?php $gMap->renderMap();  ?>
</div>
