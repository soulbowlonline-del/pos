<?php
/**
 * Ported from protected/views/emp/index.php.
 */

use app\models\Emp;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	Emp::label(2),
	'Index',
];
?>

<div class="page-header">
<h1><?php echo Html::encode(Emp::label(2)); ?></h1>
</div>

<?php 


echo $this->render('_list', [
		'dataProvider'=>$dataProvider,
]);

