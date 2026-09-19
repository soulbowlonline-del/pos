<?php
/**
 * The admin shell, ported from themes/bar/views/layouts/admin_layout.php.
 *
 * Transformed mechanically rather than rewritten: createUrl became Ui::to,
 * checkPermission became Access::check, and the user came from the bridged
 * session. The markup and the theme assets are untouched - they are served
 * from /themes/bar on this same host, so a ported page is visually identical
 * to the Yii 1 one beside it.
 *
 * Every link goes through Ui::to, which hands unported controllers back to
 * Yii 1. Until the port is finished most of this sidebar points at Yii 1
 * pages, and that is what keeps navigation working.
 *
 * @var string $content
 * @var yii\web\View $this
 */

use app\components\Access;
use app\components\Ui;
use app\models\Notification;
use app\models\OnlineOrder;
use app\models\User;
use app\models\UserRole;
use yii\helpers\Html;

$this->beginPage();
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title><?php echo Html::encode(Html::encode($this->title)); ?></title>
  <!-- Tell the browser to be responsive to screen width -->
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.5.0/css/font-awesome.min.css">

   <!-- Ionicons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/ionicons/2.0.1/css/ionicons.min.css">
  
  <!-- Bootstrap 3.3.6 -->
 	<link rel="stylesheet" type="text/css"
	href="<?php echo '/themes/bar'; ?>/css/bootstrap.min.css" />
	<link rel="stylesheet" type="text/css"
	href="<?php echo '/themes/bar'; ?>/css/AdminLTE.min.css" />
    <link rel="stylesheet" type="text/css"
	href="<?php echo '/themes/bar'; ?>/css/custom.css" />
     <link rel="stylesheet" type="text/css"
	href="<?php echo '/themes/bar'; ?>/css/main.css" />
	<link rel="stylesheet" type="text/css"
	href="<?php echo '/themes/bar'; ?>/css/_all-skins.min.css" />
  <link rel="stylesheet" type="text/css"
	href="<?php echo '/themes/bar'; ?>/css/font-awesome.min.css" />
 <link rel="stylesheet" href="<?php echo '/themes/bar'; ?>/plugins/iCheck/flat/blue.css">
  <!-- Morris chart -->
  <link rel="stylesheet" href="<?php echo '/themes/bar'; ?>/plugins/morris/morris.css">
  <!-- jvectormap -->
  <link rel="stylesheet" href="<?php echo '/themes/bar'; ?>/plugins/jvectormap/jquery-jvectormap-1.2.2.css">
  <!-- Date Picker -->
  <link rel="stylesheet" href="<?php echo '/themes/bar'; ?>/plugins/datepicker/datepicker3.css">
  <!-- Daterange picker -->
  <link rel="stylesheet" href="<?php echo '/themes/bar'; ?>/plugins/daterangepicker/daterangepicker.css">
  <!-- bootstrap wysihtml5 - text editor -->
  <link rel="stylesheet" href="<?php echo '/themes/bar'; ?>/plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.min.css">
  <!-- <script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDKUS6C5HLfrcrFV9hO6ot2jo6z2CR_Eyk&callback=initMap"
  type="text/javascript"></script> -->
  <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
  <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
  <!--[if lt IE 9]>
  <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
  <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
  <![endif]-->
<?php $this->head(); ?>
</head>
<body class="hold-transition skin-blue sidebar-mini" id="main">
<?php $this->beginBody(); ?>
<div class="wrapper">

  <header class="main-header">
    <!-- Logo -->
    <a href="#" class="logo">
      <!-- mini logo for sidebar mini 50x50 pixels -->
     
      <!-- logo for regular state and mobile devices -->
      <span class="logo-lg"><b>DAS POS</b></span>
    </a>
    <!-- Header Navbar: style can be found in header.less -->
    <nav class="navbar navbar-static-top">
      <!-- Sidebar toggle button-->
      <a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button">
        <span class="sr-only">Toggle navigation</span>
      </a>

      <div class="navbar-custom-menu">
        <ul class="nav navbar-nav">
          <!-- Messages: style can be found in dropdown.less-->
          
          
               <!-- Notifications: style can be found in dropdown.less -->
          <li class="dropdown notifications-menu">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
              <i class="fa fa-bell-o"></i>
              <span class="label label-warning"></span>
            </a>
            <ul class="dropdown-menu">
            <!--   <li class="header">You have 10 notifications</li> -->
             <?php 
             $user = Yii::$app->user->identity;
           
             // A vendor sees only the notifications addressed to them; everyone
             // else sees the last 20 regardless of recipient, as in Yii 1.
             $role = UserRole::findOne(['title' => 'Vendor']);
             $query = Notification::find()->orderBy(['id' => SORT_DESC])->limit(20);
             if ($role !== null && $role->id == $user->role_id) {
             	$query->where(['to_id' => Yii::$app->user->id]);
             }
             $notifications = $query->all();
