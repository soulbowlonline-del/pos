<!-- Main content -->

<section class="content"> 
  <!-- Small boxes (Stat box) -->
  <div class="row">
    <div class="col-lg-3 col-xs-6"> 
      <!-- small box -->
      <div class="small-box bg-aqua">
        <div class="inner">
          <h3><?php echo $items;?></h3>
          <p>Total Purchase</p>
        </div>
        <div class="icon"> <i class="ion ion-bag"></i> </div>
        <a href="#" class="small-box-footer">More info <i class="fa fa-arrow-circle-right"></i></a> </div>
    </div>
    
    <!-- ./col -->
    <div class="col-lg-3 col-xs-6"> 
      <!-- small box -->
      <div class="small-box bg-green">
        <div class="inner">
          <h3><?php echo $vendors;?></h3>
          <p>Total Sales</p>
        </div>
        <div class="icon"> <i class="ion ion-stats-bars"></i> </div>
        <a href="#" class="small-box-footer">More info <i class="fa fa-arrow-circle-right"></i></a> </div>
    </div>
    <!-- ./col -->
    <div class="col-lg-3 col-xs-6"> 
      <!-- small box -->
      <div class="small-box bg-yellow">
        <div class="inner">
          <h3><?php echo $orders;?></h3>
          <p>No Vendors</p>
        </div>
        <div class="icon"> <i class="ion ion-person-add"></i> </div>
        <a href="#" class="small-box-footer">More info <i class="fa fa-arrow-circle-right"></i></a> </div>
    </div>
    <!-- ./col -->
    <div class="col-lg-3 col-xs-6"> 
      <!-- small box -->
      <div class="small-box bg-red">
        <div class="inner">
          <h3><?php echo $pendingorders;?></h3>
          <p>MRS Pending</p>
        </div>
        <div class="icon"> <i class="ion ion-pie-graph"></i> </div>
        <a href="#" class="small-box-footer">More info <i class="fa fa-arrow-circle-right"></i></a> </div>
    </div>
    
  
    <!-- ./col --> 
  </div>
 
 
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
   <div class="col-md-4">
   <div class="box box-primary">
            <div class="box-header with-border">
              <h3 class="box-title">Recently Added Products</h3>

              <div class="box-tools pull-right">
                <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
                </button>
                <button type="button" class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i></button>
              </div>
            </div>
            <!-- /.box-header -->
            <div class="box-body">
              <ul class="products-list product-list-in-box">
                <li class="item">
                  <div class="product-img">
                    <img src="dist/img/default-50x50.gif" alt="Product Image">
                  </div>
                  <div class="product-info">
                    <a href="" class="product-title">Samsung TV
                      <span class="label label-warning pull-right">$1800</span></a>
                        <span class="product-description">
                          Samsung 32" 1080p 60Hz LED Smart HDTV.
                        </span>
                  </div>
                </li>
                <!-- /.item -->
                <li class="item">
                  <div class="product-img">
                    <img src="dist/img/default-50x50.gif" alt="Product Image">
                  </div>
                  <div class="product-info">
                    <a href="javascript:void(0)" class="product-title">Bicycle
                      <span class="label label-info pull-right">$700</span></a>
                        <span class="product-description">
                          26" Mongoose Dolomite Men's 7-speed, Navy Blue.
                        </span>
                  </div>
                </li>
                <!-- /.item -->
                <li class="item">
                  <div class="product-img">
                    <img src="dist/img/default-50x50.gif" alt="Product Image">
                  </div>
                  <div class="product-info">
                    <a href="javascript:void(0)" class="product-title">Xbox One <span class="label label-danger pull-right">$350</span></a>
                        <span class="product-description">
                          Xbox One Console Bundle with Halo Master Chief Collection.
                        </span>
                  </div>
                </li>
                <!-- /.item -->
                <li class="item">
                  <div class="product-img">
                    <img src="..themes/img/default-50x50.gif" alt="Product Image">
                  </div>
                  <div class="product-info">
                    <a href="javascript:void(0)" class="product-title">PlayStation 4
                      <span class="label label-success pull-right">$399</span></a>
                        <span class="product-description">
                          PlayStation 4 500GB Console (PS4)
                        </span>
                  </div>
                </li>
                <!-- /.item -->
              </ul>
            </div>
            <!-- /.box-body -->
            <div class="box-footer text-center">
              <a href="javascript:void(0)" class="uppercase">View All Products</a>
            </div>
            <!-- /.box-footer -->
          </div>
   
   </div>
  
  
  	<div class="col-md-8">
    <div class="box">
    
     <?php  $this->widget('bootstrap.widgets.TbGridView', array(
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
		'id',
		'full_name',
		'username',
		'email',
		//'lat',
		//'long',
		'contact_no',
			
/*
 'date_of_birth',
 'about_me:html',
 'address',
 'postal_code',
 'country',
 'city',
 array(
 'name' => 'state',
 'value'=>'$data->getStatusOptions($data->state)',
 'filter'=>User::getStatusOptions(),
 ),
 'lang',
 'image_file',
 'is_passenger',
 'is_dispatcher',
 'is_driver',
 'role_id',
 array(
 'name' => 'state_id',
 'value'=>'$data->getStatusOptions($data->state_id)',
 'filter'=>User::getStatusOptions(),
 ),
 array(
 'name' => 'type_id',
 'value'=>'$data->getTypeOptions($data->type_id)',
 'filter'=>User::getTypeOptions(),
 ),
 'last_visit_time',
 'last_action_time',
 'last_password_change',
 array(
 'name' => 'is_active',
 'value' => '($data->is_active === 0) ? Yii::t(\'app\', \'No\') : Yii::t(\'app\', \'Yes\')',
 'filter' => array('0' => Yii::t('app', 'No'), '1' => Yii::t('app', 'Yes')),
 ),
 'login_error_count',
 */
array(
		'header'=>'Actions',
			'class'=>'FaButtonColumn',
'template' => '{view}{update}{delete}'
	
),
),
)); ?>
    </div>
    
    </div>
  
  </div>
 


</section>
<!-- /.content -->