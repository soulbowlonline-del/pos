<?php
/**
 * The guest shell, ported from themes/bar/views/layouts/main.php.
 *
 * Yii 1's column1 wraps `//layouts/main`, which in the theme is this file -
 * a plain page with no sidebar and no signed-in user. The port's main.php is
 * a different layout entirely: it comes from admin_layout.php, the shell every
 * page behind a session uses.
 *
 * column1 was pointed at that one, so the three pages a guest can reach -
 * login, recover, passwordexpired - were being rendered inside the admin shell,
 * which reads the signed-in user's role_id. For a guest there is no user, and
 * /v2/user/login answered 500 on "Attempt to read property role_id on null".
 * Nothing caught it because nothing reached those pages: the suites sign in
 * through Yii 1 and never ask the port for a page while signed out.
 *
 * The header and footer sections are kept with the condition Yii 1 gives them,
 * `controller->id != 'user' && action->id != 'login'`, which is false for every
 * page that uses this layout - all three are on UserController - so neither is
 * ever rendered. Reproduced rather than simplified, so that the two layouts
 * stay comparable if another controller ever uses it.
 *
 * Theme assets are served from /themes/bar on this host, as in main.php.
 */

use yii\helpers\Html;

$theme = '/themes/bar';
$showChrome = Yii::$app->controller->id != 'user'
    && Yii::$app->controller->action->id != 'login';
?>
<?php $this->beginPage(); ?>
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

<title><?php echo Html::encode($this->title); ?></title>

<link rel="stylesheet" type="text/css"
	href="<?php echo $theme; ?>/css/bootstrap.css" />
<link rel="stylesheet" type="text/css"
	href="<?php echo $theme; ?>/css/animations.css" />
<link rel="stylesheet" type="text/css"
	href="<?php echo $theme; ?>/css/font-awesome.min.css" />
<link rel="stylesheet" type="text/css"
	href="<?php echo $theme; ?>/css/main.css" />
<?php $this->head(); ?>
</head>

<body class="hold-transition login-page">
<?php $this->beginBody(); ?>

<?php if ($showChrome) { ?>
	<section id="header" class="dark_section">
		<div class="container">
			<div class="row">
				<a class="navbar-brand col-md-2"
					href="<?php echo \app\components\Ui::to(['site/index']); ?>">DAS POS </a>

				<div class="col-md-10 mainmenu_wrap">
					<div class="main-menu-icon visible-xs">
						<span></span><span></span><span></span>
					</div>
					<ul id="mainmenu"
						class="nav menu sf-menu responsive-menu superfish navbar-right">
						<?php if (Yii::$app->user->isGuest) { ?>
						<li><?php echo Html::a('Login', \app\components\Ui::to(['user/login'])); ?>
						</li>
						<?php } else { ?>
						<li class=""><?php echo Html::a('Product', \app\components\Ui::to(['product/create'])); ?>
						</li>
						<li><?php echo Html::a('Logout(' . Yii::$app->user->identity->full_name . ')',
						                       \app\components\Ui::to(['user/logout'])); ?>
						</li>
						<?php } ?>
					</ul>
				</div>
			</div>
		</div>
	</section>
<?php } ?>

	<div id="page">

		<?php echo $content; ?>

<?php if ($showChrome) { ?>
		<section id="copyright" class="color_section">
			<div class="container">
				<div class="row">
					<div class="col-sm-12 text-center">
						<p>
							Copyright &copy;
							<?php echo date('Y'); ?>
							<?php echo Html::encode(Yii::$app->params['company'] ?? null); ?>
							. All Rights Reserved.
						</p>
					</div>
				</div>
			</div>
		</section>
<?php } ?>

	</div>
	<!-- page -->

	<script src="<?php echo $theme; ?>/js/vendor/bootstrap.min.js"
		type="text/javascript"></script>
	<script src="<?php echo $theme; ?>/js/vendor/jquery.flexslider-min.js"
		type="text/javascript"></script>
	<script src="<?php echo $theme; ?>/js/vendor/jquery.fractionslider.min.js"
		type="text/javascript"></script>
	<script src="<?php echo $theme; ?>/js/vendor/owl.carousel.min.js"
		type="text/javascript"></script>
	<script src="<?php echo $theme; ?>/js/vendor/superfish.js"
		type="text/javascript"></script>
	<script src="<?php echo $theme; ?>/js/vendor/placeholdem.min.js"
		type="text/javascript"></script>
	<script src="<?php echo $theme; ?>/js/vendor/jquery.funnyText.min.js"
		type="text/javascript"></script>

<?php $this->endBody(); ?>
</body>

</html>
<?php $this->endPage(); ?>
