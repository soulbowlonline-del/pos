<?php
/**
 * Ported from protected/views/item/storage.php.
 */

use app\components\Ui;
use app\widgets\ActiveForm;
?>
<script type="text/javascript" src="http://static.fusioncharts.com/code/latest/fusioncharts.js"></script>

<!-- Resources -->
<script src="https://www.amcharts.com/lib/3/amcharts.js"></script>
<script src="https://www.amcharts.com/lib/3/pie.js"></script>
<!-- <script src="https://www.amcharts.com/lib/3/plugins/export/export.min.js"></script>
<link rel="stylesheet" href="https://www.amcharts.com/lib/3/plugins/export/export.css" type="text/css" media="all" /> -->
<script src="https://www.amcharts.com/lib/3/themes/light.js"></script>

<script src="https://www.amcharts.com/lib/3/serial.js"></script>

<script src="https://www.amcharts.com/lib/3/themes/light.js"></script>
<?php    $url = Yii::$app->basePath.'/extensions/FusionCharts.php'; 
            include $url;
            ?>
                     
            
<section class="content"> 

  <!-- Small boxes (Stat box) -->
  
  
  <div class="row">
    <div class="col-lg-2 col-xs-6 pd-7 col-sm-5ths"> 
      <!-- small box -->
      <a href="<?php echo Ui::to('site/performance')?>" class="small-box-footer">
      <div class="small-box bg-aqua">
      <div class="icon"> <!--<i class="ion ion-bag"></i>--> <img alt="" class="image-inline-left" src="http://poc.rscube.com/hwc/wdir/uploads/ic-procurement.png">  </div>
        <div class="inner">
          <h5>Procurement (in M.T)</h5>
          <p>Total Procurement in Rabi2018 :1555463</p>
        </div>       
    </div>
    </a>
    </div>
    
    <!-- ./col -->
    <div class="col-lg-2 col-xs-6 pd-7 col-sm-5ths"> 
      <!-- small box -->
      <a href="<?php echo Ui::to('site/storage')?>" class="small-box-footer">
      <div class="small-box bg-green active">
        <div class="inner">
          <h5>Storage (in M.T)</h5>
          <p>Total Available : 2234084</p>
           <p>Utilized Till Date : 2513152</p>
          
        </div>
        <div class="icon"> <!--<i class="ion ion-stats-bars"></i> --> <img alt="" class="image-inline-left" src="http://poc.rscube.com/hwc/wdir/uploads/ic-storage.png"> </div>
    </div></a>
    </div>
    <!-- ./col -->
    <div class="col-lg-2 col-xs-6 pd-7 col-sm-5ths"> 
      <!-- small box -->
       <a href="<?php echo Ui::to('site/quality')?>" class="small-box-footer">
      <div class="small-box bg-yellow">
        <div class="inner">
          <h5>Quality Control</h5>
          <p>200</p>
        </div>
        <div class="icon"> <!--<i class="ion ion-person-add"></i>--> <img alt="" class="image-inline-left" src="http://poc.rscube.com/hwc/wdir/uploads/ic-quality-control.png">  </div>
      </div></a>
    </div>
    <!-- ./col -->
    <div class="col-lg-2 col-xs-6 pd-7 col-sm-5ths"> 
      <!-- small box -->
      <a href="<?php echo Ui::to('site/physicalVerification')?>" class="small-box-footer">
       <div class="small-box bg-red">
      <div class="icon"> <!--<i class="ion ion-pie-graph"></i>--> <img alt="" class="image-inline-left" src="http://poc.rscube.com/hwc/wdir/uploads/ic-stock-verification.png"> </div>
        <div class="inner">
          <h5>Stock Physical Verification</h5>
          <p>300</p>
        </div>
        
    </div></a>
    </div>
    
  <div class="col-lg-2 col-xs-6 pd-7 col-sm-5ths"> 
      <!-- small box -->
        <a href="<?php echo Ui::to('site/recovery')?>" class="small-box-footer">
       <div class="small-box bg-parklife">
        <div class="inner">
          <h5>Recovery</h5>
          <p>100</p>
        </div>	
        <div class="icon"> <img alt="" class="image-inline-left" src="http://poc.rscube.com/hwc/wdir/uploads/ic-recovery.png"> </div>
     </div>
     </a>
     </div>
    <!-- ./col --> 
  </div>

