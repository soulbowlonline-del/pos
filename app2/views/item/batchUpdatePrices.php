<?php
/**
 * Ported from protected/views/item/batchUpdatePrices.php.
 */

use app\components\Ui;
?>
<?php
$this->params['breadcrumbs'] = [
    'Items' => ['admin'],
    'Batch Price Update',
];

$this->context->menu = [
    ['label' => 'Items', 'url' => ['admin']],
    ['label' => 'Import Tax Data', 'url' => ['importTaxData']],
];
?>

<h1>Batch Price Update System</h1>

<div class="row">
    <div class="col-md-8">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">Update Progress</h3>
            </div>
            <div class="panel-body">
                <div id="progress-container">
                    <div class="progress">
                        <div id="progress-bar" class="progress-bar progress-bar-striped" role="progressbar" style="width: 0%">
                            0%
                        </div>
                    </div>
                    <div id="status-info" class="mt-3">
                        <p><strong>Status:</strong> <span id="status-text">Ready to start</span></p>
                        <p><strong>Total Items:</strong> <span id="total-items">-</span></p>
                        <p><strong>Updated Items:</strong> <span id="updated-items">-</span></p>
                        <p><strong>Pending Items:</strong> <span id="pending-items">-</span></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">Update Controls</h3>
            </div>
            <div class="panel-body">
                <div class="form-horizontal">
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Batch Size:</label>
                        <div class="col-sm-9">
                            <select id="batch-size" class="form-control" style="width: 200px;">
                                <option value="25">25 items per batch</option>
                                <option value="50" selected>50 items per batch</option>
                                <option value="100">100 items per batch</option>
                                <option value="200">200 items per batch</option>
                            </select>
                            <p class="help-block">Smaller batches are safer but slower. Larger batches are faster but may cause memory issues.</p>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="col-sm-offset-3 col-sm-9">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" id="dry-run"> Preview mode (don't save changes)
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-sm-offset-3 col-sm-9">
                            <button id="start-update" class="btn btn-primary" type="button">
                                <i class="fa fa-play"></i> Start Update
                            </button>
                            <button id="stop-update" class="btn btn-danger" type="button" style="display: none;">
                                <i class="fa fa-stop"></i> Stop Update
                            </button>
                            <button id="refresh-status" class="btn btn-info" type="button">
                                <i class="fa fa-refresh"></i> Refresh Status
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">Sample Updates</h3>
            </div>
            <div class="panel-body">
                <div id="sample-updates">
                    <p class="text-muted">Run update to see sample changes...</p>
                </div>
            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">Update Log</h3>
            </div>
            <div class="panel-body">
                <div id="update-log" style="height: 300px; overflow-y: auto; font-family: monospace; font-size: 12px;">
                    <p class="text-muted">Update log will appear here...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let updateInterval = null;
let isUpdating = false;

$(document).ready(function() {
    // Load initial status
    refreshStatus();

    $('#start-update').click(function() {
        startBatchUpdate();
    });

    $('#stop-update').click(function() {
        stopBatchUpdate();
    });

    $('#refresh-status').click(function() {
        refreshStatus();
    });
});

function startBatchUpdate() {
    if (isUpdating) return;
    
    isUpdating = true;
    $('#start-update').hide();
    $('#stop-update').show();
    $('#status-text').text('Starting batch update...');
    
    logMessage('Starting batch update process...');
    
    updateInterval = setInterval(function() {
        processBatch();
    }, 2000); // Process every 2 seconds
    
    // Process first batch immediately
    processBatch();
}

function stopBatchUpdate() {
    if (updateInterval) {
        clearInterval(updateInterval);
        updateInterval = null;
    }
    
    isUpdating = false;
    $('#start-update').show();
    $('#stop-update').hide();
    $('#status-text').text('Update stopped by user');
    
    logMessage('Batch update stopped by user.');
    refreshStatus();
}

