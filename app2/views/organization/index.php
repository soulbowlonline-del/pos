<?php
/**
 * Ported from protected/views/organization/index.php.
 */

use app\models\Organization;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	Organization::label(2),
	'Index',
];
?>

<div class="page-header">
<h1><?php echo Html::encode(Organization::label(2)); ?></h1>
</div>

<?php 


echo $this->render('_list', [
		'dataProvider'=>$dataProvider,
]);

