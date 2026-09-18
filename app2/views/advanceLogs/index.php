<?php
/**
 * Ported from protected/views/advanceLogs/index.php.
 */

use app\models\AdvanceLogs;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	AdvanceLogs::label(2),
	'Index',
];
?>

<div class="page-header">
<h1><?php echo Html::encode(AdvanceLogs::label(2)); ?></h1>
</div>

<?php 


echo $this->render('_list', [
		'dataProvider'=>$dataProvider,
]);

