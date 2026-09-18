<?php
namespace app\widgets;

/**
 * Stand-in for bootstrap.widgets.TbEditableColumn.
 *
 * TbEditableColumn renders the cell as an x-editable link: the value as text,
 * with data attributes that let it be edited in place and saved by ajax. The
 * x-editable plugin is not part of this port, so the cell is the value on its
 * own - the same text, not editable.
 *
 * That is a real difference in how the grid is used, and it is listed in
 * docs/web-ui-port.md beside the pickers and the rich-text editors. The
 * comparison suite reads cell text, so it will not flag this; the note is the
 * record.
 */
class EditableColumn extends DataColumn
{
    /** @var array TbEditableColumn's editable configuration, not used here. */
    public $editable = [];
}
