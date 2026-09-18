<?php
/**
 * Ported from protected/views/question/index.php.
 */

use app\models\Question;
use yii\helpers\Html;
?>
<?php
$this->params['breadcrumbs'] = [
	Question::label(2),
	'Index',
];
?>

<div class="page-header">
<h1><?php echo Html::encode(Question::label(2)); ?></h1>
</div>

<?php 


echo $this->render('_list', [
		'dataProvider'=>$dataProvider,
]);

