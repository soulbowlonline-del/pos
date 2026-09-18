<?php
/**
 * Ported from protected/views/vendor/index.php.
 */

use app\models\Vendor;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	Vendor::label(2),
	'Index',
];
?>

<div class="page-header">
<h1><?php echo Html::encode(Vendor::label(2)); ?></h1>
</div>

<?php 


echo $this->render('_list', [
		'dataProvider'=>$dataProvider,
]);

