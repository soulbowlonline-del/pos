<?php
/**
 * Ported from protected/views/rolePermission/index.php.
 */

use app\models\RolePermission;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	RolePermission::label(2),
	'Index',
];
?>

<div class="page-header">
<h1><?php echo Html::encode(RolePermission::label(2)); ?></h1>
</div>

<?php 


echo $this->render('_list', [
		'dataProvider'=>$dataProvider,
]);

