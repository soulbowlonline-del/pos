/*
 * "Read a vendor bill" on the GRN screen (purchaseBillDetail/index).
 *
 * Draws a button under Merge. A photo or PDF of the vendor's bill is sent to
 * /v2/ai/bill-grn, which reads it with Claude and answers with which bill
 * line belongs to which line of the grid (GrnBillFill.php). This script then
 * does what a storekeeper does with the paper bill: it types the received
 * qty, MRP, rate and discount % into the grid's own inputs and fires their
 * change events. Every amount, tax and total is worked out by the screen's
 * own gridcalculation(); nothing is calculated here and nothing is saved -
 * the storekeeper checks the grid and presses Update, as always.
 *
 * Everything is drawn from javascript on purpose: the page's own markup is
 * compared with Yii 1's by the page sweeps and must not change.
 */
(function ($) {
	'use strict';

	var cfg = window.DASPOS_GRN_BILL;
	if (!cfg || !$) {
		return;
	}
	var $merge = $('.content-header a.export-btn').first();
	if (!$merge.length) {
		return;
	}

	function esc(s) {
		return $('<div>').text(s === null || s === undefined ? '' : String(s)).html();
	}
	function money(v) {
		return v === null || v === undefined || v === '' || isNaN(v) ? '' : Number(v).toFixed(2);
	}
	function qty(v) {
		return v === null || v === undefined || v === '' || isNaN(v) ? '' : String(parseFloat(Number(v).toFixed(3)));
	}
	function num(s) {
		var n = parseFloat(s);
		return isNaN(n) ? 0 : n;
	}
	function positive(v) {
		return v !== null && v !== undefined && v > 0 ? v : null;
	}

	// ---------------------------------------------------------------- panel

	var $panel = $(
		'<div class="ai-grn-bill">'
		+ '<input type="file" class="ai-grn-file" accept="application/pdf,image/jpeg,image/png,image/webp,image/gif" style="display:none">'
		+ '<button type="button" class="btn btn-info ai-grn-read"><i class="fa fa-magic"></i> Read a vendor bill</button> '
		+ '<button type="button" class="btn btn-default ai-grn-again" style="display:none"></button> '
		+ '<span class="ai-thinking ai-grn-wait" style="display:none"><i class="fa fa-spinner fa-spin"></i> Reading the bill - this takes up to a minute.</span>'
		+ '<span class="ai-grn-hint"></span>'
		+ '</div>');
	var $result = $('<div class="ai-grn-result" style="display:none"></div>');
	$merge.after($panel);
	$('section.content').first().prepend($result);

	var $file = $panel.find('.ai-grn-file');
	var $read = $panel.find('.ai-grn-read');
	var $again = $panel.find('.ai-grn-again');
	var $wait = $panel.find('.ai-grn-wait');
	var $hint = $panel.find('.ai-grn-hint');

	if (cfg.reason) {
		$read.prop('disabled', true);
		$hint.text(cfg.reason);
	} else if (!cfg.poid) {
		$read.prop('disabled', true);
		$hint.text('Choose the vendor and the purchase bill number first.');
	} else {
		$hint.text('Upload the PDF or a photo of the bill: the grid below is filled from it. Nothing is saved until you press Update.');
	}
	if (cfg.last && cfg.poid) {
		$again.html('<i class="fa fa-repeat"></i> Fill again from ' + esc(cfg.last.file) + ' (read at ' + esc(cfg.last.time) + ', no new reading)').show();
	}

	function busy(on) {
		$read.prop('disabled', on);
		$again.prop('disabled', on);
		$wait.toggle(on);
		if (on) {
			$hint.hide();
		}
	}
	function fail(message) {
		$result.html('<div class="ai-error">' + esc(message) + '</div>').show();
	}
	function done(data) {
		busy(false);
		if (!data || !data.ok) {
			fail(data && data.error ? data.error : 'The bill could not be read. Try again.');
			return;
		}
		var applied = apply(data);
		render(data, applied);
	}
	function failed(xhr, status) {
		busy(false);
		fail(status === 'timeout' ? 'Reading took too long. Try again, or upload the bill one page at a time.'
			: (status === 'parsererror' ? 'The server did not answer as expected - you may have been signed out. Reload the page and try again.'
			: (xhr.status === 403 ? 'You are not allowed to read bills, or the page is stale - reload it and try again.'
			: (xhr.status === 413 ? 'The file is too large to upload.'
			: 'The bill could not be read (the server answered ' + xhr.status + '). Try again.'))));
	}

	$read.on('click', function () {
		$file.val('');
		$file.trigger('click');
	});
	$file.on('change', function () {
		if (!this.files || !this.files.length) {
			return;
		}
		var body = new FormData();
		body.append(cfg.csrfParam, cfg.csrfToken);
		body.append('poid', cfg.poid);
		body.append('bill', this.files[0]);
		busy(true);
		$result.hide();
		$.ajax({url: cfg.readUrl, type: 'POST', data: body, processData: false, contentType: false, dataType: 'json', timeout: 280000})
			.done(done).fail(failed);
	});
	$again.on('click', function () {
		busy(true);
		$.ajax({url: cfg.lastUrl, type: 'GET', dataType: 'json', cache: false, timeout: 60000}).done(done).fail(failed);
	});

	// ----------------------------------------------------------------- fill

	/**
	 * Puts v into the input unless it already holds that number. True when
	 * the input changed; the old value stays in its title.
	 */
	function put($el, v, decimals, cls) {
		if (v === null || v === undefined || !$el.length) {
			return false;
		}
		var now = $el.val();
		var next = Number(v).toFixed(decimals);
		if (now !== '' && !isNaN(parseFloat(now)) && parseFloat(now).toFixed(decimals) === next) {
			return false;
		}
		$el.val(next).addClass(cls || 'ai-filled').attr('title', 'Filled from the bill; it was ' + (now === '' ? 'empty' : now));
		return true;
	}

	/** Types the bill into the grid, as a storekeeper would, and lets the screen calculate. */
	function apply(plan) {
		var stats = {rows: 0, cells: 0, amountGaps: [], absent: []};

		// gridcalculation() ends by asking the server to redraw the tax table,
		// once per change. Filling fifty lines would ask two hundred times,
		// with the answers arriving in any order; ask once, at the end.
		var taxTable = window.checkTaxTable;
		var lastId = null;
		if (typeof taxTable === 'function') {
			window.checkTaxTable = function () {};
		}
		try {
			$.each(plan.fills, function (_, f) {
				var id = f.detail_id;
				var $qty = $('#approve_input_qty' + id);
				if (!$qty.length) {
					stats.absent.push(f);
					return;
				}
				var touched = 0;
				// Each change event runs the screen's own handler: the MRP's
				// sets the sale rate, the price's rounds to paise, the
				// discount's works the discount amount out of the %, and every
				// one recalculates the line. A qty, MRP or rate of zero is a
				// value the bill did not print, not one to type in.
				if (put($qty, positive(f.qty), 3)) {
					$qty.trigger('change');
					touched++;
				}
				if (put($('#mrp_input' + id), positive(f.mrp), 2, 'ai-filled ai-filled-mrp')) {
					$('#mrp_input' + id).trigger('change');
					touched++;
				}
				if (put($('#price_input' + id), positive(f.rate), 2)) {
					$('#price_input' + id).trigger('change');
					touched++;
				}
				if (put($('#discount_input' + id), f.discount, 2)) {
					$('#discount_input' + id).trigger('change');
					touched++;
				}
				if (f.free_detail_id && put($('#approve_input_qty' + f.free_detail_id), positive(f.free_qty), 3)) {
					$('#approve_input_qty' + f.free_detail_id).trigger('change');
					touched++;
				}
				if (touched) {
					stats.rows++;
					stats.cells += touched;
					lastId = id;
				}
			});
		} finally {
			if (typeof taxTable === 'function') {
				window.checkTaxTable = taxTable;
				if (lastId !== null) {
					taxTable($('#select_tax' + lastId).val(), lastId);
				}
			}
		}

		// Does each line now come to what the bill shows for it? Bills print
		// either the taxable value or the total with tax; either will do.
		$.each(plan.fills, function (_, f) {
			var id = f.detail_id;
			var $total = $('#total_amount_input' + id);
			if (f.amount === null || !$total.length) {
				return;
			}
			var taxable = num($('#approve_input_qty' + id).val()) * num($('#price_input' + id).val())
				- num($('#discount_amt_input' + id).val()) - num($('#discount_amt1_input' + id).val());
			var total = num($total.val());
			var slack = Math.max(1, 0.01 * Math.abs(f.amount));
			if (Math.abs(f.amount - taxable) > slack && Math.abs(f.amount - total) > slack) {
				$total.addClass('ai-mismatch').attr('title', 'The bill shows ' + money(f.amount) + ' for this line');
				stats.amountGaps.push({f: f, taxable: taxable, total: total});
			}
		});

		var b = plan.bill;
		if (b.bill_no && $('#PurchaseBillDetail_bill_no').val() !== b.bill_no) {
			var $no = $('#PurchaseBillDetail_bill_no');
			$no.attr('title', 'Filled from the bill; it was ' + ($no.val() === '' ? 'empty' : $no.val())).val(b.bill_no).addClass('ai-filled');
			stats.cells++;
		}
		if (b.bill_date && $('#PurchaseBillDetail_bill_date').val() !== b.bill_date) {
			var $date = $('#PurchaseBillDetail_bill_date');
			$date.attr('title', 'Filled from the bill; it was ' + $date.val()).val(b.bill_date).addClass('ai-filled');
			try { $date.datepicker('update', b.bill_date); } catch (e) { /* no datepicker on this input */ }
			stats.cells++;
		}
		if (stats.cells) {
			// What the screen sets when a cell is typed in: Add Item then asks for Update first.
			$('#grid_changed').val('1');
		}
		return stats;
	}

	// --------------------------------------------------------------- result

	function flags(list) {
		return $.map(list || [], function (f) {
			return '<span class="ai-flag ' + esc(f[0]) + '">' + esc(f[1]) + '</span>';
		}).join(' ');
	}
	function billCells(l) {
		return '<td>' + l.n + '</td><td>' + esc(l.description) + '</td>'
			+ '<td class="ai-nowrap">' + qty(l.qty) + ' ' + esc(l.unit) + (l.free_qty ? ' + ' + qty(l.free_qty) + ' free' : '') + '</td>'
			+ '<td>' + money(positive(l.rate)) + '</td><td>' + money(positive(l.mrp)) + '</td><td>' + qty(l.discount) + '</td><td>' + money(l.amount) + '</td>';
	}
	var billHead = '<th>#</th><th>On the bill</th><th>Qty</th><th>Rate</th><th>MRP</th><th>Disc %</th><th>Amount</th>';

	function section(title, note, head, rows, open) {
		if (!rows.length) {
			return '';
		}
		return '<details class="ai-grn-section"' + (open ? ' open' : '') + '><summary>' + esc(title) + ' <span class="ai-count">' + rows.length + '</span></summary>'
			+ (note ? '<p class="ai-sub">' + note + '</p>' : '')
			+ '<div class="ai-scroll"><table class="table table-bordered table-condensed ai-table"><thead><tr>' + head + '</tr></thead><tbody>'
			+ rows.join('') + '</tbody></table></div></details>';
	}

	function render(plan, stats) {
		var b = plan.bill;
		var html = '<div class="box box-info ai-grn-box"><div class="box-body">';

		html += '<div class="ai-bill-head">'
			+ '<div><span>File</span>' + esc(plan.file) + '</div>'
			+ '<div><span>Vendor on bill</span>' + esc(b.vendor_name) + '</div>'
			+ '<div><span>Bill no. / date</span>' + esc(b.bill_no) + (b.bill_date || b.bill_date_printed ? ' / ' + esc(b.bill_date || b.bill_date_printed) : '') + '</div>'
			+ '<div><span>Bill total / GST</span>' + money(b.bill_total) + (b.tax_total !== null ? ' / ' + money(b.tax_total) : '') + '</div>'
			+ '<div><span>Read by</span>' + esc(plan.model) + '</div>'
			+ '</div>';

		var filled = plan.fills.length - stats.absent.length;
		html += '<div class="ai-note"><i class="fa fa-magic"></i> <b>' + filled + ' of ' + plan.line_count + ' bill lines</b> are in the grid'
			+ (stats.cells ? ' - ' + stats.cells + ' value' + (stats.cells === 1 ? '' : 's') + ' changed, shown in blue (a changed MRP in yellow).'
				: ' - the grid already held the bill\'s values, nothing was changed.')
			+ ' <b>Nothing is saved yet.</b> Check the grid against the bill, then press Update.</div>';

		if (plan.retry) {
			html += '<div class="ai-note"><i class="fa fa-refresh"></i> ' + esc(plan.retry) + '</div>';
		}
		$.each(plan.flags, function (_, f) {
			html += '<div class="ai-warn">' + esc(f) + '</div>';
		});

		// The grid's own Bill Amount against the bill's total.
		var grid = num($('#bill_amount').val());
		if (b.bill_total !== null) {
			var gap = Math.abs(grid - b.bill_total);
			if (gap <= Math.max(1, 0.01 * b.bill_total)) {
				html += '<div class="ai-note ai-ok"><i class="fa fa-check"></i> The grid\'s Bill Amount, ' + money(grid) + ', agrees with the bill total, ' + money(b.bill_total) + '.</div>';
			} else {
				html += '<div class="ai-warn">The grid\'s Bill Amount is ' + money(grid) + ' and the bill total is ' + money(b.bill_total) + ' - ' + money(gap) + ' apart.'
					+ (plan.missing.length || plan.unmatched.length ? ' Some bill lines are not in the grid yet (below).' : '')
					+ (plan.untouched.length ? ' Some grid lines are not on the bill (below).' : '') + '</div>';
			}
		}
		if (stats.amountGaps.length) {
			html += '<div class="ai-warn">' + stats.amountGaps.length + ' line' + (stats.amountGaps.length === 1 ? ' does' : 's do')
				+ ' not come to the amount the bill shows (Amt cell in red): '
				+ $.map(stats.amountGaps, function (g) {
					return '<a href="#" class="ai-grn-goto" data-id="' + g.f.detail_id + '">' + esc(g.f.item) + '</a> - bill ' + money(g.f.amount)
						+ ', grid ' + money(g.taxable) + ' before tax, ' + money(g.total) + ' with tax';
				}).join('; ') + '. Check the qty, rate, discount or a rate printed with tax.</div>';
		}

		html += section('Filled into the grid', 'Click a line to go to it in the grid.',
			billHead + '<th>Line of this GRN</th><th>Checks</th>',
			$.map(plan.fills, function (f) {
				return '<tr class="ai-grn-goto" data-id="' + f.detail_id + '">' + billCells(f)
					+ '<td>' + esc(f.item) + '<div class="ai-alt">' + esc(f.barcode) + ' &middot; by ' + esc(f.how) + '</div></td>'
					+ '<td>' + ($.inArray(f, stats.absent) === -1 ? '' : '<span class="ai-flag danger">This line is not in the grid any more - reload the page and fill again</span> ')
					+ flags(f.flags) + '</td></tr>';
			}), stats.absent.length > 0);

		html += section('On the bill, not on this GRN',
			'These items are in the item master but this GRN has no line for them. Press Update first, so what was filled is kept; then use '
			+ '<b>Put in Add Item</b>, check the line and press Add Item.',
			billHead + '<th>Item in DASPOS</th><th>Checks</th><th></th>',
			$.map(plan.missing, function (l, i) {
				return '<tr>' + billCells(l)
					+ '<td>' + esc(l.item) + '<div class="ai-alt">' + esc(l.barcode) + ' &middot; by ' + esc(l.how) + '</div></td>'
					+ '<td>' + flags(l.flags) + '</td>'
					+ '<td><button type="button" class="btn btn-default btn-xs ai-grn-add" data-i="' + i + '">Put in Add Item</button></td></tr>';
			}), true);

		html += section('On the bill, not found in the item master',
			'Find or create the item, then add the line with Add Item as usual.',
			billHead + '<th>Closest items</th>',
			$.map(plan.unmatched, function (l) {
				return '<tr>' + billCells(l) + '<td>' + $.map(l.alternatives, function (a) {
					return '<div class="ai-alt">' + esc(a.title) + ' (' + esc(a.barcode) + ')</div>';
				}).join('') + '</td></tr>';
			}), true);

		html += section('On this GRN, not on the bill', 'Left as they were. If they did not arrive, set their App Qty yourself.',
			'<th>Item</th><th>Bar Code</th><th>Ordered</th><th>App Qty</th>',
			$.map(plan.untouched, function (r) {
				return '<tr class="ai-grn-goto" data-id="' + r.detail_id + '"><td>' + esc(r.item) + '</td><td>' + esc(r.barcode) + '</td><td>'
					+ qty(r.req_qty) + '</td><td>' + qty(r.approved_qty) + '</td></tr>';
			}), true);

		html += '<p class="ai-grn-actions">'
			+ '<a class="btn btn-default btn-sm" href="' + esc(cfg.csvUrl) + '"><i class="fa fa-download"></i> The reading as CSV</a> '
			+ (stats.cells ? '<button type="button" class="btn btn-default btn-sm ai-grn-undo"><i class="fa fa-undo"></i> Undo the fill</button> ' : '')
			+ '<span class="ai-sub">The reading can be wrong where the print is unclear - the paper bill decides.</span></p>';
		html += '</div></div>';

		$result.html(html).show();
		$result.data('plan', plan);
	}

	// Go to a grid line and flash it.
	$result.on('click', '.ai-grn-goto', function (e) {
		if ($(e.target).closest('button').length) {
			return;
		}
		e.preventDefault();
		var $cell = $('#approve_input_qty' + $(this).data('id'));
		if (!$cell.length) {
			return;
		}
		var $row = $cell.closest('tr');
		$('html, body').animate({scrollTop: Math.max(0, $row.offset().top - 140)}, 200);
		$row.addClass('ai-grn-flash');
		setTimeout(function () { $row.removeClass('ai-grn-flash'); }, 2500);
	});

	// Nothing was saved, so reloading is the undo - but it also drops anything typed by hand since the last Update.
	$result.on('click', '.ai-grn-undo', function () {
		if (window.confirm('Reload the page and drop everything not yet saved with Update?')) {
			window.location.reload();
		}
	});

	/*
	 * A bill line with no line on the GRN: put its barcode into the Add Item
	 * form. The screen then does what it does when a barcode is scanned -
	 * checks the item is active and the vendor's, and fills the tax, MRP and
	 * rate from the item master. When that has answered, the bill's qty, MRP,
	 * rate and discount are typed over it through the form's own handlers.
	 * The storekeeper presses Add Item.
	 */
	$result.on('click', '.ai-grn-add', function () {
		var plan = $result.data('plan');
		var l = plan && plan.missing[$(this).data('i')];
		if (!l) {
			return;
		}
		if ($('#grid_changed').val() !== '0') {
			window.alert('Press Update first, so the values filled into the grid are saved. Then use "Fill again" and add this line.');
			return;
		}
		// Only the answer to this barcode: a lookup that fails, or a line
		// asked for earlier, must not type into a later scan.
		$(document).off('.aiGrnAdd').on('ajaxComplete.aiGrnAdd', function (event, xhr, settings) {
			if (!settings || String(settings.url).indexOf('ajaxPBillTax') === -1) {
				return;
			}
			$(document).off('.aiGrnAdd');
			var data = xhr.responseJSON;
			if (!data || data.msg !== 'success') {
				return; // the screen has said why, and reloads
			}
			var fields = [['#PurchaseBillDetail_approved_qty', positive(l.qty), 3], ['#PurchaseBillDetail_mrp', positive(l.mrp), 2],
				['#PurchaseBillDetail_price', positive(l.rate), 2], ['#PurchaseBillDetail_discount', l.discount, 2]];
			$.each(fields, function (_, x) {
				if (x[1] !== null) {
					$(x[0]).val(Number(x[1]).toFixed(x[2])).addClass('ai-filled').trigger('change');
				}
			});
		});
		var $barcode = $('#PurchaseBillDetail_bar_code');
		$('html, body').animate({scrollTop: Math.max(0, $barcode.offset().top - 160)}, 200);
		$barcode.val(l.barcode).trigger('change');
	});
})(window.jQuery);
