<?php
/**
 * Ported from protected/views/itemCompany/index.php.
 */

use app\models\ItemCompany;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	ItemCompany::label(2),
	'Index',
];
?>

<div class="page-header">
<h1><?php echo Html::encode(ItemCompany::label(2)); ?></h1>
</div>

<?php 


echo $this->render('_list', [
		'dataProvider'=>$dataProvider,
]);

