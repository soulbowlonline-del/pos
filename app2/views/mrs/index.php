<?php
/**
 * Ported from protected/views/mrs/index.php.
 */

use app\models\Mrs;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	Mrs::label(2),
	'Index',
];
?>

<div class="page-header">
<h1><?php echo Html::encode(Mrs::label(2)); ?></h1>
</div>

<?php 


echo $this->render('_list', [
		'dataProvider'=>$dataProvider,
]);

