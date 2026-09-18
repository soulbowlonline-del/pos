<?php
/**
 * Ported from protected/views/country/index.php.
 */

use app\models\Country;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	Country::label(2),
	'Index',
];
?>

<div class="page-header">
<h1><?php echo Html::encode(Country::label(2)); ?></h1>
</div>

<?php 


echo $this->render('_list', [
		'dataProvider'=>$dataProvider,
]);