function processBatch() {
    const batchSize = $('#batch-size').val();
    const dryRun = $('#dry-run').is(':checked');
    
    $.ajax({
        url: '<?php echo Ui::to('batchUpdatePrices'); ?>',
        type: 'GET',
        data: {
            batch_size: batchSize,
            dry_run: dryRun ? 1 : 0
        },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                updateProgress(response);
                displaySampleUpdates(response.sample_updates);
                
                logMessage(`Batch completed: ${response.processed} processed, ${response.updated} updated, ${response.errors} errors`);
                
                if (response.remaining === 0 || !response.continue) {
                    // All done
                    stopBatchUpdate();
                    $('#status-text').text('Update completed successfully!');
                    logMessage('All items have been processed successfully!');
                }
            } else {
                // Error occurred
                stopBatchUpdate();
                $('#status-text').text('Error: ' + response.message);
                logMessage('Error: ' + response.message);
                
                if (response.error_details) {
                    logMessage('Details: ' + response.error_details);
                }
            }
        },
        error: function(xhr, status, error) {
            stopBatchUpdate();
            $('#status-text').text('Network error occurred');
            logMessage('Network error: ' + error);
        }
    });
}

function refreshStatus() {
    $.ajax({
        url: '<?php echo Ui::to('checkUpdateStatus'); ?>',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            $('#total-items').text(response.total_items);
            $('#updated-items').text(response.updated_items);
            $('#pending-items').text(response.pending_items);
            
            const progress = response.progress_percentage;
            $('#progress-bar').css('width', progress + '%');
            $('#progress-bar').text(progress + '%');
            
            if (progress >= 100) {
                $('#progress-bar').removeClass('progress-bar-striped').addClass('progress-bar-success');
            }
            
            if (!isUpdating) {
                if (response.pending_items === 0) {
                    $('#status-text').text('All items updated');
                } else {
                    $('#status-text').text(`Ready to start (${response.pending_items} items pending)`);
                }
            }
        }
    });
}

function updateProgress(response) {
    refreshStatus(); // This will update the actual progress from database
}

function displaySampleUpdates(samples) {
    if (!samples || samples.length === 0) {
        $('#sample-updates').html('<p class="text-muted">No sample updates available</p>');
        return;
    }
    
    let html = '<div class="list-group">';
    samples.forEach(function(item) {
        let taxType = 'N/A';
        if (item.tax_info && item.tax_info.tax_type !== undefined) {
            taxType = item.tax_info.tax_type == 0 ? 'GST (CGST+SGST)' : 'IGST';
        }
        
        html += `
            <div class="list-group-item">
                <h6 class="list-group-item-heading">${item.title}</h6>
                <p class="list-group-item-text small">
                    <strong>HSN:</strong> ${item.hsn_code}<br>
                    <strong>MRP:</strong> ${item.old_mrp} → ${item.new_mrp}<br>
                    <strong>Sale Price:</strong> ${item.old_sale_price} → ${item.new_sale_price}<br>
                    <strong>Tax:</strong> ${item.tax_info.tax_percentage || 'N/A'}% (${taxType})
                </p>
            </div>
        `;
    });
    html += '</div>';
    
    $('#sample-updates').html(html);
}

function logMessage(message) {
    const timestamp = new Date().toLocaleTimeString();
    const logEntry = `[${timestamp}] ${message}\n`;
    $('#update-log').append(logEntry);
    $('#update-log').scrollTop($('#update-log')[0].scrollHeight);
}
</script>

<style>
.progress {
    height: 25px;
    margin-bottom: 20px;
}

.list-group-item {
    padding: 8px 12px;
}

.list-group-item-heading {
    margin-bottom: 5px;
    font-size: 14px;
}

.list-group-item-text {
    margin-bottom: 0;
}

#update-log {
    background-color: #f8f8f8;
    border: 1px solid #ddd;
    padding: 10px;
    white-space: pre-wrap;
}

.panel {
    margin-bottom: 20px;
}

.mt-3 {
    margin-top: 1rem;
}
</style>
