<?php
/**
 * Why the Claude features are off, if they are. The rule-based insights work regardless.
 *
 * @var string|null $reason
 */
use yii\helpers\Html;

if ($reason !== null) { ?>
<div class="ai-warn"><i class="fa fa-info-circle"></i> <?php echo Html::encode($reason); ?></div>
<?php }
