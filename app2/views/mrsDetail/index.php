<?php
/**
 * Ported from protected/views/mrsDetail/index.php.
 */

use app\models\MrsDetail;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	MrsDetail::label(2),
	'Index',
];
?>

<div class="page-header">
<h1><?php echo Html::encode(MrsDetail::label(2)); ?></h1>
</div>

<?php 


echo $this->render('_list', [
		'dataProvider'=>$dataProvider,
]);

