<?php
$this->breadcrumbs = array(
    'Items' => array('admin'),
    'Import Tax Data',
);

$this->menu = array(
    array('label' => 'Items', 'url' => array('admin')),
    array('label' => 'Batch Update Prices', 'url' => array('batchUpdatePrices')),
);
?>

<h1>Import Tax Data from CSV</h1>

<div class="form">
    
    <?php if (!empty($uploadError)): ?>
        <div class="alert alert-danger">
            <strong>Error:</strong> <?php echo CHtml::encode($uploadError); ?>
        </div>
    <?php endif; ?>
    
    <?php if ($importResults['success'] > 0 || $importResults['failed'] > 0): ?>
        <div class="alert alert-info">
            <h4>Import Results:</h4>
            <p><strong>Successfully imported:</strong> <?php echo $importResults['success']; ?> records</p>
            <p><strong>Failed:</strong> <?php echo $importResults['failed']; ?> records</p>
            
            <?php if (!empty($importResults['errors'])): ?>
                <hr>
                <h5>Errors:</h5>
                <ul>
                    <?php foreach ($importResults['errors'] as $error): ?>
                        <li><?php echo CHtml::encode($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">Upload CSV File</h3>
        </div>
        <div class="panel-body">
            
            <div class="alert alert-info">
                <h4>CSV Format Instructions:</h4>
                <p>Your CSV file should contain the following columns in this order:</p>
                <ol>
                    <li><strong>Title</strong> - Product title/name</li>
                    <li><strong>HSN Code</strong> - HSN/SAC code</li>
                    <li><strong>Tax</strong> - Tax information (e.g., "GST @5%", "IGST @28%")</li>
                </ol>
                <p><strong>Example:</strong></p>
                <pre>Title,HSN Code,Tax
00 PIZZA FLOUR 907GM,19010000,IGST @5%
A CUBE CASHEW NUTS 250GM,8013220,GST @5%
AIR FILTER 2 WHEELER,84212300,GST @28%</pre>
            </div>

            <?php 
            $form = $this->beginWidget('CActiveForm', array(
                'id' => 'csv-upload-form',
                'enableAjaxValidation' => false,
                'htmlOptions' => array(
                    'enctype' => 'multipart/form-data',
                    'class' => 'form-horizontal'
                ),
            )); 
            ?>

            <div class="form-group">
                <label class="col-sm-2 control-label" for="csv_file">CSV File:</label>
                <div class="col-sm-6">
                    <input type="file" name="csv_file" id="csv_file" accept=".csv" class="form-control" required>
                    <p class="help-block">Select a CSV file to upload. Maximum file size: 5MB</p>
                </div>
            </div>

            <div class="form-group">
                <div class="col-sm-offset-2 col-sm-6">
                    <button type="submit" name="submit" value="1" class="btn btn-primary">
                        <i class="fa fa-upload"></i> Import Data
                    </button>
                    <a href="<?php echo $this->createUrl('admin'); ?>" class="btn btn-default">
                        <i class="fa fa-arrow-left"></i> Back to Items
                    </a>
                </div>
            </div>

            <?php $this->endWidget(); ?>
        </div>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">Sample CSV Data</h3>
        </div>
        <div class="panel-body">
            <p>Based on your uploaded image, here's how the data should be formatted:</p>
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>HSN Code</th>
                        <th>Tax</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>00 PIZZA FLOUR 907GM</td>
                        <td>19010000</td>
                        <td>IGST @5%</td>
                    </tr>
                    <tr>
                        <td>A CUBE CASHEW NUTS 250GM</td>
                        <td>8013220</td>
                        <td>GST @5%</td>
                    </tr>
                    <tr>
                        <td>AIR FILTER 2 WHEELER</td>
                        <td>84212300</td>
                        <td>GST @28%</td>
                    </tr>
                    <tr>
                        <td>AIR FILTER ACTIVA</td>
                        <td>84213100</td>
                        <td>GST @28%</td>
                    </tr>
                    <tr>
                        <td>AJAY BLACK SALT 100GM</td>
                        <td>2501</td>
                        <td>GST @0%</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.alert {
    padding: 15px;
    margin-bottom: 20px;
    border: 1px solid transparent;
    border-radius: 4px;
}
.alert-danger {
    color: #a94442;
    background-color: #f2dede;
    border-color: #ebccd1;
}
.alert-info {
    color: #31708f;
    background-color: #d9edf7;
    border-color: #bce8f1;
}
.panel {
    margin-bottom: 20px;
    background-color: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    box-shadow: 0 1px 1px rgba(0,0,0,.05);
}
.panel-heading {
    padding: 10px 15px;
    border-bottom: 1px solid #ddd;
    background-color: #f5f5f5;
}
.panel-body {
    padding: 15px;
}
.panel-title {
    margin-top: 0;
    margin-bottom: 0;
    font-size: 16px;
}
pre {
    background-color: #f5f5f5;
    border: 1px solid #ccc;
    border-radius: 4px;
    padding: 9.5px;
    margin: 0 0 10px;
    font-size: 13px;
    line-height: 1.42857143;
    color: #333;
    word-break: break-all;
    word-wrap: break-word;
}
</style>
