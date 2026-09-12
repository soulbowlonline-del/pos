<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="chrome=1">
<meta name="language" content="en" />
<meta name="description" content="">
<meta name="keywords" content="">
<meta name="author" content="">

<!-- Le HTML5 shim, for IE6-8 support of HTML elements -->
<!--[if lt IE 9]>
		<script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
	<![endif]-->

<title><?php echo CHtml::encode($this->pageTitle); ?></title>

<link rel="stylesheet" type="text/css"
	href="<?php echo Yii::app()->theme->baseUrl; ?>/css/bootstrap.css" />
<link rel="stylesheet" type="text/css"
	href="<?php echo Yii::app()->theme->baseUrl; ?>/css/animations.css" />
<link rel="stylesheet" type="text/css"
	href="<?php echo Yii::app()->theme->baseUrl; ?>/css/font-awesome.min.css" />
<link rel="stylesheet" type="text/css"
	href="<?php echo Yii::app()->theme->baseUrl; ?>/css/main.css" />
</head>

<body class="hold-transition login-page">

<?php if( Yii::app()->controller->id != 'user' && Yii::app()->controller->action->id != 'login'){?>
	<section id="header" class="dark_section">
		<div class="container">
			<div class="row">


				<a class="navbar-brand col-md-2"
					href="<?php echo Yii::app()->createUrl('site/index')?>">
					<?php /*?><img
					class="img-responsive" alt=""
					src="<?php echo Yii::app()->theme->baseUrl.'/img/logo.png'?>">*/?>POS </a>


				<div class="col-md-10 mainmenu_wrap">
					<div class="main-menu-icon visible-xs">
						<span></span><span></span><span></span>
					</div>
					<ul id="mainmenu"
						class="nav menu sf-menu responsive-menu superfish navbar-right">
						

							<?php
						
						if (Yii::app()->user->isGuest)
						{?>
						
						<li><?php echo CHtml::link('Login',array('/user/login'));?>
						</li>
						
						<?php
						}
						else
						{
							?>

						<li class=""><?php echo CHtml::link('Product',array('/product/create'));?>
						</li>
						<li><?php 
						echo CHtml::link('Logout('.Yii::app()->user->name.')',array('/user/logout'));?>
						</li>
						<?php }
						?>


					</ul>
				</div>

			</div>
		</div>
	</section>
<?php }?>



	<div id="page">

	<?php // $this->renderNavBar();?>
		<!-- header -->

	 
		<?php echo $content; ?>
 








<?php if( Yii::app()->controller->id != 'user' && Yii::app()->controller->action->id != 'login'){?>
		<section id="copyright" class="color_section">
			<div class="container">
				<div class="row">

					<div class="col-sm-12 text-center">
						<p>
							Copyright &copy; 
							<?php echo date('Y'); ?>
							<?php echo CHtml::encode(Yii::app()->params['company'])?>
							. All Rights Reserved.
						</p>
					</div>

				</div>
			</div>
		</section>
<?php }?>


		<!-- footer -->

	</div>
	<!-- page -->

	<script
		src="<?php  echo Yii::app()->theme->baseUrl; ?>/js/vendor/bootstrap.min.js"
		type="text/javascript"></script>
	<script
		src="<?php  echo Yii::app()->theme->baseUrl; ?>/js/vendor/jquery.flexslider-min.js"
		type="text/javascript"></script>
	<script
		src="<?php  echo Yii::app()->theme->baseUrl; ?>/js/vendor/jquery.fractionslider.min.js"
		type="text/javascript"></script>
	<script
		src="<?php  echo Yii::app()->theme->baseUrl; ?>/js/vendor/owl.carousel.min.js"
		type="text/javascript"></script>
	<script
		src="<?php  echo Yii::app()->theme->baseUrl; ?>/js/vendor/superfish.js"
		type="text/javascript"></script>
	<script
		src="<?php  echo Yii::app()->theme->baseUrl; ?>/js/vendor/placeholdem.min.js"
		type="text/javascript"></script>
	<script
		src="<?php  echo Yii::app()->theme->baseUrl; ?>/js/vendor/jquery.funnyText.min.js"
		type="text/javascript"></script>
	<?php /*?><script src="<?php  echo Yii::app()->theme->baseUrl; ?>/js/plugins.js"
		type="text/javascript"></script>
	<script src="<?php echo Yii::app()->theme->baseUrl; ?>/js/main.js"
		type="text/javascript"></script>*/?>

</body>

</html>