?>
              <?php if($notifications){?>
              <li>
                <!-- inner menu: contains the actual data -->
                 
                <ul class="menu">
                <?php 
              foreach($notifications as $notification){?>
              <?php 
              $link = '#';
              $type = $notification->model_type;
              if($type == Notification::TYPE_MRS){
              	if(Access::check('mrs/admin')){
              	$link = Ui::to('mrs/admin');
              	}
              }
              if($type == Notification::TYPE_MRN){
              	if(Access::check('mrn/admin')){
              		$link = Ui::to('mrn/admin');
              	}
              }
              if($type == Notification::TYPE_PO){
              	if(Access::check('purchaseOrderDetail/admin')){
              		$link = Ui::to('purchaseOrderDetail/admin');
              	}
              }
              if($type == Notification::TYPE_PBILL){
              	if(Access::check('purchaseBillDetail/admin')){
              		$link = Ui::to('purchaseBillDetail/admin');
              	}else if(Access::check('purchaseBillDetail/index')){
              		$link = Ui::to('purchaseBillDetail/index');
              	}
              }
              ?>
                  <li>
                    <a href="<?php echo $link;?>">
                      <i class="fa fa-list text-aqua"></i> <?php echo $notification->description;?> 
                      <?php  if($user){
                      if($role->id != $user->role_id ){?>
                      for <?php $vendoruser = User::findOne($notification->to_id);
                      if($vendoruser){
                      echo $vendoruser->full_name;}?>
                      <?php }}?>
                    </a>
                  </li>
                  <?php }?>
                </ul>
               
              </li>
                   <li class="footer"><a href="#">View all</a></li>
               <?php }else{?>
                 <li class="footer"><a href="#">No notifications</a></li>
               <?php }?>
         
            </ul>
          </li>
          
          
          
          <!-- Tasks: style can be found in dropdown.less -->
           
         
          <!-- User Account: style can be found in dropdown.less -->
          <li class="dropdown user user-menu">
            <a data-toggle="dropdown" class="dropdown-toggle" href="#">
              <img alt="User Image" class="user-image" src="<?php echo '/themes/bar'; ?>/img/default_user.png">
              <?php $user= Yii::$app->user->identity;?>
              <span class="hidden-xs"><?php echo isset($user)?$user->full_name:'';?></span>
            </a>
            <ul class="dropdown-menu">
              <!-- User image -->
              <li class="user-header">
                <img alt="User Image" class="img-circle" src="<?php echo '/themes/bar'; ?>/img/default_user.png">

                <p>
                 <?php echo isset($user)?$user->full_name:'';?>
                  
                </p>
              </li>
              
              <!-- Menu Footer-->
              <li class="user-footer">
                <div class="pull-left">
                  <a class="btn btn-default btn-flat" href="<?php echo Ui::to('user/view');?>">Profile</a>
                </div>
                <div class="pull-right">
                  <a class="btn btn-default btn-flat" href="<?php echo Ui::to('user/logout');?>">Sign out</a>
                </div>
              </li>
            </ul>
          </li>
          <!-- Control Sidebar Toggle Button -->
        
        </ul>
      </div>
    </nav>
  </header>
  <!-- Left side column. contains the logo and sidebar -->
  <aside class="main-sidebar">
    <!-- sidebar: style can be found in sidebar.less -->
    <section class="sidebar">
      <!-- Sidebar user panel -->
      <div class="user-panel">
        <div class="pull-left image">
         <img alt="User Image" class="img-circle" src="<?php echo '/themes/bar'; ?>/img/default_user.png">
        </div>
        <div class="pull-left info">
          <p><?php echo isset($user)?$user->full_name:'';?></p>
          <a href="#"><i class="fa fa-circle text-success"></i> Online</a>
        </div>
      </div>
      <!-- search form -->
     
      <!-- /.search form -->
      <!-- sidebar menu: : style can be found in sidebar.less -->
      <ul class="sidebar-menu">
      <?php if(!Yii::$app->user->isGuest){
      $permission = Yii::$app->user->identity;?>
      <?php if(Access::check('user/dashboard')){?>
         <li>
          <a href="<?php echo Ui::to('user/dashboard');?>">
              <i class="fa fa-dashboard"></i> <span>Dashboard</span>
           
          </a>
        </li>
        <?php }?>
          <?php if(Access::check('user/dash')){?>
         <li>
          <a href="<?php echo Ui::to('user/dash');?>">
              <i class="fa fa-dashboard"></i> <span>Dashboard</span>
           
          </a>
        </li>
        <?php }?>
         
          <?php if(Access::check('item/admin') || Access::check('itemCompany/admin')||
          		Access::check('itemCategory/admin')|| Access::check('itemdetail/admin')){?>
          		<li class=" treeview">
          <a href="#">
            <i class="fa fa-list"></i> <span>Manage Items</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-right pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
          
            <?php if(Access::check('itemCategory/admin')){?>
          <li>
          <a href="<?php echo Ui::to('itemCategory/admin');?>">
              <i class="fa fa-dot-circle-o"></i> <span>Categories</span>
           
          </a>
        </li>
        <?php }?>
         <?php if(Access::check('itemCategory/admin')){?>
          <li>
          <a href="<?php echo Ui::to('itemCategory/subcategory');?>">
              <i class="fa fa-dot-circle-o"></i> <span>Subcategory</span>
           
          </a>
        </li>
        <?php }?>
           <?php if(Access::check('itemCompany/admin')){?>
          <li>
          <a href="<?php echo Ui::to('itemCompany/admin');?>">
              <i class="fa fa-dot-circle-o"></i> <span>Companies</span>
           
          </a>
        </li>
           <?php }?>
             <?php if(Access::check('itemCompany/admin')){?>
          <li>
          <a href="<?php echo Ui::to('itemCompany/subcompany');?>">
              <i class="fa fa-dot-circle-o"></i> <span>SubCompany</span>
           
          </a>
        </li>
           <?php }?>
            <?php if(Access::check('item/admin')){?>
          <li>
          <a href="<?php echo Ui::to('item/admin');?>">
              <i class="fa fa-dot-circle-o"></i> <span>Manage Items</span>
           
          </a>
        </li>
        <?php }?>
         <?php if(Access::check('item/admin')){?>
          <li>
          <a href="<?php echo Ui::to('itemDetail/admin');?>">
              <i class="fa fa-dot-circle-o"></i> <span>Manage SubItems</span>
           
          </a>
        </li>
        <?php }?>
         <!-- <?php if(Access::check('item/adjustStock')){?>
          <li>
          <a href="<?php echo Ui::to('item/adjustStock');?>">
              <i class="fa fa-dot-circle-o"></i> <span>Stock Adjustment</span>
           
          </a>
        </li>
		<?php }?> -->
		<?php if(Access::check('item/expireStock')){?>
          <li>
          <a href="<?php echo Ui::to('item/expireStock');?>">
              <i class="fa fa-dot-circle-o"></i> <span>Stock Expire</span>
           
          </a>
        </li>
		<?php }?>
		<?php if(Access::check('itemReturnItem/admin')){?>
         <li>
          <a href="<?php echo Ui::to('itemReturnItem/admin');?>">
              <i class="fa fa-dot-circle-o"></i> <span>Stock Return</span>
           
          </a>
        </li>
        <?php }?>
        </ul>
           </li>
           <?php }?>
         
          <?php if(Access::check('vendor/admin') || Access::check('emp/admin')||
          		 Access::check('tax/admin') || Access::check('outlet/admin')|| Access::check('shift/admin')
          		|| Access::check('discount/admin')|| Access::check('shift/admin')
          		|| Access::check('designation/admin')|| Access::check('organization/admin')
          		/* || Access::check('question/admin')|| Access::check('city/admin')
          		|| Access::check('state/admin')|| Access::check('country/admin') */){?>
          		<li class=" treeview">
          <a href="#">
            <i class="fa fa-adjust"></i> <span>Basic Master</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-right pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
          
              <?php if(Access::check('vendor/admin')){?>
		<li>
          <a href="<?php echo Ui::to('vendor/admin');?>">
              <i class="fa fa-dot-circle-o"></i> <span> Manage vendor</span>
           
          </a>
        </li>
        <?php }?>
         <?php if(Access::check('vendorSchemes/admin')){?>
		<li>
          <a href="<?php echo Ui::to('vendorSchemes/admin');?>">
              <i class="fa fa-dot-circle-o"></i> <span> Manage vendor Schemes</span>
           
          </a>
        </li>
        <?php }?>
            <?php if(Access::check('emp/admin')){?>
            <li>
          <a href="<?php echo Ui::to('emp/admin');?>">
              <i class="fa fa-dot-circle-o"></i> <span>Manage Employee</span>
           
          </a>
        </li>
          <?php }?>
             <?php if(Access::check('customer/admin')){?>
            <li>
          <a href="<?php echo Ui::to('customer/admin');?>">
              <i class="fa fa-dot-circle-o"></i> <span>Manage Customer</span>
           
          </a>
        </li>
        <?php }?>
          <?php if(Access::check('tax/admin')){?>
           <li>
          <a href="<?php echo Ui::to('tax/admin');?>">
              <i class="fa fa-dot-circle-o"></i> <span>Manage Tax</span>
           
          </a>
        </li>
        <?php }?>
             <?php if(Access::check('outlet/admin')){?>
		<li>
          <a href="<?php echo Ui::to('outlet/admin');?>">
              <i class="fa fa-dot-circle-o"></i> <span> Manage Outlet</span>
           
          </a>
        </li>
        <?php }?>
            <?php if(Access::check('shift/admin')){?>
            <li>
          <a href="<?php echo Ui::to('shift/admin');?>">
              <i class="fa fa-dot-circle-o"></i> <span>Manage Shift</span>
           
          </a>
        </li>
          <?php }?>
       
         <?php if(Access::check('discount/admin')){?>
         <li>
          <a href="<?php echo Ui::to('discount/admin');?>">
              <i class="fa fa-dot-circle-o"></i> <span>Manage Discount</span>
           
          </a>
        </li>
        <?php }?>
        
         
		
           
		<?php if(Access::check('designation/admin')){?>
		<li>
          <a href="<?php echo Ui::to('designation/admin');?>">
              <i class="fa fa-dot-circle-o"></i> <span> Manage Designation</span>
           
          </a>
        </li>
        <?php }?>
        <?php if(Access::check('organization/admin')){?>
		<li>
          <a href="<?php echo Ui::to('organization/admin');?>">
              <i class="fa fa-dot-circle-o"></i> <span> Manage Organization</span>
           
          </a>
        </li>
        <?php }?>
       <?php /*?>
      
        <?php if(Access::check('question/admin')){?>
		<li>
          <a href="<?php echo Ui::to('question/admin');?>">
              <i class="fa fa-list"></i> <span> Manage Questions</span>
           
          </a>
        </li>
        <?php }*/?>
          <?php if(Access::check('country/admin')){?>
		<li>
          <a href="<?php echo Ui::to('country/admin');?>">
              <i class="fa fa-list"></i> <span> Manage Country</span>
           
          </a>
        </li>
        <?php }?>
         <?php if(Access::check('state/admin')){?>
		<li>
          <a href="<?php echo Ui::to('state/admin');?>">
              <i class="fa fa-list"></i> <span> Manage State</span>
           
          </a>
        </li>
        <?php }?>
          <?php if(Access::check('city/admin')){?>
		<li>
          <a href="<?php echo Ui::to('city/admin');?>">
              <i class="fa fa-list"></i> <span> Manage City</span>
           
          </a>
        </li>
        <?php }?>
         <?php //if(Access::check('paymentMode/admin')){?>
		<li>
          <a href="<?php echo Ui::to('paymentMode/admin');?>">
              <i class="fa fa-list"></i> <span> Manage Payment Modes</span>
           
          </a>
        </li>
        <?php //}?>
      
         
          </ul>
           </li>
          <?php }?>
         
          <?php if(Access::check('user/admin') || Access::check('userRole/admin')){?>
          		<li class=" treeview">
          <a href="#">
            <i class="fa fa-user"></i> <span>Manage Users</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-right pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
          
            <?php if(Access::check('user/admin')){?>
        <li>
          <a href="<?php echo Ui::to('user/admin');?>">
              <i class="fa fa-dot-circle-o"></i> <span> Manage User</span>
           
          </a>
        </li>
        <?php }?>
           <?php if(Access::check('userRole/admin')){?>
         <li>
          <a href="<?php echo Ui::to('userRole/admin');?>">
              <i class="fa fa-dot-circle-o"></i> <span>User Roles</span>
           
          </a>
        </li>
        <?php }?>
           
        
        
          </ul>
           </li>
          <?php }?>
         <?php if($permission->checkSelectedSession() == true){?>
        <?php if(Access::check('mrsDetail/admin')){?>
         <li>
          <a href="<?php echo Ui::to('mrsDetail/admin');?>">
              <i class="fa fa-list"></i> <span>MRS</span>
           
          </a>
        </li>
        <?php }?>
         <?php if(Access::check('mrsDetail/pending')){?>
          <li>
          <a href="<?php echo Ui::to('mrsDetail/pending');?>">
              <i class="fa fa-hourglass"></i> <span>Pending MRS</span>
           
          </a>
        </li>
        <?php }?>
        
          
          <?php if(Access::check('mrnDetail/admin')){?>
            <li>
          <a href="<?php echo Ui::to('mrnDetail/admin');?>">
              <i class="fa fa-list-alt"></i> <span>Material Receipt Note</span>
           
          </a>
        </li>
          <?php }?>
          
             <?php if(Access::check('purchaseOrder/index')){?>
            <li>
          <a href="<?php echo Ui::to('purchaseOrder/index');?>">
              <i class="fa fa-list-alt"></i> <span>Purchase Order</span>
           
          </a>
        </li>
          <li>
          <a href="<?php echo Ui::to('bill/admin');?>">
              <i class="fa fa-list-alt"></i> <span>Bills</span>
           
          </a>
        </li>
          <?php }else{?>
          <?php if(Access::check('purchaseOrderDetail/admin')){?>
            <li>
          <a href="<?php echo Ui::to('purchaseOrderDetail/admin');?>">
              <i class="fa fa-list-alt"></i> <span>Purchase Order</span>
           
          </a>
        </li>
          <?php }?>
          <?php }?>
          <?php if(Access::check('purchaseBillDetail/index')){?>
            <li>
          <a href="<?php echo Ui::to('purchaseBillDetail/index');?>">
              <i class="fa fa-list-alt"></i> <span>Goods Received Note</span>
           
          </a>
        </li>
          <?php }else{?>
          <?php if(Access::check('purchaseBillDetail/admin')){?>
            <li>
          <a href="<?php echo Ui::to('purchaseBillDetail/admin');?>">
              <i class="fa fa-list-alt"></i> <span>Goods Received Note</span>
           
          </a>
        </li>
          <?php }?>
          <?php }?>
          <?php }?>
         <?php if(Access::check('purchaseBillDetail/index') || Access::check('purchaseOrder/index')){?>
           <li>
          <a href="<?php echo Ui::to('purchaseBillDetail/list');?>">
             <i class="fa fa-list-alt"></i> <span>GRN Details</span>
           
          </a>
        </li>
        <?php }?>
		
		
		<li>
          <a href="<?php echo Ui::to('b2bpurchaseBillDetail/index');?>">
              <i class="fa fa-list-alt"></i> <span>B2B Sales</span>
           
          </a>
        </li>
		
		
		<li>
          <a href="<?php echo Ui::to('b2bpurchaseBillDetail/list');?>">
              <i class="fa fa-list-alt"></i> <span>B2B Sale Details</span>
           
          </a>
        </li>
		
		
           <?php if(Access::check('purchaseBillDetail/index')){?>
        <li>
          <a href="<?php echo Ui::to('session/admin');?>">
             <i class="fa fa-list-alt"></i> <span>Session</span>
           
          </a>
        </li>
        <?php }?>
        <?php /*?>
        <li>
          <a href="<?php echo Ui::to('advancePayment/admin');?>">
             <i class="fa fa-list-alt"></i> <span>Advance Payment</span>
           
          </a>
        </li>*/?>
           <?php if(Access::check('order/userWise') || Access::check('order/itemWise')||
          		Access::check('order/deptWise')|| Access::check('order/compWise')||
           		Access::check('order/admin') || Access::check('orderItem/admin') ||
           		Access::check('tax/admin') || Access::check('itemTax/admin') ||
           		Access::check('order/groupTax') || Access::check('order/b2bReport') || Access::check('order/tax') ||
           		Access::check('purchaseBillDetail/report') || Access::check('purchaseBill/admin') ||
           		Access::check('paymentReport/admin') ||Access::check('orderRefundItem/admin') ||
           		Access::check('stockAdjustLog/admin') || Access::check('itemExpire/admin') ||
           		Access::check('itemReturn/admin') || Access::check('customer/admin')){?>
          		
				
							
				<li class=" treeview">
          <a href="#">
            <i class="fa fa-file-text"></i> <span>Manage B2B Reports</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-right pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
          
            <?php if(Access::check('order/userWise')){?>
             	<li>
          <a href="<?php echo Ui::to('b2bpurchaseBill/userWise');?>">
              <i class="fa fa-dot-circle-o"></i> <span>B2B UserWise Sale Report</span>
           
          </a>
        </li>
		 <?php }?>
		  <?php if(Access::check('order/itemWise')){?>
             <li>
          <a href="<?php echo Ui::to('order/b2bitemwise');?>">
              <i class="fa fa-dot-circle-o"></i> <span>ItemWise Sale Report</span>
           
          </a>
        </li>
        <?php }?>
		 <?php if(Access::check('order/admin')){?>
          <li>
          <a href="<?php echo Ui::to('order/b2borders');?>">
              <i class="fa fa-dot-circle-o"></i> <span>B2b Orders</span>
           
          </a>
        </li>
        <?php }?>
           <?php if(Access::check('orderItem/admin')){?>
          <li>
          <a href="<?php echo Ui::to('orderItem/b2bdetail');?>">
              <i class="fa fa-dot-circle-o"></i> <span>B2b Order Details</span>
           
          </a>
        </li>
		  <li>
          <a href="<?php echo Ui::to('orderItem/b2bsales');?>">
              <i class="fa fa-dot-circle-o"></i> <span>B2b sale Report</span>
           
          </a>
        </li>
           <?php }?>
		   
		 <?php if(Access::check('order/b2bReport')){?>
         <li>
          <!--<a href="<?php echo Ui::to('order/b2bReport');?>">-->
          <a href="<?php echo Ui::to('b2bpurchaseBillDetail/taxwise');?>">
              <i class="fa fa-dot-circle-o"></i> <span>B2B Department group tax wise</span>
           
          </a>
        </li>
        <?php }?>  
			</ul>
</li>			
				
          		<li class=" treeview">
          <a href="#">
            <i class="fa fa-file-text"></i> <span>Manage Reports</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-right pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
          
            <?php if(Access::check('order/userWise')){?>
              <li>
          <a href="<?php echo Ui::to('order/userWise');?>">
              <i class="fa fa-dot-circle-o"></i> <span>UserWise Sale Report</span>
           
          </a>
        </li>
        <?php }?>
         <?php if(Access::check('order/itemWise')){?>
             <li>
          <a href="<?php echo Ui::to('order/itemWise');?>">
              <i class="fa fa-dot-circle-o"></i> <span>ItemWise Sale Report</span>
           
          </a>
        </li>
        <?php }?>
        <?php if(Access::check('order/deptWise')){?>
        <li>
          <a href="<?php echo Ui::to('order/deptWise');?>">
              <i class="fa fa-dot-circle-o"></i> <span>Deptwise Sale Report</span>
           
          </a>
        </li>
        <?php }?>
        <?php if(Access::check('order/compWise')){?>
        <li>
          <a href="<?php echo Ui::to('order/compWise');?>">
              <i class="fa fa-dot-circle-o"></i> <span>Company Wise Sale Report</span>
           
          </a>
        </li>
        <?php }?>
        <?php if(Access::check('order/vendorWise')){?>
        <li>
          <a href="<?php echo Ui::to('order/vendorWise');?>">
              <i class="fa fa-dot-circle-o"></i> <span>Vendor Wise Sale Report</span>
           
          </a>
        </li>
        <?php }?>
        
         <?php if(Access::check('order/admin')){?>
          <li>
          <a href="<?php echo Ui::to('order/admin');?>">
              <i class="fa fa-dot-circle-o"></i> <span>Orders</span>
           
          </a>
        </li>
        <?php }?>
           <?php if(Access::check('orderItem/admin')){?>
          <li>
          <a href="<?php echo Ui::to('orderItem/admin');?>">
              <i class="fa fa-dot-circle-o"></i> <span>Order Details</span>
           
          </a>
        </li>
           <?php }?>
            <?php if(Access::check('tax/admin')){?>
          <li>
          <a href="<?php echo Ui::to('tax/admin');?>">
              <i class="fa fa-dot-circle-o"></i> <span>Taxes</span>
           
          </a>
        </li>
        <?php }?>
        <?php if(Access::check('itemTax/admin')){?>
         <li>
          <a href="<?php echo Ui::to('itemTax/admin');?>">
              <i class="fa fa-dot-circle-o"></i> <span>Item Taxes</span>
           
          </a>
        </li>
        <?php }?>
         <?php if(Access::check('order/groupTax')){?>
         <li>
          <a href="<?php echo Ui::to('order/groupTax');?>">
              <i class="fa fa-dot-circle-o"></i> <span>Department group tax wise</span>
           
          </a>
        </li>
        <?php }?>
		<?php if(Access::check('order/groupTax')){?>
         <li>
          <a href="<?php echo Ui::to('order/grouphsntax');?>">
              <i class="fa fa-dot-circle-o"></i> <span>Department group hsncode wise</span>
           
          </a>
        </li>
        <?php }?>
         <?php if(Access::check('order/b2bReport')){?>
         <li>
          <a href="<?php echo Ui::to('order/b2bReport');?>">
              <i class="fa fa-dot-circle-o"></i> <span>B2B Department group tax wise</span>
           
          </a>
        </li>
        <?php }?>
        
        <?php if(Access::check('order/tax')){?>
         <li>
          <a href="<?php echo Ui::to('order/tax');?>">
              <i class="fa fa-dot-circle-o"></i> <span>Order Taxes</span>
           
          </a>
        </li>
        <?php }?>
          <?php if(Access::check('purchaseBillDetail/report')){?>
            <li>
          <a href="<?php echo Ui::to('purchaseBillDetail/report');?>">
              <i class="fa fa-dot-circle-o"></i> <span>Tally Report</span>
           
          </a>
        </li>
        <?php }?>
           <?php if(Access::check('purchaseBill/admin')){?>
          <li>
          <a href="<?php echo Ui::to('purchaseBill/admin');?>">
              <i class="fa fa-dot-circle-o"></i> <span>RBI Report</span>
           
          </a>
        </li>
        <?php }?>
		 <li>
          <a href="<?php echo Ui::to('itemReturnItem/report');?>">
              <i class="fa fa-dot-circle-o"></i> <span>Debit Return Report</span>
           
          </a>
        </li>
          <?php if(Access::check('paymentReport/admin')){?>
        <li>
          <a href="<?php echo Ui::to('paymentReport/admin');?>">
              <i class="fa fa-dot-circle-o"></i> <span>Payment Report</span>
           
          </a>
        </li>
        <?php }?>
         <?php if(Access::check('orderRefundItem/admin')){?>
          <li>
          <a href="<?php echo Ui::to('orderRefundItem/admin');?>">
              <i class="fa fa-dot-circle-o"></i> <span>Refund Report</span>
           
          </a>
        </li>
        <?php }?>
         <?php if(Access::check('stockAdjustLog/admin')){?>
          <li>
          <a href="<?php echo Ui::to('stockAdjustLog/admin');?>">
              <i class="fa fa-dot-circle-o"></i> <span>Stock Adjust Report</span>
           
          </a>
        </li>
        <?php }?>
         <?php  if(Access::check('item/report')){?>
          <li>
          <a href="<?php echo Ui::to('item/report');?>">
              <i class="fa fa-dot-circle-o"></i> <span>Stock Report</span>
           
          </a>
        </li>
        <?php }?>
        <?php if(Access::check('itemExpire/admin')){?>
        <li>
          <a href="<?php echo Ui::to('itemExpire/admin');?>">
              <i class="fa fa-dot-circle-o"></i> <span>Item Expire</span>
           
          </a>
        </li>
        <?php }?>
         <?php if(Access::check('itemReturn/admin')){?>
        <li>
          <a href="<?php echo Ui::to('itemReturn/admin');?>">
              <i class="fa fa-dot-circle-o"></i> <span>Purchase Return Report</span>
           
          </a>
        </li>
        <?php }?>
         <?php if(Access::check('customer/admin')){?>
        <li>
          <a href="<?php echo Ui::to('customer/admin');?>">
              <i class="fa fa-dot-circle-o"></i> <span>Customers</span>
           
          </a>
        </li>
        <?php }?>
        <li>
          <a href="<?php echo Ui::to('item/scannedItems');?>">
              <i class="fa fa-dot-circle-o"></i> <span>Scanned Items</span>
           
          </a>
        </li>
        <li>
          <a href="<?php echo Ui::to('customer/whatsapplogs');?>">
              <i class="fa fa-dot-circle-o"></i> <span>Whatsapp Logs</span>
           
          </a>
        </li>
        <?php //}?>
        </ul>
           </li>
           <?php }?>
              <?php if(Access::check('creditNote/admin')){?>
            <li>
          <a href="<?php echo Ui::to('creditNote/admin');?>">
              <i class="fa fa-credit-card"></i> <span> Manage Credit Notes</span>
           
          </a>
        </li>
        <?php }?>

        <li class="treeview">
          <a href="#">
            <i class="fa fa-gift"></i> <span>Loyalty Program</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-right pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
            <li>
              <a href="<?php echo Ui::to('loyaltyAdmin/customers'); ?>">
                <i class="fa fa-dot-circle-o"></i> <span>Customers</span>
              </a>
            </li>
            <li>
              <a href="<?php echo Ui::to('loyaltyAdmin/reports'); ?>">
                <i class="fa fa-dot-circle-o"></i> <span>Reports</span>
              </a>
            </li>
            <li>
              <a href="<?php echo Ui::to('loyaltyAdmin/settings'); ?>">
                <i class="fa fa-dot-circle-o"></i> <span>Settings</span>
              </a>
            </li>
          </ul>
        </li>
         <?php if(Access::check('permission/admin')){?>
        <li>
          <a href="<?php echo Ui::to('permission/admin');?>">
              <i class="fa fa-briefcase"></i> <span> Manage Permission</span>
           
          </a>
        </li>
        <?php }?>
         <?php if(Access::check('rolePermission/admin')){?>
        <li>
          <a href="<?php echo Ui::to('rolePermission/admin');?>">
              <i class="fa fa-paper-plane"></i> <span> Manage Role Permission</span>
           
          </a>
        </li>
        <?php }?>
            <?php  if(Access::check('onlineOrder/admin')){
            	$count = OnlineOrder::find()->where(['type_id' => OnlineOrder::TYPE_NEW])->count();
            	
            	?>
        <li>
          <a href="<?php echo Ui::to('onlineOrder/admin');?>">
              <i class="fa fa-list"></i> <span> Online Orders
              <span
								class="sidebar-counter"><?php echo $count;?></span>
              </span>
           
          </a>
        </li>
        <?php }?>
        <?php if($permission->role_id ==1){?>
         <li>
          <a href="<?php echo Ui::to('backup/default/index');?>">
              <i class="fa fa-download"></i> <span>Backup</span>
           
          </a>
        </li>
        <?php }?>
        <?php }?>
         <?php /*if(!Yii::$app->user->isGuest){?>
        <li class=" treeview">
          <a href="#">
            <i class="fa fa-list"></i> <span>Manage Homes</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-right pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
            <li class="active"><a href="<?php echo Ui::to('home/admin');?>"><i class="fa fa-circle-o"></i>Homes</a></li>
             <li><a href="<?php echo Ui::to('homeCategory/admin');?>"><i class="fa fa-circle-o"></i>Home Categories</a></li>
          </ul>
        </li>
        <?php }*/?>
          <?php /*if((Yii::$app->user->identity && Yii::$app->user->identity->role_id == 1)){?>
        <li class=" treeview">
          <a href="#">
            <i class="fa fa-user"></i> <span>Manage User</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-right pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
            <li class="active"><a href="<?php echo Ui::to('user/admin/role_id/'.User::ROLE_MERCHANT);?>"><i class="fa fa-circle-o"></i>Merchant</a></li>
            <li><a href="<?php echo Ui::to('user/admin/role_id/'.User::ROLE_USER);?>"><i class="fa fa-circle-o"></i>Customer</a></li>
          </ul>
        </li>
        <?php }
         <li class=" treeview">
          <a href="#">
            <i class="fa fa-tags fa-fw"></i> <span>Catalog</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-right pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
            <?php if((Yii::$app->user->identity && Yii::$app->user->identity->role_id == 1)){?>
            <li ><a href="<?php echo Ui::to('category/admin');?>"><i class="fa fa-circle-o"></i>Categories</a></li>
             
             <?php }?>
         </ul>
        </li>*/?>
        
          
       
        
      </ul>
    </section>
    <!-- /.sidebar -->
  </aside>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
   <?php echo $content; ?>
    
  </div>
  <!-- /.content-wrapper -->
  <footer class="main-footer">
    
    <strong>Copyright &copy; <?php echo date('Y');?> <a href="#"><?php echo Html::encode(Yii::$app->params['company'])?></a>.</strong> All rights
    reserved.
  </footer>

  
  <!-- /.control-sidebar -->
  <!-- Add the sidebar's background. This div must be placed
       immediately after the control sidebar -->
  <div class="control-sidebar-bg"></div>
