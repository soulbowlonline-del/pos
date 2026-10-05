/*
 * Yii 1's $.fn.yiiGridView.update(id), for the views that still call it.
 *
 * 98 views call it after an ajax save to refresh a grid. Yii 2's
 * yii.gridView.js has no such function, so the call threw "Cannot read
 * properties of undefined" and every line after it was skipped. On
 * item/expireStock the stock was taken off and the line marked expired, but
 * the "Data is saved successfully" alert and the reload after it never came:
 * Save looked as if it did nothing.
 *
 * As Yii 1's: fetch the current page and put the grid of that id from it in
 * place of this one; an id not on the page (expireStock asks for
 * 'menu-grid', which it does not have) does nothing. Yii 2 binds a grid's
 * filters on document, so the replaced grid keeps them.
 *
 * Loaded after endBody(), so after yii.gridView.js, which would otherwise
 * replace $.fn.yiiGridView and drop this.
 */
(function ($) {
    if (!$ || !$.fn) {
        return;
    }
    if (!$.fn.yiiGridView) {
        $.fn.yiiGridView = function () { return this; };
    }
    if ($.fn.yiiGridView.update) {
        return;
    }
    $.fn.yiiGridView.update = function (id, options) {
        var $grid = $('#' + id);
        if (!$grid.length) {
            return;
        }
        $.ajax($.extend({
            url: window.location.href,
            type: 'GET',
            cache: false,
            success: function (html) {
                var $fresh = $('<div>').append($.parseHTML(html)).find('#' + id);
                if ($fresh.length) {
                    $grid.replaceWith($fresh);
                }
            }
        }, options || {}));
    };
})(window.jQuery);
