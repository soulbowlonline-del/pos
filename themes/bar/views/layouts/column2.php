<?php $this->beginContent('//layouts/main'); ?>
<div class="">

<?php if($this->id== 'site' && $this->action->id== 'index') {?>
	<div id="content" class="content">
	
	<?php }else{?>
		<div id="content" class="content  container">
		<?php }?>
	
	<?php echo $content; ?>
	</div>
	<!-- content -->

</div>
</div>
	<?php $this->endContent(); ?>