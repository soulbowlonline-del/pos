<?php
/**
 * Ported from protected/views/onlineOrder/index.php.
 */

use app\models\OnlineOrder;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	OnlineOrder::label(2),
	'Index',
];
?>

<div class="page-header">
<h1><?php echo Html::encode(OnlineOrder::label(2)); ?></h1>
</div>

<?php 


echo $this->render('_list', [
		'dataProvider'=>$dataProvider,
]);

