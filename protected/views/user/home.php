<?php /*?><div class="user_home">



<?php   $this->widget('bootstrap.widgets.TbButtonGroup', array(
		'buttons'=>$this->menu,
		'type'=>'primary',
		'htmlOptions'=>array('class'=> 'pull-right'),
));
?>
<h3><?php 
echo 'Welcome'.'  '.Yii::app()->user->name?></h3>
<hr />
<?php
$this->widget('bootstrap.widgets.TbTabs', array(
'type' => 'tabs',
'tabs' => array(
		array('label' => 'Bars', 'content'=>$this->renderPartial('/bar/mybar', 
		array('dataProvider'=> Bar::getMyBar()), true), 'active' => true),
		//array('label' => 'User', 'content'=>$this->renderPartial('/user/myuser', 
		//array('dataProvider'=> User::getUsers()), true), 'active' => true),
	     array('label' => 'Transaction', 'content'=>$this->renderPartial('/transaction/myTransaction',
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
	 
	/*$this->widget('bootstrap.widgets.TbTabs', array(
			'type' => 'tabs',
			'tabs' => array(
					array('label' => 'Profile', 'content'=>$this->renderPartial('view', array('model'=>$user), true), 'active' => true),
					 array('label' => 'Passenger', 'items' => array(
							array('label' => 'Book Driver', 'content'=>$this->renderPartial('/journey/create', array('model'=>$journey), true)),
							array('label' => 'All Booking', 'content'=>$this->renderPartial('/journey/index', array('dataProvider'=>$dataProvider),true))
					)),
					  array('label' => 'Driver', 'items'=>array(
							array('label'=>'Car Details','content'=>$this->renderPartial('/driver/create', array('model'=>$bookdriver), true)),
							array('label'=>'All Car Details','content'=>$this->renderPartial('/driver/index', array('griddataProvider'=>$gridDataProvider), true))
					  	//	array('label'=>'All Car Details','content'=>$this->renderPartial('/driver/index', array('dataProvider'=>$dataProvider), true))
					)), 
					array('label' => 'Dispatcher', 'content'=>$this->renderPartial('/dispatcher/index', array('dispatcher'=>$dispatcher), true)),
			)
	)
	); 
	</div>
	<?php */?>