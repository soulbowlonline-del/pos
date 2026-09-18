<?php
/**
 * Ported from protected/views/notification/index.php.
 */

use app\models\Notification;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	Notification::label(2),
	'Index',
];
?>

<div class="page-header">
<h1><?php echo Html::encode(Notification::label(2)); ?></h1>
</div>

<?php 


echo $this->render('_list', [
		'dataProvider'=>$dataProvider,
]);

