<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title><?php echo CHtml::encode($this->pageTitle); ?></title>
  <!-- Tell the browser to be responsive to screen width -->
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.5.0/css/font-awesome.min.css">

   <!-- Ionicons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/ionicons/2.0.1/css/ionicons.min.css">
  
  <!-- Bootstrap 3.3.6 -->
 	<link rel="stylesheet" type="text/css"
	href="<?php echo Yii::app()->theme->baseUrl; ?>/css/bootstrap.min.css" />
	<link rel="stylesheet" type="text/css"
	href="<?php echo Yii::app()->theme->baseUrl; ?>/css/AdminLTE.min.css" />
    <link rel="stylesheet" type="text/css"
	href="<?php echo Yii::app()->theme->baseUrl; ?>/css/custom.css" />
     <link rel="stylesheet" type="text/css"
	href="<?php echo Yii::app()->theme->baseUrl; ?>/css/main.css" />
	<link rel="stylesheet" type="text/css"
	href="<?php echo Yii::app()->theme->baseUrl; ?>/css/_all-skins.min.css" />
  <link rel="stylesheet" type="text/css"
	href="<?php echo Yii::app()->theme->baseUrl; ?>/css/font-awesome.min.css" />
 <link rel="stylesheet" href="<?php echo Yii::app()->theme->baseUrl; ?>/plugins/iCheck/flat/blue.css">
  <!-- Morris chart -->
  <link rel="stylesheet" href="<?php echo Yii::app()->theme->baseUrl; ?>/plugins/morris/morris.css">
  <!-- jvectormap -->
  <link rel="stylesheet" href="<?php echo Yii::app()->theme->baseUrl; ?>/plugins/jvectormap/jquery-jvectormap-1.2.2.css">
  <!-- Date Picker -->
  <link rel="stylesheet" href="<?php echo Yii::app()->theme->baseUrl; ?>/plugins/datepicker/datepicker3.css">
  <!-- Daterange picker -->
  <link rel="stylesheet" href="<?php echo Yii::app()->theme->baseUrl; ?>/plugins/daterangepicker/daterangepicker.css">
  <!-- bootstrap wysihtml5 - text editor -->
  <link rel="stylesheet" href="<?php echo Yii::app()->theme->baseUrl; ?>/plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.min.css">
  <!-- <script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDKUS6C5HLfrcrFV9hO6ot2jo6z2CR_Eyk&callback=initMap"
  type="text/javascript"></script> -->
  <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
  <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
  <!--[if lt IE 9]>
  <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
  <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
  <![endif]-->
