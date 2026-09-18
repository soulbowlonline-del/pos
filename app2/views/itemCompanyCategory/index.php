<?php
/**
 * Ported from protected/views/itemCompanyCategory/index.php.
 */

use app\models\ItemCompanyCategory;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	ItemCompanyCategory::label(2),
	'Index',
];
?>

<div class="page-header">
<h1><?php echo Html::encode(ItemCompanyCategory::label(2)); ?></h1>
</div>

<?php 


echo $this->render('_list', [
		'dataProvider'=>$dataProvider,
]);

