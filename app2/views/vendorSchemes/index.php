<?php
/**
 * Ported from protected/views/vendorSchemes/index.php.
 */

use app\models\VendorSchemes;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	VendorSchemes::label(2),
	'Index',
];
?>

<div class="page-header">
<h1><?php echo Html::encode(VendorSchemes::label(2)); ?></h1>
</div>

<?php 


echo $this->render('_list', [
		'dataProvider'=>$dataProvider,
]);