<div class="row">
	 <section class="content-header">
      <h1>
        Storage
      </h1> 
</section> 
</div>

<div class="row">
<div class="col-md-12">


 <div class="box box-primary">
            <div class="box-header with-border bg-green">
              <h3 class="box-title"><?php echo strtoupper('Storage Capacity (M.T) & Utilization(M.T) Comparision')?></h3>

              <div class="box-tools pull-right">
                <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
                </button>
                <button type="button" class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i></button>
              </div>
            </div>
            <!-- /.box-header -->
            <div class="box-body">
                  <div class="row">
  
  
  <?php $form = ActiveForm::begin([
		'id'=>'procurement-pie-form',
]); ?>


<div class="col-md-4">
<?php

$model->start_date = '30/06/2018';
echo $form->datepickerRow ( $model, 'start_date', [
		'hint' => 'Click inside! to select a date.',
		'prepend' => '<i class="icon-calendar"></i>',
		'options' => [
				'format' => 'dd/mm/yyyy' 
		] 
] );
?>

</div>

<?php ActiveForm::end(); ?>
  </div>
           
           
           <style>
#chartdiv {
  width: 100%;
  height: 400px;
}	

#chartdivnew{
  width: 100%;
  height: 400px;
}												
</style>

<br>

<div class="col-md-6">
<center>Capacity of Current Year</center>
<div id="chartdiv" ></div>

</div>

<div class="col-md-6">
<center>Capacity of Last Year</center>
  <div id="chartdivnew"></div>         
           
           
      </div>     
           
        
          
            </div>
           
           
          </div>

</div>

</div>




<div class="row">

<div class="col-md-12">



 <div class="box box-primary">
            <div class="box-header with-border bg-green">
              <h3 class="box-title"><?php echo strtoupper('Utilization of Warehouse Storage Capacity')?></h3>

              <div class="box-tools pull-right">
                <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
                </button>
                <button type="button" class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i></button>
              </div>
            </div>
            <!-- /.box-header -->
            <div class="box-body">
             <p>
          <b>As On Date:</b> <?php echo '30/6/2018';?></p>
   <?php /*?>        
<div id="container" style="min-width: 310px; height: 400px; margin: 0 auto"></div>  

<?php */?>

<!-- Styles -->
<style>
#chartdiv1 {
  width: 100%;
  height: 500px;
}										
</style>



          
                 

<!-- HTML -->
<div id="chartdiv1"></div>












            </div>
           
           
          </div>





</div>


</div>
 </section>


<!-- Chart code -->

<!-- Chart code -->
<script>
getData();

