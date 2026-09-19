<?php
/**
 * Ported from protected/views/user/dashboard.php.
 */

use app\components\Gx;
use app\components\Ui;
use app\models\Mrn;
use app\models\Mrs;
use app\models\Notification;
use app\models\Order;
use app\models\PurchaseOrder;
use app\models\Session;
use app\models\Setting;
use app\models\User;
use app\models\UserRole;
use app\widgets\ActionColumn;
use app\widgets\ActiveForm;
use app\widgets\Button;
use app\widgets\GridView;
?>
<!-- Main content -->

<section class="content"> 
  <!-- Small boxes (Stat box) -->
  <div class="row">
    <div class="col-lg-3 col-xs-6"> 
      <!-- small box -->
      <div class="small-box ">
        <div class="inner">
          <h3><?php echo $items;?></h3>
          <p>Total Items</p>
        </div>
        <div class="icon bg-aqua"> <i class="ion ion-bag"></i> </div>
        <a href="<?php echo Ui::to('item/admin');?>" class="small-box-footer bg-aqua">More info <i class="fa fa-arrow-circle-right"></i></a> </div>
    </div>
    
    <!-- ./col -->
    <div class="col-lg-3 col-xs-6"> 
      <!-- small box -->
      <div class="small-box">
        <div class="inner">
          <h3><?php echo $vendors;?></h3>
          <p>Total Vendors</p>
        </div>
        <div class="icon bg-green"> <i class="ion ion-stats-bars"></i> </div>
        <a href="<?php echo Ui::to('vendor/admin');?>" class="small-box-footer bg-green">More info <i class="fa fa-arrow-circle-right"></i></a> </div>
    </div>
    <!-- ./col -->
    <div class="col-lg-3 col-xs-6"> 
      <!-- small box -->
      <div class="small-box">
        <div class="inner">
          <h3><?php echo $orders;?></h3>
          <p>Total Orders</p>
        </div>
        <div class="icon bg-yellow"> <i class="ion ion-person-add"></i> </div>
        <a href="#" class="small-box-footer bg-yellow">More info <i class="fa fa-arrow-circle-right"></i></a> </div>
    </div>
    <!-- ./col -->
    <div class="col-lg-3 col-xs-6"> 
      <!-- small box -->
      <div class="small-box">
        <div class="inner">
          <h3><?php echo $pendingorders;?></h3>
          <p>Pending  Orders</p>
        </div>
        <div class="icon bg-red"> <i class="ion ion-pie-graph"></i> </div>
        <a href="#" class="small-box-footer bg-red">More info <i class="fa fa-arrow-circle-right"></i></a> </div>
    </div>
    
  
    <!-- ./col --> 
  </div>
 
 
      <div class="row">
        <div class="col-md-12">
        <?php if(Yii::$app->session['select_session_id'] != ''){
        	$model->session_id = Yii::$app->session['select_session_id'];
        }?>
        <?php $form = ActiveForm::begin([
			'id' => 'user-form',
		'type' => 'horizontal',
		'action'=> Ui::to('user/setSession'),
		'enableClientValidation'=>true,	
		'clientOptions'=>[
		'validateOnSubmit'=>true
],
//'enableAjaxValidation' => true,
			'htmlOptions'=>['enctype'=>'multipart/form-data'],
]);
?>
<div class="col-md-3">
<?php 
echo $form->dropDownListRow($model, 'session_id',
		Gx::listData(Session::class),['class' => 'form-control']); ?>
		
</div>
<div class="col-md-3">
<?php $id = 1;
			$setting = Setting::findOne($id);
			if($setting){
				$model->api_key = $setting->api_key;
				$model->ivr_username = $setting->ivr_username;
				
			}
			?>
			
<?php echo $form->textFieldRow($model,'api_key',['class'=>'form-control']); ?>
</div>
<div class="col-md-3">
<?php echo $form->textFieldRow($model,'ivr_username',['class'=>'form-control']); ?>
</div>
<div class="col-md-3">
<?php echo Button::widget([
				'buttonType'=>'submit',
				'type'=>'primary',
				'label'=>'Save',
				'htmlOptions'=>['class'=>'btn  btn-orange'],
]); ?>
</div>


<?php ActiveForm::end(); ?>

