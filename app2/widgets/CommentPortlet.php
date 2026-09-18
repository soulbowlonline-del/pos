<?php
namespace app\widgets;

use yii\base\Widget;

/**
 * Port of protected/components/CommentPortlet.php - which renders nothing.
 *
 * 41 view files end by calling this widget, so it has to exist. What it does
 * not have to do is render: the Yii 1 class extends CPortlet and its
 * renderContent() is commented out, so CPortlet draws its (empty) decoration
 * and the comment form in components/views/commentPortlet.php is never
 * reached. The Yii 1 pages emit no portlet markup at all - checked against the
 * running application, not inferred.
 *
 * So this renders nothing too. Reinstating the comment form would be a new
 * feature, not a port.
 */
class CommentPortlet extends Widget
{
    /** @var \yii\db\ActiveRecord the row the comments would belong to. */
    public $model;

    public function run()
    {
        return '';
    }
}
