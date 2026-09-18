<?php
/**
 * Ported from protected/views/state/index.php.
 */

use app\models\State;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	State::label(2),
	'Index',
];
?>

<div class="page-header">
<h1><?php echo Html::encode(State::label(2)); ?></h1>
</div>

<?php 


echo $this->render('_list', [
		'dataProvider'=>$dataProvider,
]);

