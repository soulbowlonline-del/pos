<?php
/**
 * Ported from protected/views/tax/index.php.
 */

use app\models\Tax;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	Tax::label(2),
	'Index',
];
?>

<div class="page-header">
<h1><?php echo Html::encode(Tax::label(2)); ?></h1>
</div>

<?php 


echo $this->render('_list', [
		'dataProvider'=>$dataProvider,
]);

