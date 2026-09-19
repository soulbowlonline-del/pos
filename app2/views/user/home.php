<?php
/**
 * Ported from protected/views/user/home.php.
 */

use app\models\User;
use app\widgets\ButtonGroup;
use app\widgets\Tabs;
?>
<?php
/*?><div class="user_home">



<?php   echo ButtonGroup::widget(array(
		'buttons'=>$this->context->menu,
		'type'=>'primary',
		'htmlOptions'=>array('class'=> 'pull-right'),
));
?>
<h3><?php 
echo 'Welcome'.'  '.Yii::$app->user->name?></h3>
<hr />
<?php
echo Tabs::widget(array(
'type' => 'tabs',
'tabs' => array(
		array('label' => 'Bars', 'content'=>echo $this->render('/bar/mybar', 
		array('dataProvider'=> Bar::getMyBar()), true), 'active' => true),
		//array('label' => 'User', 'content'=>echo $this->render('/user/myuser', 
		//array('dataProvider'=> User::getUsers()), true), 'active' => true),
	     array('label' => 'Transaction', 'content'=>echo $this->render('/transaction/myTransaction',
	      array('dataProvider'=> Transaction::getMyTransaction()), true),),
)
)
);
?>
</div>
<?php */?>
<!-- -------------------------------------       -->
<?php /*?>
<div class="clearfix mar_top9"></div>
<div class="container">
	 
	/*echo Tabs::widget(array(
			'type' => 'tabs',
			'tabs' => array(
					array('label' => 'Profile', 'content'=>echo $this->render('view', array('model'=>$user), true), 'active' => true),
					 array('label' => 'Passenger', 'items' => array(
							array('label' => 'Book Driver', 'content'=>echo $this->render('/journey/create', array('model'=>$journey), true)),
							array('label' => 'All Booking', 'content'=>echo $this->render('/journey/index', array('dataProvider'=>$dataProvider),true))
					)),
					  array('label' => 'Driver', 'items'=>array(
							array('label'=>'Car Details','content'=>echo $this->render('/driver/create', array('model'=>$bookdriver), true)),
							array('label'=>'All Car Details','content'=>echo $this->render('/driver/index', array('griddataProvider'=>$gridDataProvider), true))
					  	//	array('label'=>'All Car Details','content'=>echo $this->render('/driver/index', array('dataProvider'=>$dataProvider), true))
					)), 
					array('label' => 'Dispatcher', 'content'=>echo $this->render('/dispatcher/index', array('dispatcher'=>$dispatcher), true)),
			)
	)
	); 
	</div>
	<?php */?>