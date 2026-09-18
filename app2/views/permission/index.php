<?php
/**
 * Ported from protected/views/permission/index.php.
 */

use app\models\Permission;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	Permission::label(2),
	'Index',
];
?>

<div class="page-header">
<h1><?php echo Html::encode(Permission::label(2)); ?></h1>
</div>

<?php 


echo $this->render('_list', [
		'dataProvider'=>$dataProvider,
]);

