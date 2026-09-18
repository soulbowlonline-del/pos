<?php
/**
 * Ported from protected/views/userRole/index.php.
 */

use app\models\UserRole;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	UserRole::label(2),
	'Index',
];
?>

<div class="page-header">
<h1><?php echo Html::encode(UserRole::label(2)); ?></h1>
</div>

<?php 


echo $this->render('_list', [
		'dataProvider'=>$dataProvider,
]);