</div>
<!-- ./wrapper -->
<!-- jQuery 2.2.3 -->

<!-- jQuery UI 1.11.4 -->

<!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
<script>
$('a[title]').on('mouseenter', function () {
	   var $this = $(this);
	   $this.attr('title-cache', $this.attr('title'));
	   $this.attr('title', '');
	});

	$('a[title]').on('mouseleave', function () {
	   var $this = $(this);
	   $this.attr('title', $this.attr('title-cache'));
	   $this.attr('title-cache', '');
	});
  <?php /*?>$.widget.bridge('uibutton', $.ui.button);*/ ?>
  $( document ).ready(function() {
	  checkcount();
	});
  var ajax_call = function() {
		checkcount();
	};

	function checkcount(){
		$.ajax({
		       url: '<?php echo Ui::to('onlineOrder/countOrders'); ?>',
		       type: 'get',

		       success: function (data) {
			       if(data != 0){
		    	$('.sidebar-counter').html(data);
		    	$('.sidebar-counter').show();
			       }else{
			    	   $('.sidebar-counter').hide();
			       }
		       }
			 
		  }); 
	}
	var interval = 1000 * 60 * 2; // where X is your every X minutes

	setInterval(ajax_call, interval);
</script>
<!-- Bootstrap 3.3.6 -->