$('#User_start_date').change(function () {       
	getData();
	
});
function getData(){
var cdate = $('#User_start_date').val();
var result = cdate.split('/');
var month = result[1];
console.log(month);
if(month == '04'){
	var chart = AmCharts.makeChart( "chartdiv", {
		  "type": "pie",
		  "theme": "light",
		  "dataProvider": [ 


			     {
			            "label": "Sirsa",
			            "value": "200933"
			        },
			        {
			            "label": "Panipat",
			            "value": "240762"
			        },
			        {
			            "label": "Rewari",
			            "value": "13882"
			        },
			        {
			            "label": "Rohtak",
			            "value": "321034"
			        },
			    		
			    		
			    		  {
			            "label": "Palwal",
			            "value": "231943"
			        },
			    		  {
			            "label": "Kurukshetra",
			            "value": "137999"
			        },
			    		  {
			            "label": "Kaithal",
			            "value": "243449"
			        },
			    		  {
			            "label": "Ambala",
			            "value": "153586"
			        },
			    		  {
			            "label": "Fatehabad",
			            "value": "328083"
			        }
		],
		  "valueField": "value",
		  "titleField": "label",
		  "outlineAlpha": 1.8,
		  "depth3D": 8,
		  "balloonText": "[[title]]<br><span style='font-size:16px'><b>[[value]]</b> ([[percents]]%)</span>",
		  "angle": 10,
		/*   "export": {
		    "enabled": true
		  } */
		} );

		var newchart = AmCharts.makeChart( "chartdivnew", {
		  "type": "pie",
		  "theme": "light",
		  "dataProvider": [ 


			     {
			            "label": "Sirsa",
			            "value": "118990"
			        },
			        {
			            "label": "Panipat",
			            "value": "216062"
			        },
			        {
			            "label": "Rewari",
			            "value": "93822"
			        },
			        {
			            "label": "Rohtak",
			            "value": "245323"
			        },
			    		
			    		
			    		  {
			            "label": "Palwal",
			            "value": "215970"
			        },
			    		  {
			            "label": "Kurukshetra",
			            "value": "142913"
			        },
			    		  {
			            "label": "Kaithal",
			            "value": "221890"
			        },
			    		  {
			            "label": "Ambala",
			            "value": "149310"
			        },
			    		  {
			            "label": "Fatehabad",
			            "value": "319570"
			        }
		],
		  "valueField": "value",
		  "titleField": "label",
		  "outlineAlpha": 1.8,
		  "depth3D": 8,
		  "balloonText": "[[title]]<br><span style='font-size:16px'><b>[[value]]</b> ([[percents]]%)</span>",
		  "angle": 10,
		/*   "export": {
		    "enabled": true
		  } */
		} );

		var chart = AmCharts.makeChart( "chartdiv1", {
		  "type": "serial",
		  "theme": "serial",
		  "depth3D": 20,
		  "angle": 30,
		  "legend": {
		    "horizontalGap": 10,
		    "useGraphSettings": true,
		    "markerSize": 10
		  },
		  "dataProvider": [ {
		        "checklist": "Sirsa",
		        "capacity2017": 118990,
		        "utilization2017": 87426,
		        "capacity2018": 200933,
		        "utilization2018": 183907
		    }, {
		        "checklist": "Panipat",
		        "capacity2017": 216062,
		        "utilization2017": 208217,
		        "capacity2018": 240762,
		        "utilization2018": 264085
		    }, {
		        "checklist": "Rewari",
		        "capacity2017": 93822,
		        "utilization2017": 90294,
		        "capacity2018": 13882,
		        "utilization2018": 140395
		    }, {
		        "checklist": "Palwal",
		        "capacity2017": 215970,
		        "utilization2017": 237877,
		        "capacity2018": 231943,
		        "utilization2018": 235998
		    }, {
		        "checklist": "Kurukshetra",
		        "capacity2017": 142913,
		        "utilization2017": 162893,
		        "capacity2018": 137999,
		        "utilization2018": 161523
		    }, {
		        "checklist": "Kaithal",
		        "capacity2017": 221890,
		        "utilization2017": 231362,
		        "capacity2018": 243449,
		        "utilization2018": 289565
		    }, {
		        "checklist": "Ambala",
		        "capacity2017": 149310,
		        "utilization2017": 161994,
		        "capacity2018": 153586,
		        "utilization2018": 180112
		    }, {
		        "checklist": "Fatehabad",
		        "capacity2017": 319570,
		        "utilization2017": 312946,
		        "capacity2018": 328083,
		        "utilization2018": 342154
		    }
		],
		  "valueAxes": [ {
		    "stackType": "regular",
		    "axisAlpha": 0,
		    "gridAlpha": 0
		  } ],
		  "graphs": [ {
		    "balloonText": "<b>[[title]]</b><br><span style='font-size:14px'>[[category]]: <b>[[value]]</b></span>",
		    "fillAlphas": 0.8,
		    "labelText": "[[value]]",
		    "lineAlpha": 0.3,
		    "title": "capacity2017",
		    "type": "column",
		    "color": "#000000",
		    "valueField": "capacity2017"
		  } , {
		    "balloonText": "<b>[[title]]</b><br><span style='font-size:14px'>[[category]]: <b>[[value]]</b></span>",
		    "fillAlphas": 0.8,
		    "labelText": "[[value]]",
		    "lineAlpha": 0.3,
		    "title": "utilization2017",
		    "type": "column",
		    "newStack": true,
		    "color": "#000000",
		    "valueField": "utilization2017"
		  }
		  ,{
			    "balloonText": "<b>[[title]]</b><br><span style='font-size:14px'>[[category]]: <b>[[value]]</b></span>",
			    "fillAlphas": 0.8,
			    "labelText": "[[value]]",
			    "lineAlpha": 0.3,
			    "title": "capacity2018",
			    "type": "column",
			    "newStack": true,
			    "color": "#000000",
			    "valueField": "capacity2018"
			  }, {
		    "balloonText": "<b>[[title]]</b><br><span style='font-size:14px'>[[category]]: <b>[[value]]</b></span>",
		    "fillAlphas": 0.8,
		    "labelText": "[[value]]",
		    "lineAlpha": 0.3,
		    "title": "utilization2018",
		    "type": "column",
		    "newStack": true,
		    "color": "#000000",
		    "valueField": "utilization2018"
		  },

		  ],
		  "categoryField": "checklist",
		  "categoryAxis": {
		    "gridPosition": "start",
		    "axisAlpha": 0,
		    "gridAlpha": 0,
		    "position": "left"
		  },
		 

		} );
}else{
var chart = AmCharts.makeChart( "chartdiv", {
  "type": "pie",
  "theme": "light",
  "dataProvider": [ 


	     {
	            "label": "Sirsa",
	            "value": "218557"
	        },
	        {
	            "label": "Panipat",
	            "value": "231962"
	        },
	        {
	            "label": "Rewari",
	            "value": "189863"
	        },
	        {
	            "label": "Rohtak",
	            "value": "372150"
	        },
	    		
	    		
	    		  {
	            "label": "Palwal",
	            "value": "283947"
	        },
	    		  {
	            "label": "Kurukshetra",
	            "value": "135299"
	        },
	    		  {
	            "label": "Kaithal",
	            "value": "242935"
	        },
	    		  {
	            "label": "Ambala",
	            "value": "175364"
	        },
	    		  {
	            "label": "Fatehabad",
	            "value": "384004"
	        }
],
  "valueField": "value",
  "titleField": "label",
  "outlineAlpha": 1.8,
  "depth3D": 8,
  "balloonText": "[[title]]<br><span style='font-size:16px'><b>[[value]]</b> ([[percents]]%)</span>",
  "angle": 10,
/*   "export": {
    "enabled": true
  } */
} );

var newchart = AmCharts.makeChart( "chartdivnew", {
  "type": "pie",
  "theme": "light",
  "dataProvider": [ 


	     {
	            "label": "Sirsa",
	            "value": "119990"
	        },
	        {
	            "label": "Panipat",
	            "value": "216062"
	        },
	        {
	            "label": "Rewari",
	            "value": "94162"
	        },
	        {
	            "label": "Rohtak",
	            "value": "253181"
	        },
	    		
	    		
	    		  {
	            "label": "Palwal",
	            "value": "219912"
	        },
	    		  {
	            "label": "Kurukshetra",
	            "value": "131249"
	        },
	    		  {
	            "label": "Kaithal",
	            "value": "221073"
	        },
	    		  {
	            "label": "Ambala",
	            "value": "155138"
	        },
	    		  {
	            "label": "Fatehabad",
	            "value": "319957"
	        }
],
  "valueField": "value",
  "titleField": "label",
  "outlineAlpha": 1.8,
  "depth3D": 8,
  "balloonText": "[[title]]<br><span style='font-size:16px'><b>[[value]]</b> ([[percents]]%)</span>",
  "angle": 10,
/*   "export": {
    "enabled": true
  } */
} );

var chart = AmCharts.makeChart( "chartdiv1", {
  "type": "serial",
  "theme": "serial",
  "depth3D": 20,
  "angle": 30,
  "legend": {
    "horizontalGap": 10,
    "useGraphSettings": true,
    "markerSize": 10
  },
  "dataProvider": [ {
        "checklist": "Sirsa",
        "capacity2017": 119990,
        "utilization2017": 112885,
        "capacity2018": 218557,
        "utilization2018": 243573
    }, {
        "checklist": "Panipat",
        "capacity2017": 216062,
        "utilization2017": 200358,
        "capacity2018": 231962,
        "utilization2018": 260270
    }, {
        "checklist": "Rewari",
        "capacity2017": 94162,
        "utilization2017": 99616,
        "capacity2018": 189863,
        "utilization2018": 214911
    }, {
        "checklist": "Palwal",
        "capacity2017": 219912,
        "utilization2017": 236869,
        "capacity2018": 283947,
        "utilization2018": 321931
    }, {
        "checklist": "Kurukshetra",
        "capacity2017": 131249,
        "utilization2017": 133796,
        "capacity2018": 135299,
        "utilization2018": 134410
    }, {
        "checklist": "Kaithal",
        "capacity2017": 221073,
        "utilization2017": 232348,
        "capacity2018": 242935,
        "utilization2018": 288986
    }, {
        "checklist": "Ambala",
        "capacity2017": 155138,
        "utilization2017": 171055,
        "capacity2018": 175364,
        "utilization2018": 200950
    }, {
        "checklist": "Fatehabad",
        "capacity2017": 319957,
        "utilization2017": 312876,
        "capacity2018": 384004,
        "utilization2018": 434467
    }
],
  "valueAxes": [ {
    "stackType": "regular",
    "axisAlpha": 0,
    "gridAlpha": 0
  } ],
  "graphs": [ {
    "balloonText": "<b>[[title]]</b><br><span style='font-size:14px'>[[category]]: <b>[[value]]</b></span>",
    "fillAlphas": 0.8,
    "labelText": "[[value]]",
    "lineAlpha": 0.3,
    "title": "capacity2017",
    "type": "column",
    "color": "#000000",
    "valueField": "capacity2017"
  } , {
    "balloonText": "<b>[[title]]</b><br><span style='font-size:14px'>[[category]]: <b>[[value]]</b></span>",
    "fillAlphas": 0.8,
    "labelText": "[[value]]",
    "lineAlpha": 0.3,
    "title": "utilization2017",
    "type": "column",
    "newStack": true,
    "color": "#000000",
    "valueField": "utilization2017"
  }
  ,{
	    "balloonText": "<b>[[title]]</b><br><span style='font-size:14px'>[[category]]: <b>[[value]]</b></span>",
	    "fillAlphas": 0.8,
	    "labelText": "[[value]]",
	    "lineAlpha": 0.3,
	    "title": "capacity2018",
	    "type": "column",
	    "newStack": true,
	    "color": "#000000",
	    "valueField": "capacity2018"
	  }, {
    "balloonText": "<b>[[title]]</b><br><span style='font-size:14px'>[[category]]: <b>[[value]]</b></span>",
    "fillAlphas": 0.8,
    "labelText": "[[value]]",
    "lineAlpha": 0.3,
    "title": "utilization2018",
    "type": "column",
    "newStack": true,
    "color": "#000000",
    "valueField": "utilization2018"
  },

  ],
  "categoryField": "checklist",
  "categoryAxis": {
    "gridPosition": "start",
    "axisAlpha": 0,
    "gridAlpha": 0,
    "position": "left"
  },
 

} );
}
}
</script>




