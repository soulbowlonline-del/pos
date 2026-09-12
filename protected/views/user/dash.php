<!-- Main content -->

<section class="content"> 
  <!-- Small boxes (Stat box) -->
  <div class="row">
    <div class="col-lg-3 col-xs-6"> 
      <!-- small box -->
      <div class="small-box bg-aqua">
        <div class="inner">
          <h3><?php echo $items;?></h3>
          <p>Total Items</p>
        </div>
        <div class="icon"> <i class="ion ion-bag"></i> </div>
        <a href="<?php echo Yii::app()->createUrl('item/admin');?>" class="small-box-footer">More info <i class="fa fa-arrow-circle-right"></i></a> </div>
    </div>
    
    <!-- ./col -->
    <div class="col-lg-3 col-xs-6"> 
      <!-- small box -->
      <div class="small-box bg-green">
        <div class="inner">
          <h3><?php echo $mrss;?></h3>
          <p>Total MRS</p>
        </div>
        <div class="icon"> <i class="ion ion-stats-bars"></i> </div>
        <a href="<?php echo Yii::app()->createUrl('mrsDetail/admin');?>" class="small-box-footer">More info <i class="fa fa-arrow-circle-right"></i></a> </div>
    </div>
    <!-- ./col -->
    <div class="col-lg-3 col-xs-6"> 
      <!-- small box -->
      <div class="small-box bg-yellow">
        <div class="inner">
          <h3><?php echo $pos;?></h3>
          <p>Total PO</p>
        </div>
        <div class="icon"> <i class="ion ion-person-add"></i> </div>
        <a href="<?php echo Yii::app()->createUrl('purchaseOrderDetail/index');?>" class="small-box-footer">More info <i class="fa fa-arrow-circle-right"></i></a> </div>
    </div>
    <!-- ./col -->
    <div class="col-lg-3 col-xs-6"> 
      <!-- small box -->
      <div class="small-box bg-red">
        <div class="inner">
          <h3><?php echo $grns;?></h3>
          <p>Total GRN</p>
        </div>
        <div class="icon"> <i class="ion ion-pie-graph"></i> </div>
        <a href="<?php echo Yii::app()->createUrl('purchaseBillDetail/index');?>" class="small-box-footer">More info <i class="fa fa-arrow-circle-right"></i></a> </div>
    </div>
    
  
    <!-- ./col --> 
  </div>
 
 <?php /*?>
      <div class="row">
        <div class="col-md-12">
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
                    <span class="progress-text">MRS Created</span>
                    <span class="progress-number"><b>160</b>/200</span>

                    <div class="progress sm">
                      <div class="progress-bar progress-bar-aqua" style="width: 80%"></div>
                    </div>
                  </div>
                  <!-- /.progress-group -->
                  <div class="progress-group">
                    <span class="progress-text">MRN Created</span>
                    <span class="progress-number"><b>310</b>/400</span>

                    <div class="progress sm">
                      <div class="progress-bar progress-bar-red" style="width: 80%"></div>
                    </div>
                  </div>
                  <!-- /.progress-group -->
                  <div class="progress-group">
                    <span class="progress-text">Number of Purchase Order</span>
                    <span class="progress-number"><b>480</b>/800</span>

                    <div class="progress sm">
                      <div class="progress-bar progress-bar-green" style="width: 80%"></div>
                    </div>
                  </div>
                  <!-- /.progress-group -->
                  <div class="progress-group">
                    <span class="progress-text">Due Bills</span>
                    <span class="progress-number"><b>250</b>/500</span>

                    <div class="progress sm">
                      <div class="progress-bar progress-bar-yellow" style="width: 80%"></div>
                    </div>
                  </div>
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
                    <span class="description-percentage text-green"><i class="fa fa-caret-up"></i> 17%</span>
                    <h5 class="description-header">$35,210.43</h5>
                    <span class="description-text">TOTAL REVENUE</span>
                  </div>
                  <!-- /.description-block -->
                </div>
                <!-- /.col -->
                <div class="col-sm-3 col-xs-6">
                  <div class="description-block border-right">
                    <span class="description-percentage text-yellow"><i class="fa fa-caret-left"></i> 0%</span>
                    <h5 class="description-header">$10,390.90</h5>
                    <span class="description-text">TOTAL COST</span>
                  </div>
                  <!-- /.description-block -->
                </div>
                <!-- /.col -->
                <div class="col-sm-3 col-xs-6">
                  <div class="description-block border-right">
                    <span class="description-percentage text-green"><i class="fa fa-caret-up"></i> 20%</span>
                    <h5 class="description-header">$24,813.53</h5>
                    <span class="description-text">TOTAL PROFIT</span>
                  </div>
                  <!-- /.description-block -->
                </div>
                <!-- /.col -->
                <div class="col-sm-3 col-xs-6">
                  <div class="description-block">
                    <span class="description-percentage text-red"><i class="fa fa-caret-down"></i> 18%</span>
                    <h5 class="description-header">1200</h5>
                    <span class="description-text">GOAL COMPLETIONS</span>
                  </div>
                  <!-- /.description-block -->
                </div>
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
              <h3 class="box-title">Recently Sales Notification</h3>

              <div class="box-tools pull-right">
                <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
                </button>
                <button type="button" class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i></button>
              </div>
            </div>
            <!-- /.box-header -->
            <div class="box-body">
              <ul class="products-list product-list-in-box">
              <?php $notifications = Notification::model()->findAllByAttributes(array('to_id'=>Yii::app()->user->id));
              if($notifications){
              foreach($notifications as $notification){
              	?>
              	 <li class="item">
                  <div class="product-img">
                    <img src="<?php echo Yii::app()->theme->baseUrl; ?>/img/default-50x50.gif" alt="Product Image">
                  </div>
                  <div class="product-info">
                    <a href="" class="product-title"><?php echo $notification->getTypeOptions($notification->type_id);?>
<!--                       <span class="label label-warning pull-right">$1800</span></a> --></a>
                        <span class="product-description">
                          <?php echo $notification->description;?>
                        </span>
                  </div>
                </li>
              	<?php 
              }
              }?>
               
               
                
             
                <!-- /.item -->
              </ul>
            </div>
            <!-- /.box-body -->
            <div class="box-footer text-center">
              <a href="<?php echo Yii::app()->createUrl('item/admin')?>" class="uppercase">View All Products</a>
            </div>
            <!-- /.box-footer -->
          </div>
   
   </div>
  
  
  	<div class="col-md-9 col-sm-9 col-xs-12">
    <div class="box">
    
    <div class="box-header with-border">
           <!--    <h3 class="box-title">Users</h3> -->
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
    
    
     <?php  /* $this->widget('bootstrap.widgets.TbGridView', array(
	//$this->widget('zii.widgets.grid.CGridView', array(

'id' => 'user-grid',
	'type'=>'striped bordered condensed',
	'dataProvider' => $model->search(),
	'filter' => $model,
	'pager' => array(
	'class' => 'CLinkPager',
'htmlOptions' => array('class' => 'pager'
),
),
	'columns' => array(
		
		'full_name',
		//'username',
		'email',
		//'lat',
		//'long',
		'contact_no',

array(
		'header'=>'Actions',
			'class' => 'CButtonColumn',
'template' => '{view}{update}{delete}'
	
),
),
)); 

</div>
    </div>
    
    </div>
  
  </div>
  */?>

<!-- AdminLTE dashboard demo (This is only for demo purposes) -->
<?php /*?>
<script src="<?php  echo Yii::app()->theme->baseUrl; ?>/js/dashboard2.js"></script>*/?>
</section>
<!-- /.content -->