<!-- Morris.js charts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/raphael/2.1.0/raphael-min.js"></script>
<script src="<?php  echo '/themes/bar'; ?>/plugins/morris/morris.min.js"></script>
<!-- Sparkline -->
<script src="<?php  echo '/themes/bar'; ?>/plugins/sparkline/jquery.sparkline.min.js"></script>
<!-- jvectormap -->
<script src="<?php  echo '/themes/bar'; ?>/plugins/jvectormap/jquery-jvectormap-1.2.2.min.js"></script>
<script src="<?php  echo '/themes/bar'; ?>/plugins/jvectormap/jquery-jvectormap-world-mill-en.js"></script>
<!-- jQuery Knob Chart -->
<script src="<?php  echo '/themes/bar'; ?>/plugins/knob/jquery.knob.js"></script>
<!-- daterangepicker -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.11.2/moment.min.js"></script>

<!-- Bootstrap WYSIHTML5 -->
<script src="<?php  echo '/themes/bar'; ?>/plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.all.min.js"></script>
<!-- Slimscroll -->
<script src="<?php  echo '/themes/bar'; ?>/plugins/slimScroll/jquery.slimscroll.min.js"></script>
<!-- FastClick -->
<script src="<?php  echo '/themes/bar'; ?>/plugins/fastclick/fastclick.js"></script>
<!-- AdminLTE App -->
<script src="<?php  echo '/themes/bar'; ?>/js/app.min.js"></script>
<!-- AdminLTE dashboard demo (This is only for demo purposes) -->
<?php /*?>
<script src="<?php  echo '/themes/bar'; ?>/js/dashboard.js"></script>*/?>
<!-- AdminLTE for demo purposes -->

<!-- jQuery 2.2.3 -->

<?php $this->endBody(); ?>
</body>
</html>

<?php $this->endPage(); ?>
