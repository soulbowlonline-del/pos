<?php
$this->breadcrumbs=array(
	'Manage'=>array('index'),
);?>
<section class="content-header">
<h1><?php echo Yii::t('app', 'Manage database backup files'); ?></h1>

<?php $this->widget('bootstrap.widgets.TbButtonGroup', array(
	'buttons'=>$this->menu,
	'type'=>'success',
	'htmlOptions'=>array('class'=> 'pull-right'),
));
?>
<br/>
</section>

<section class="content">
  <div class="row">
    <div class="col-md-12 col-xs-12">
      <div class="box">
         <div class="box-header"><h3 class="box-title">Backup Files</h3></div>
        <div class="box-body">
          <div class="row">
            <div class="col-md-12">
<div class="table-responsive customgridwidth">

<?php $this->renderPartial('_list', array(
		'dataProvider'=>$dataProvider,
));
?>
</div>
</div>
</div>
</div>
</div>
</div>
</div>
</section>