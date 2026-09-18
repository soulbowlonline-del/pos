<?php
/**
 * Ported from protected/views/outlet/index.php.
 */

use app\models\Outlet;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	Outlet::label(2),
	'Index',
];
?>

<div class="page-header">
<h1><?php echo Html::encode(Outlet::label(2)); ?></h1>
</div>

<?php 


echo $this->render('_list', [
		'dataProvider'=>$dataProvider,
]);