<div class="clearfix"></div>
          <div class="box">
            <div class="box-header with-border">
              <h3 class="box-title">Monthly Sales Report</h3>

              <div class="box-tools pull-right">
                <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
                </button>
                <div class="btn-group">
                  <button type="button" class="btn btn-box-tool dropdown-toggle" data-toggle="dropdown">
                    <i class="fa fa-wrench"></i></button>
                  <ul class="dropdown-menu" role="menu">
                    <li><a href="#">Action</a></li>
                    <li><a href="#">Another action</a></li>
                    <li><a href="#">Something else here</a></li>
                    <li class="divider"></li>
                    <li><a href="#">Separated link</a></li>
                  </ul>
                </div>
                <button type="button" class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i></button>
              </div>
            </div>
            <!-- /.box-header -->
            <div class="box-body">
              <div class="row">
                <div class="col-md-8">
                  <p class="text-center">
                    <strong>Sales: 1 Jan, 2017 - 30 Jul, 2017</strong>
                  </p>

                  <div class="chart">
                    <!-- Sales Chart Canvas -->
                    <canvas id="salesChart" style="height: 180px;"></canvas>
                  </div>
                  <!-- /.chart-responsive -->
                </div>
                <!-- /.col -->
                <div class="col-md-4">
                  <p class="text-center">
                    <strong>Sales Completion</strong>
                  </p>

                  <div class="progress-group">
                  <?php $mrscreated = Mrs::model()->count();
                  $mrsapproved = Mrs::model()->countByAttributes(['status'=>Mrs::STATUS_DONE]);
                  ?>
                    <span class="progress-text">MRS Approved</span>
                    <span class="progress-number"><b><?php echo $mrsapproved;?></b>/<?php echo $mrscreated;?></span>

                    <div class="progress sm">
                      <div class="progress-bar progress-bar-aqua" style="width: 80%"></div>
                    </div>
                  </div>
                  <!-- /.progress-group -->
                  <div class="progress-group">
                     <?php $mrncreated = Mrn::model()->count();
                  $mrnapproved = Mrn::model()->countByAttributes(['status'=>Mrn::STATUS_APPROVED]);
                  ?>
                    <span class="progress-text">MRN Approved</span>
                    <span class="progress-number"><b><?php echo $mrnapproved;?></b>/<?php echo $mrncreated;?></span>

                    <div class="progress sm">
                      <div class="progress-bar progress-bar-red" style="width: 80%"></div>
                    </div>
                  </div>
                  <!-- /.progress-group -->
                  <div class="progress-group">
                     <?php $pocreated = PurchaseOrder::model()->count();
                  $poapproved = PurchaseOrder::model()->countByAttributes(['status'=>PurchaseOrder::STATUS_APPROVED]);
                  ?>
                    <span class="progress-text">Purchase Order Approved</span>
                    <span class="progress-number"><b><?php echo $poapproved;?></b>/<?php echo $pocreated;?></span>

                    <div class="progress sm">
                      <div class="progress-bar progress-bar-green" style="width: 80%"></div>
                    </div>
                  </div>
                  <!-- /.progress-group -->
                  <?php /*?>
                  <div class="progress-group">
                    <span class="progress-text">Due Bills</span>
                    <span class="progress-number"><b>250</b>/500</span>

                    <div class="progress sm">
                      <div class="progress-bar progress-bar-yellow" style="width: 80%"></div>
                    </div>
                  </div>*/?>
                  <!-- /.progress-group -->
                </div>
                <!-- /.col -->
              </div>
              <!-- /.row -->
            </div>
            <!-- ./box-body -->
            <div class="box-footer">
              <div class="row">
                <div class="col-sm-3 col-xs-6">
                  <div class="description-block border-right">
                  
                    <h5 class="description-header">Rs 0</h5>
                    <span class="description-text">TOTAL REVENUE</span>
                  </div>
                  <!-- /.description-block -->
                </div>
                <!-- /.col -->
                <div class="col-sm-3 col-xs-6">
                  <div class="description-block border-right">
                  
                    <h5 class="description-header">Rs 0</h5>
                    <span class="description-text">TOTAL COST</span>
                  </div>
                  <!-- /.description-block -->
                </div>
                <!-- /.col -->
                <div class="col-sm-3 col-xs-6">
                  <div class="description-block border-right">
                  
                    <h5 class="description-header">Rs 0</h5>
                    <span class="description-text">TOTAL PROFIT</span>
                  </div>
                  <!-- /.description-block -->
                </div>
                <!-- /.col -->
               
              </div>
              <!-- /.row -->
            </div>
            <!-- /.box-footer -->
          </div>
          <!-- /.box -->
        </div>
        <!-- /.col -->
      </div>
      <!-- /.row -->
  
  <div class="row">  
   <div class="col-md-3 col-sm-3 col-xs-12">
   <div class="box box-primary">
            <div class="box-header with-border">
              <h3 class="box-title">Recent Notification</h3>

              <div class="box-tools pull-right">
                <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
                </button>
                <button type="button" class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i></button>
              </div>
            </div>
            <!-- /.box-header -->
            <div class="box-body">
            
                 <?php 
             $user = Yii::$app->user->model;
           
             $role = UserRole::find()->where(['title'=>'Vendor'])->one();
             if($role->id == $user->role_id ){
             	$query = Notification::find();
             	$query->orderBy(['id' => SORT_DESC]);
             	$query->limit(20);
             	$query->andWhere('to_id ='.Yii::$app->user->id);
             	$notifications = $query->all();
             	
             }else{
             	$query_2 = Notification::find();
             	$query_2->orderBy(['id' => SORT_DESC]);
             	$query_2->limit(20);
             	$notifications = $query_2->all();
             
}?>
            <?php if($notifications){?>
              <ul class="products-list product-list-in-box" id="notification_data">
              <?php 
              foreach($notifications as $notification){?>
              <?php 
              $link = '#';
              $type = $notification->model_type;
              if($type == Notification::TYPE_MRS){
              	if($user->checkPermission('mrsDetail/admin')){
              	$link = Ui::to('mrsDetail/admin');
              	}
              }
              if($type == Notification::TYPE_MRN){
              	if($user->checkPermission('mrnDetail/admin')){
              		$link = Ui::to('mrnDetail/admin');
              	}
              }
              if($type == Notification::TYPE_PO){
              	if($user->checkPermission('purchaseOrderDetail/admin')){
              		$link = Ui::to('purchaseOrderDetail/admin');
              	}
              }
              if($type == Notification::TYPE_PBILL){
              	if($user->checkPermission('purchaseBillDetail/admin')){
              		$link = Ui::to('purchaseBillDetail/admin');
              	}else if($user->checkPermission('purchaseBillDetail/index')){
              		$link = Ui::to('purchaseBillDetail/index');
              	}
              }
              ?>
              <li class="item">
               <a href="<?php echo $link;?>">
                  <div class="product-img">
                    <img src="<?php echo '/themes/bar'; ?>/img/default-50x50.gif" alt="Product Image">
                  </div>
                  <div class="product-info">
<!--                     <a href="" class="product-title">Samsung TV 
                      <span class="label label-warning pull-right">$1800</span> </a> -->
                        <span class="product-description">
                       <?php echo $notification->description;?> 
                      <?php if($user){ 
                      if($role->id != $user->role_id ){?>
                      for <?php $vendoruser = User::findOne($notification->to_id);
                      if($vendoruser)
                      echo $vendoruser->full_name;?>
                      <?php }}?>
                        </span>
                  </div>
                  </a>
                </li>
                 
                  <?php }?>
              
                
             
                <!-- /.item -->
              </ul>
              <?php }?>
            </div>
            <!-- /.box-body -->
            <div class="box-footer text-center">
              <a href="<?php echo Ui::to('item/admin');?>" class="uppercase">View All Products</a>
            </div>
            <!-- /.box-footer -->
          </div>
   
   </div>
  
  
  	<div class="col-md-9 col-sm-9 col-xs-12">
    <div class="box">
    
    <div class="box-header with-border">
              <h3 class="box-title">Users</h3>
              <div class="box-tools pull-right">
                <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
                </button>
                <div class="btn-group">
                  <button type="button" class="btn btn-box-tool dropdown-toggle" data-toggle="dropdown">
                    <i class="fa fa-wrench"></i></button>
                  <ul class="dropdown-menu" role="menu">
                    <li><a href="#">Action</a></li>
                    <li><a href="#">Another action</a></li>
                    <li><a href="#">Something else here</a></li>
                    <li class="divider"></li>
                    <li><a href="#">Separated link</a></li>
                  </ul>
                </div>
                <button type="button" class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i></button>
              </div>
            </div>
            
            <div class="box-body table-responsive">
    
    
     <?php  echo GridView::widget([
	//echo GridView::widget(array(

'id' => 'user-grid',
	'type'=>'striped bordered condensed',
	'dataProvider' => $model->search(),
	'filter' => $model,
	'pager' => [
	'class' => \app\widgets\LinkPager::class,
'htmlOptions' => ['class' => 'pager'
],
],
	'columns' => [
		
		'full_name',
		//'username',
		'email',
		//'lat',
		//'long',
		'contact_no',

[
		'header'=>'Actions',
			'class' => ActionColumn::class,
'template' => '{view}{update}'
	
],
],
]); ?>

