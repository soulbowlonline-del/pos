<?php
/**
 * Ported from themes/bar/views/layouts/column1.php.
 *
 * A wrapper around the main layout rather than a layout in its own right.
 * Yii 1 spells the parent '//layouts/main'; Yii 2 wants a view file. Without
 * this, user/recover answered 500 on a layout that does not exist.
 */
?>
<?php $this->beginContent('@app/views/layouts/guest.php'); ?>
<div class="container-fluid">

<div class="row-fluid main_inner">

	<div class="span12">
		<?php echo $content; ?>
	</div>

	<!-- content -->
	</div>
</div>

<?php $this->endContent(); ?>
