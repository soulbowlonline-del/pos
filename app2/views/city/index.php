<?php
/**
 * Ported from protected/views/city/index.php.
 */

use app\models\City;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	City::label(2),
	'Index',
];
?>

<div class="page-header">
<h1><?php echo Html::encode(City::label(2)); ?></h1>
</div>

<?php 


echo $this->render('_list', [
		'dataProvider'=>$dataProvider,
]);