</div>
    </div>
    
    </div>
  
  </div>

<!-- AdminLTE dashboard demo (This is only for demo purposes) -->
<?php /* ?>
<script src="<?php  echo '/themes/bar'; ?>/js/dashboard2.js"></script>*/?>
<script src="<?php  echo '/themes/bar'; ?>/plugins/chartjs/Chart.min.js"></script>
</section>

<script>
$(function () {

	  var salesChartCanvas = $("#salesChart").get(0).getContext("2d");
	  // This will get the first returned node in the jQuery collection.
	  var salesChart = new Chart(salesChartCanvas);
	  
      var chartdata = <?php echo '['.Order::getOrderRecord().']';?>;
      console.log(chartdata);
	  var salesChartData = {
	    labels: ["January", "February", "March", "April", "May", "June", "July","August","September","October","November","December"],
	    datasets: [
	      /* {
	        label: "Electronics",
	        fillColor: "rgb(210, 214, 222)",
	        strokeColor: "rgb(210, 214, 222)",
	        pointColor: "rgb(210, 214, 222)",
	        pointStrokeColor: "#c1c7d1",
	        pointHighlightFill: "#fff",
	        pointHighlightStroke: "rgb(220,220,220)",
	        data: [65, 59, 80, 81, 56, 55, 40]
	      }, */
	      {
	        label: "Digital Goods",
	        fillColor: "rgba(60,141,188,0.9)",
	        strokeColor: "rgba(60,141,188,0.8)",
	        pointColor: "#3b8bba",
	        pointStrokeColor: "rgba(60,141,188,1)",
	        pointHighlightFill: "#fff",
	        pointHighlightStroke: "rgba(60,141,188,1)",
	        data: chartdata
	      }
	    ]
	  };

	  var salesChartOptions = {
	    //Boolean - If we should show the scale at all
	    showScale: true,
	    //Boolean - Whether grid lines are shown across the chart
	    scaleShowGridLines: false,
	    //String - Colour of the grid lines
	    scaleGridLineColor: "rgba(0,0,0,.05)",
	    //Number - Width of the grid lines
	    scaleGridLineWidth: 1,
	    //Boolean - Whether to show horizontal lines (except X axis)
	    scaleShowHorizontalLines: true,
	    //Boolean - Whether to show vertical lines (except Y axis)
	    scaleShowVerticalLines: true,
	    //Boolean - Whether the line is curved between points
	    bezierCurve: true,
	    //Number - Tension of the bezier curve between points
	    bezierCurveTension: 0.3,
	    //Boolean - Whether to show a dot for each point
	    pointDot: false,
	    //Number - Radius of each point dot in pixels
	    pointDotRadius: 4,
	    //Number - Pixel width of point dot stroke
	    pointDotStrokeWidth: 1,
	    //Number - amount extra to add to the radius to cater for hit detection outside the drawn point
	    pointHitDetectionRadius: 20,
	    //Boolean - Whether to show a stroke for datasets
	    datasetStroke: true,
	    //Number - Pixel width of dataset stroke
	    datasetStrokeWidth: 2,
	    //Boolean - Whether to fill the dataset with a color
	    datasetFill: true,
	    //String - A legend template
	    legendTemplate: "<ul class=\"<%=name.toLowerCase()%>-legend\"><% for (var i=0; i<datasets.length; i++){%><li><span style=\"background-color:<%=datasets[i].lineColor%>\"></span><%=datasets[i].label%></li><%}%></ul>",
	    //Boolean - whether to maintain the starting aspect ratio or not when responsive, if set to false, will take up entire container
	    maintainAspectRatio: true,
	    //Boolean - whether to make the chart responsive to window resizing
	    responsive: true
	  };

	  //Create the line chart
	  salesChart.Line(salesChartData, salesChartOptions);

	});
$(function(){
    $('#notification_data').slimScroll({
        height: '350px'
    });
});
</script>
<!-- /.content -->