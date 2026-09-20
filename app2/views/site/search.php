<?php
/**
 * Ported from protected/views/site/search.php.
 */

use app\models\User;
?>
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


$gMap = new EGMap();
$gMap->setWidth('860px');

$gMap->setHeight('350px');
$gMap->zoom = 11;
$mapTypeControlOptions = [
		'position'=> EGMapControlPosition::LEFT_BOTTOM,
		'style'=>EGMap::MAPTYPECONTROL_STYLE_DROPDOWN_MENU
];

$gMap->mapTypeControlOptions= $mapTypeControlOptions;

$rec = count( $models);
if( $rec > 0 )
{
	foreach ( $models as $model)
	{
		$lat =($model->latitude );
		$lon = ($model->longitude );
		$city = ($model->city );
		$query = Pointer::find();
		$query->andWhere('locator_id  ='.$model->id);
		$pointer = $query->one();
		$query_2 = User::find();
        $query_2->orderBy(['id' => SORT_DESC]);
		$query_2->select('email , id,full_name');
		$user = $query_2->one();
		if(!$pointer)
		{
			$icon = new EGMapMarkerImage(Yii::$app->request->baseUrl.'/images/phone.png');

		}
		else
			$icon = new EGMapMarkerImage(Yii::$app->request->baseUrl.'/images/index.png');
		if (strpos($q,'.') !== false){
			if($q==$pointer->createUser.'.'.$pointer->title)
			{
				$html = $this->render('_infowindow', ['user'=>$user,'model'=>$model ,'pointer'=>$pointer,'q'=>$q ],true);
				$marker =  new EGMapMarkerWithLabel($lat,$lon,['title' =>'locator :'.$model->id .' '.'click to show more information','icon'=>$icon]);
			}
			else{
				echo "No result found";
			}
		}
		else {
			$html = $this->render('_infowindow', ['user'=>$user,'model'=>$model ,'pointer'=>$pointer,'q'=>$q ],true);
			$marker =  new EGMapMarkerWithLabel($lat,$lon,['title' =>'locator :'.$model->id .' '.'click to show more information','icon'=>$icon]);

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
