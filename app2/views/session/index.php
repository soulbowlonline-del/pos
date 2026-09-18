<?php
/**
 * Ported from protected/views/session/index.php.
 */

use app\models\Session;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	Session::label(2),
	'Index',
];
?>

<div class="page-header">
<h1><?php echo Html::encode(Session::label(2)); ?></h1>
</div>

<?php 


echo $this->render('_list', [
		'dataProvider'=>$dataProvider,
]);