</head>
<body class="hold-transition skin-blue sidebar-mini" id="main">
<div class="wrapper">

  <header class="main-header">
    <!-- Logo -->
    <a href="#" class="logo">
      <!-- mini logo for sidebar mini 50x50 pixels -->
     
      <!-- logo for regular state and mobile devices -->
      <span class="logo-lg"><b>POS</b></span>
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
             $user = Yii::app()->user->model;
           
             $role = UserRole::model()->findByAttributes(array('title'=>'Vendor'));
             if($role->id == $user->role_id ){
             	
             	$criteria = new CDbCriteria();
             	$criteria->order = 'id desc';
             	$criteria->limit = '20';
             	$criteria->addCondition('to_id ='.Yii::app()->user->id);
             	$notifications = Notification::model()->findAll($criteria);
             }else{
             	$criteria = new CDbCriteria();
             	$criteria->order = 'id desc';
             	$criteria->limit = '20';
             	$notifications = Notification::model()->findAll($criteria);
             
}?>
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
              	if($user->checkPermission('mrs/admin')){
              	$link = Yii::app()->createUrl('mrs/admin');
              	}
              }
              if($type == Notification::TYPE_MRN){
              	if($user->checkPermission('mrn/admin')){
              		$link = Yii::app()->createUrl('mrn/admin');
              	}
              }
              if($type == Notification::TYPE_PO){
              	if($user->checkPermission('purchaseOrderDetail/admin')){
              		$link = Yii::app()->createUrl('purchaseOrderDetail/admin');
              	}
              }
              if($type == Notification::TYPE_PBILL){
              	if($user->checkPermission('purchaseBillDetail/admin')){
              		$link = Yii::app()->createUrl('purchaseBillDetail/admin');
              	}else if($user->checkPermission('purchaseBillDetail/index')){
              		$link = Yii::app()->createUrl('purchaseBillDetail/index');
              	}
              }
              ?>
                  <li>
                    <a href="<?php echo $link;?>">
                      <i class="fa fa-list text-aqua"></i> <?php echo $notification->description;?> 
                      <?php  if($user){
                      if($role->id != $user->role_id ){?>
                      for <?php $vendoruser = User::model()->findByPk($notification->to_id);
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
              <img alt="User Image" class="user-image" src="<?php echo Yii::app()->theme->baseUrl; ?>/img/default_user.png">
              <?php $user= Yii::app()->user->model;?>
              <span class="hidden-xs"><?php echo isset($user)?$user->full_name:'';?></span>
            </a>
            <ul class="dropdown-menu">
              <!-- User image -->
              <li class="user-header">
                <img alt="User Image" class="img-circle" src="<?php echo Yii::app()->theme->baseUrl; ?>/img/default_user.png">

                <p>
                 <?php echo isset($user)?$user->full_name:'';?>
                  
                </p>
              </li>
              
              <!-- Menu Footer-->
              <li class="user-footer">
                <div class="pull-left">
                  <a class="btn btn-default btn-flat" href="<?php echo Yii::app()->createUrl('user/view');?>">Profile</a>
                </div>
                <div class="pull-right">
                  <a class="btn btn-default btn-flat" href="<?php echo Yii::app()->createUrl('user/logout');?>">Sign out</a>
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
         <img alt="User Image" class="img-circle" src="<?php echo Yii::app()->theme->baseUrl; ?>/img/default_user.png">
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
      <?php if(!Yii::app()->user->isGuest){
      $permission = Yii::app()->user->model;?>
      <?php if($permission->checkPermission('user/dashboard')){?>
         <li>
          <a href="<?php echo Yii::app()->createUrl('user/dashboard');?>">
              <i class="fa fa-dashboard"></i> <span>Dashboard</span>
           
          </a>
        </li>
        <?php }?>
          <?php if($permission->checkPermission('user/dash')){?>
         <li>
          <a href="<?php echo Yii::app()->createUrl('user/dash');?>">
              <i class="fa fa-dashboard"></i> <span>Dashboard</span>
           
          </a>
        </li>
        <?php }?>
         
          <?php if($permission->checkPermission('item/admin') || $permission->checkPermission('itemCompany/admin')||
          		$permission->checkPermission('itemCategory/admin')|| $permission->checkPermission('itemdetail/admin')){?>
          		<li class=" treeview">
          <a href="#">
            <i class="fa fa-list"></i> <span>Manage Items</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-right pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
          
            <?php if($permission->checkPermission('itemCategory/admin')){?>
          <li>
          <a href="<?php echo Yii::app()->createUrl('itemCategory/admin');?>">
              <i class="fa fa-dot-circle-o"></i> <span>Categories</span>
           
          </a>
        </li>
        <?php }?>
         <?php if($permission->checkPermission('itemCategory/admin')){?>
          <li>
          <a href="<?php echo Yii::app()->createUrl('itemCategory/subcategory');?>">
              <i class="fa fa-dot-circle-o"></i> <span>Subcategory</span>
           
          </a>
        </li>
        <?php }?>
           <?php if($permission->checkPermission('itemCompany/admin')){?>
          <li>
          <a href="<?php echo Yii::app()->createUrl('itemCompany/admin');?>">
              <i class="fa fa-dot-circle-o"></i> <span>Companies</span>
           
          </a>
        </li>
           <?php }?>
             <?php if($permission->checkPermission('itemCompany/admin')){?>
          <li>
          <a href="<?php echo Yii::app()->createUrl('itemCompany/subcompany');?>">
              <i class="fa fa-dot-circle-o"></i> <span>SubCompany</span>
           
          </a>
        </li>
           <?php }?>
            <?php if($permission->checkPermission('item/admin')){?>
          <li>
          <a href="<?php echo Yii::app()->createUrl('item/admin');?>">
              <i class="fa fa-dot-circle-o"></i> <span>Manage Items</span>
           
          </a>
        </li>
        <?php }?>
         <?php if($permission->checkPermission('item/admin')){?>
          <li>
          <a href="<?php echo Yii::app()->createUrl('itemDetail/admin');?>">
              <i class="fa fa-dot-circle-o"></i> <span>Manage SubItems</span>
           
          </a>
        </li>
        <?php }?>
         <?php if($permission->checkPermission('item/adjustStock')){?>
          <li>
          <a href="<?php echo Yii::app()->createUrl('item/adjustStock');?>">
              <i class="fa fa-dot-circle-o"></i> <span>Stock Adjustment</span>
           
          </a>
        </li>
          <li>
          <a href="<?php echo Yii::app()->createUrl('item/expireStock');?>">
              <i class="fa fa-dot-circle-o"></i> <span>Stock Expire</span>
           
          </a>
        </li>
         <li>
          <a href="<?php echo Yii::app()->createUrl('itemReturnItem/admin');?>">
              <i class="fa fa-dot-circle-o"></i> <span>Stock Return</span>
           
          </a>
        </li>
        <?php }?>
        </ul>
           </li>
           <?php }?>
         
          <?php if($permission->checkPermission('vendor/admin') || $permission->checkPermission('emp/admin')||
          		 $permission->checkPermission('tax/admin') || $permission->checkPermission('outlet/admin')|| $permission->checkPermission('shift/admin')
          		|| $permission->checkPermission('discount/admin')|| $permission->checkPermission('shift/admin')
          		|| $permission->checkPermission('designation/admin')|| $permission->checkPermission('organization/admin')
          		/* || $permission->checkPermission('question/admin')|| $permission->checkPermission('city/admin')
          		|| $permission->checkPermission('state/admin')|| $permission->checkPermission('country/admin') */){?>
          		<li class=" treeview">
          <a href="#">
            <i class="fa fa-adjust"></i> <span>Basic Master</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-right pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
          
              <?php if($permission->checkPermission('vendor/admin')){?>
		<li>
          <a href="<?php echo Yii::app()->createUrl('vendor/admin');?>">
              <i class="fa fa-dot-circle-o"></i> <span> Manage vendor</span>
           
          </a>
        </li>
        <?php }?>
         <?php if($permission->checkPermission('vendorSchemes/admin')){?>
		<li>
          <a href="<?php echo Yii::app()->createUrl('vendorSchemes/admin');?>">
              <i class="fa fa-dot-circle-o"></i> <span> Manage vendor Schemes</span>
           
          </a>
        </li>
        <?php }?>
            <?php if($permission->checkPermission('emp/admin')){?>
            <li>
          <a href="<?php echo Yii::app()->createUrl('emp/admin');?>">
              <i class="fa fa-dot-circle-o"></i> <span>Manage Employee</span>
           
          </a>
        </li>
          <?php }?>
             <?php if($permission->checkPermission('customer/admin')){?>
            <li>
          <a href="<?php echo Yii::app()->createUrl('customer/admin');?>">
              <i class="fa fa-dot-circle-o"></i> <span>Manage Customer</span>
           
          </a>
        </li>
        <?php }?>
          <?php if($permission->checkPermission('tax/admin')){?>
           <li>
          <a href="<?php echo Yii::app()->createUrl('tax/admin');?>">
              <i class="fa fa-dot-circle-o"></i> <span>Manage Tax</span>
           
          </a>
        </li>
        <?php }?>
             <?php if($permission->checkPermission('outlet/admin')){?>
		<li>
          <a href="<?php echo Yii::app()->createUrl('outlet/admin');?>">
              <i class="fa fa-dot-circle-o"></i> <span> Manage Outlet</span>
           
          </a>
        </li>
        <?php }?>
            <?php if($permission->checkPermission('shift/admin')){?>
            <li>
          <a href="<?php echo Yii::app()->createUrl('shift/admin');?>">
              <i class="fa fa-dot-circle-o"></i> <span>Manage Shift</span>
           
          </a>
        </li>
          <?php }?>
       
         <?php if($permission->checkPermission('discount/admin')){?>
         <li>
          <a href="<?php echo Yii::app()->createUrl('discount/admin');?>">
              <i class="fa fa-dot-circle-o"></i> <span>Manage Discount</span>
           
          </a>
        </li>
        <?php }?>
        
         
		
           
		<?php if($permission->checkPermission('designation/admin')){?>
		<li>
          <a href="<?php echo Yii::app()->createUrl('designation/admin');?>">
              <i class="fa fa-dot-circle-o"></i> <span> Manage Designation</span>
           
          </a>
        </li>
        <?php }?>
        <?php if($permission->checkPermission('organization/admin')){?>
		<li>
          <a href="<?php echo Yii::app()->createUrl('organization/admin');?>">
              <i class="fa fa-dot-circle-o"></i> <span> Manage Organization</span>
           
          </a>
        </li>
        <?php }?>
       <?php /*?>
      
        <?php if($permission->checkPermission('question/admin')){?>
		<li>
          <a href="<?php echo Yii::app()->createUrl('question/admin');?>">
              <i class="fa fa-list"></i> <span> Manage Questions</span>
           
          </a>
        </li>
        <?php }*/?>
          <?php if($permission->checkPermission('country/admin')){?>
		<li>
          <a href="<?php echo Yii::app()->createUrl('country/admin');?>">
              <i class="fa fa-list"></i> <span> Manage Country</span>
           
          </a>
        </li>
        <?php }?>
         <?php if($permission->checkPermission('state/admin')){?>
		<li>
          <a href="<?php echo Yii::app()->createUrl('state/admin');?>">
              <i class="fa fa-list"></i> <span> Manage State</span>
           
          </a>
        </li>
        <?php }?>
          <?php if($permission->checkPermission('city/admin')){?>
		<li>
          <a href="<?php echo Yii::app()->createUrl('city/admin');?>">
              <i class="fa fa-list"></i> <span> Manage City</span>
           
          </a>
        </li>
        <?php }?>
         <?php //if($permission->checkPermission('paymentMode/admin')){?>
		<li>
          <a href="<?php echo Yii::app()->createUrl('paymentMode/admin');?>">
              <i class="fa fa-list"></i> <span> Manage Payment Modes</span>
           
          </a>
        </li>
        <?php //}?>
      
         
          </ul>
           </li>
          <?php }?>
         
          <?php if($permission->checkPermission('user/admin') || $permission->checkPermission('userRole/admin')){?>
          		<li class=" treeview">
          <a href="#">
            <i class="fa fa-user"></i> <span>Manage Users</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-right pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
          
            <?php if($permission->checkPermission('user/admin')){?>
        <li>
          <a href="<?php echo Yii::app()->createUrl('user/admin');?>">
              <i class="fa fa-dot-circle-o"></i> <span> Manage User</span>
           
          </a>
        </li>
        <?php }?>
           <?php if($permission->checkPermission('userRole/admin')){?>
         <li>
          <a href="<?php echo Yii::app()->createUrl('userRole/admin');?>">
              <i class="fa fa-dot-circle-o"></i> <span>User Roles</span>
           
          </a>
        </li>
        <?php }?>
           
        
        
          </ul>
           </li>
          <?php }?>
         <?php if($permission->checkSelectedSession() == true){?>
        <?php if($permission->checkPermission('mrsDetail/admin')){?>
         <li>
          <a href="<?php echo Yii::app()->createUrl('mrsDetail/admin');?>">
              <i class="fa fa-list"></i> <span>MRS</span>
           
          </a>
        </li>
        <?php }?>
         <?php if($permission->checkPermission('mrsDetail/pending')){?>
          <li>
          <a href="<?php echo Yii::app()->createUrl('mrsDetail/pending');?>">
              <i class="fa fa-hourglass"></i> <span>Pending MRS</span>
           
          </a>
        </li>
        <?php }?>
        
           <?php if($permission->checkPermission('mrnDetail/index')){?>
            <li>
          <a href="<?php echo Yii::app()->createUrl('mrnDetail/index');?>">
              <i class="fa fa-list-alt"></i> <span>Material Receipt Note</span>
           
          </a>
        </li>
          <?php }else{?>
          <?php if($permission->checkPermission('mrnDetail/admin')){?>
            <li>
          <a href="<?php echo Yii::app()->createUrl('mrnDetail/admin');?>">
              <i class="fa fa-list-alt"></i> <span>Material Receipt Note</span>
           
          </a>
        </li>
          <?php }?>
          <?php }?>
             <?php if($permission->checkPermission('purchaseOrderDetail/index')){?>
            <li>
          <a href="<?php echo Yii::app()->createUrl('purchaseOrderDetail/index');?>">
              <i class="fa fa-list-alt"></i> <span>Purchase Order</span>
           
          </a>
        </li>
          <?php }else{?>
          <?php if($permission->checkPermission('purchaseOrderDetail/admin')){?>
            <li>
          <a href="<?php echo Yii::app()->createUrl('purchaseOrderDetail/admin');?>">
              <i class="fa fa-list-alt"></i> <span>Purchase Order</span>
           
          </a>
        </li>
          <?php }?>
          <?php }?>
          <?php if($permission->checkPermission('purchaseBillDetail/index')){?>
            <li>
          <a href="<?php echo Yii::app()->createUrl('purchaseBillDetail/index');?>">
              <i class="fa fa-list-alt"></i> <span>Goods Received Note</span>
           
          </a>
        </li>
          <?php }else{?>
          <?php if($permission->checkPermission('purchaseBillDetail/admin')){?>
            <li>
          <a href="<?php echo Yii::app()->createUrl('purchaseBillDetail/admin');?>">
              <i class="fa fa-list-alt"></i> <span>Goods Received Note</span>
           
          </a>
        </li>
          <?php }?>
          <?php }?>
          <?php }?>
         <?php if($permission->checkPermission('purchaseBillDetail/index') || $permission->checkPermission('purchaseBillDetail/admin')){?>
           <li>
          <a href="<?php echo Yii::app()->createUrl('purchaseBillDetail/list');?>">
             <i class="fa fa-list-alt"></i> <span>GRN Details</span>
           
          </a>
        </li>
        <?php }?>
           
        <li>
          <a href="<?php echo Yii::app()->createUrl('session/admin');?>">
             <i class="fa fa-list-alt"></i> <span>Session</span>
           
          </a>
        </li>
        <?php /*?>
        <li>
          <a href="<?php echo Yii::app()->createUrl('advancePayment/admin');?>">
             <i class="fa fa-list-alt"></i> <span>Advance Payment</span>
           
          </a>
        </li>*/?>
           <?php //if($permission->checkPermission('item/admin') || $permission->checkPermission('itemCompany/admin')||
          		//$permission->checkPermission('itemCategory/admin')|| $permission->checkPermission('itemdetail/admin')){?>
          		 <?php if($permission->checkPermission('purchaseBillDetail/report')){?>
          		<li class=" treeview">
          <a href="#">
            <i class="fa fa-file-text"></i> <span>Manage Reports</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-right pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
          
            <?php //if($permission->checkPermission('order/admin')){?>
              <li>
          <a href="<?php echo Yii::app()->createUrl('order/userWise');?>">
              <i class="fa fa-dot-circle-o"></i> <span>UserWise Sale Report</span>
           
          </a>
        </li>
             <li>
          <a href="<?php echo Yii::app()->createUrl('order/itemWise');?>">
              <i class="fa fa-dot-circle-o"></i> <span>ItemWise Sale Report</span>
           
          </a>
        </li>
        <li>
          <a href="<?php echo Yii::app()->createUrl('order/deptWise');?>">
              <i class="fa fa-dot-circle-o"></i> <span>Deptwise Sale Report</span>
           
          </a>
        </li>
        <li>
          <a href="<?php echo Yii::app()->createUrl('order/compWise');?>">
              <i class="fa fa-dot-circle-o"></i> <span>Company Wise Sale Report</span>
           
          </a>
        </li>
          <li>
          <a href="<?php echo Yii::app()->createUrl('order/admin');?>">
              <i class="fa fa-dot-circle-o"></i> <span>Orders</span>
           
          </a>
        </li>
        <?php //}?>
           <?php //if($permission->checkPermission('orderItem/admin')){?>
          <li>
          <a href="<?php echo Yii::app()->createUrl('orderItem/admin');?>">
              <i class="fa fa-dot-circle-o"></i> <span>Order Details</span>
           
          </a>
        </li>
           <?php //}?>
            <?php //if($permission->checkPermission('item/admin')){?>
          <li>
          <a href="<?php echo Yii::app()->createUrl('tax/admin');?>">
              <i class="fa fa-dot-circle-o"></i> <span>Taxes</span>
           
          </a>
        </li>
         <li>
          <a href="<?php echo Yii::app()->createUrl('itemTax/admin');?>">
              <i class="fa fa-dot-circle-o"></i> <span>Item Taxes</span>
           
          </a>
        </li>
         <li>
          <a href="<?php echo Yii::app()->createUrl('order/groupTax');?>">
              <i class="fa fa-dot-circle-o"></i> <span>Department group tax wise</span>
           
          </a>
        </li>
         <li>
          <a href="<?php echo Yii::app()->createUrl('order/tax');?>">
              <i class="fa fa-dot-circle-o"></i> <span>Order Taxes</span>
           
          </a>
        </li>
            <li>
          <a href="<?php echo Yii::app()->createUrl('purchaseBillDetail/report');?>">
              <i class="fa fa-dot-circle-o"></i> <span>Tally Report</span>
           
          </a>
        </li>
          <li>
          <a href="<?php echo Yii::app()->createUrl('purchaseBill/admin');?>">
              <i class="fa fa-dot-circle-o"></i> <span>RBI Report</span>
           
          </a>
        </li>
        <li>
          <a href="<?php echo Yii::app()->createUrl('paymentReport/admin');?>">
              <i class="fa fa-dot-circle-o"></i> <span>Payment Report</span>
           
          </a>
        </li>
          <li>
          <a href="<?php echo Yii::app()->createUrl('orderRefundItem/admin');?>">
              <i class="fa fa-dot-circle-o"></i> <span>Refund Report</span>
           
          </a>
        </li>
          <li>
          <a href="<?php echo Yii::app()->createUrl('stockAdjustLog/admin');?>">
              <i class="fa fa-dot-circle-o"></i> <span>Stock Adjust Report</span>
           
          </a>
        </li>
        <li>
          <a href="<?php echo Yii::app()->createUrl('itemExpire/admin');?>">
              <i class="fa fa-dot-circle-o"></i> <span>Item Expire</span>
           
          </a>
        </li>
        <li>
          <a href="<?php echo Yii::app()->createUrl('itemReturn/admin');?>">
              <i class="fa fa-dot-circle-o"></i> <span>Purchase Return Report</span>
           
          </a>
        </li>
        <li>
          <a href="<?php echo Yii::app()->createUrl('customer/admin');?>">
              <i class="fa fa-dot-circle-o"></i> <span>Customers</span>
           
          </a>
        </li>
        <?php //}?>
        </ul>
           </li>
           <?php }?>
              <?php if($permission->checkPermission('creditNote/admin')){?>
            <li>
          <a href="<?php echo Yii::app()->createUrl('creditNote/admin');?>">
              <i class="fa fa-credit-card"></i> <span> Manage Credit Notes</span>
           
          </a>
        </li>
        <?php }?>
         <?php if($permission->checkPermission('permission/admin')){?>
        <li>
          <a href="<?php echo Yii::app()->createUrl('permission/admin');?>">
              <i class="fa fa-briefcase"></i> <span> Manage Permission</span>
           
          </a>
        </li>
        <?php }?>
         <?php if($permission->checkPermission('rolePermission/admin')){?>
        <li>
          <a href="<?php echo Yii::app()->createUrl('rolePermission/admin');?>">
              <i class="fa fa-paper-plane"></i> <span> Manage Role Permission</span>
           
          </a>
        </li>
        <?php }?>
            <?php  if($permission->checkPermission('onlineOrder/admin')){?>
        <li>
          <a href="<?php echo Yii::app()->createUrl('onlineOrder/admin');?>">
              <i class="fa fa-list"></i> <span> Online Orders</span>
           
          </a>
        </li>
        <?php }?>
        <?php if($permission->role_id ==1){?>
         <li>
          <a href="<?php echo Yii::app()->createUrl('backup/default/index');?>">
              <i class="fa fa-download"></i> <span>Backup</span>
           
          </a>
        </li>
        <?php }?>
        <?php }?>
         <?php /*if(!Yii::app()->user->isGuest){?>
        <li class=" treeview">
          <a href="#">
            <i class="fa fa-list"></i> <span>Manage Homes</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-right pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
            <li class="active"><a href="<?php echo Yii::app()->createUrl('home/admin');?>"><i class="fa fa-circle-o"></i>Homes</a></li>
             <li><a href="<?php echo Yii::app()->createUrl('homeCategory/admin');?>"><i class="fa fa-circle-o"></i>Home Categories</a></li>
          </ul>
        </li>
        <?php }*/?>
          <?php /*if(Yii::app()->user->isAdmin){?>
        <li class=" treeview">
          <a href="#">
            <i class="fa fa-user"></i> <span>Manage User</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-right pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
            <li class="active"><a href="<?php echo Yii::app()->createUrl('user/admin/role_id/'.User::ROLE_MERCHANT);?>"><i class="fa fa-circle-o"></i>Merchant</a></li>
            <li><a href="<?php echo Yii::app()->createUrl('user/admin/role_id/'.User::ROLE_USER);?>"><i class="fa fa-circle-o"></i>Customer</a></li>
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
            <?php if(Yii::app()->user->isAdmin){?>
            <li ><a href="<?php echo Yii::app()->createUrl('category/admin');?>"><i class="fa fa-circle-o"></i>Categories</a></li>
             
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
    
    <strong>Copyright &copy; <?php echo date('Y');?> <a href="#"><?php echo CHtml::encode(Yii::app()->params['company'])?></a>.</strong> All rights
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
  <?php /*?>$.widget.bridge('uibutton', $.ui.button);*/ ?>

</script>
<!-- Bootstrap 3.3.6 -->

<!-- Morris.js charts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/raphael/2.1.0/raphael-min.js"></script>
<script src="<?php  echo Yii::app()->theme->baseUrl; ?>/plugins/morris/morris.min.js"></script>
<!-- Sparkline -->
<script src="<?php  echo Yii::app()->theme->baseUrl; ?>/plugins/sparkline/jquery.sparkline.min.js"></script>
<!-- jvectormap -->
<script src="<?php  echo Yii::app()->theme->baseUrl; ?>/plugins/jvectormap/jquery-jvectormap-1.2.2.min.js"></script>
<script src="<?php  echo Yii::app()->theme->baseUrl; ?>/plugins/jvectormap/jquery-jvectormap-world-mill-en.js"></script>
<!-- jQuery Knob Chart -->
<script src="<?php  echo Yii::app()->theme->baseUrl; ?>/plugins/knob/jquery.knob.js"></script>
<!-- daterangepicker -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.11.2/moment.min.js"></script>

<!-- Bootstrap WYSIHTML5 -->
<script src="<?php  echo Yii::app()->theme->baseUrl; ?>/plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.all.min.js"></script>
<!-- Slimscroll -->
<script src="<?php  echo Yii::app()->theme->baseUrl; ?>/plugins/slimScroll/jquery.slimscroll.min.js"></script>
<!-- FastClick -->
<script src="<?php  echo Yii::app()->theme->baseUrl; ?>/plugins/fastclick/fastclick.js"></script>
<!-- AdminLTE App -->
<script src="<?php  echo Yii::app()->theme->baseUrl; ?>/js/app.min.js"></script>
<!-- AdminLTE dashboard demo (This is only for demo purposes) -->
<?php /*?>
<script src="<?php  echo Yii::app()->theme->baseUrl; ?>/js/dashboard.js"></script>*/?>
<!-- AdminLTE for demo purposes -->

<!-- jQuery 2.2.3 -->

</body>
</html>
