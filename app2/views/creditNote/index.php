<?php
/**
 * Ported from protected/views/creditNote/index.php.
 */

use app\models\CreditNote;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	CreditNote::label(2),
	'Index',
];
?>

<div class="page-header">
<h1><?php echo Html::encode(CreditNote::label(2)); ?></h1>
</div>

<?php 


echo $this->render('_list', [
		'dataProvider'=>$dataProvider,
]);

