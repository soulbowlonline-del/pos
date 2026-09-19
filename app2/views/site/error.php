<?php
/**
 * Ported from protected/views/site/error.php.
 */

use yii\helpers\Html;
?>
<?php
/* @var $this SiteController */
/* @var $error array */

$this->title=Yii::$app->name . ' - Error';
$this->params['breadcrumbs'] =[
	'Error',
];
?>

<h2>Error <?php echo $code; ?></h2>

<div class="error">
<?php echo Html::encode($message); ?>
</div>