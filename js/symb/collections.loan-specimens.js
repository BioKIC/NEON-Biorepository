/* #start neon edits */
/* The specimen tab is loaded by jQuery UI; keep state local to each tab load. */
(function ($) {
	'use strict';
	const root = $('#outloanspecdiv');
	const tableElement = root.find('#loanSpecimenTable')[0];
	const table = $(tableElement).DataTable({
		pageLength: 50,
		lengthMenu: [10, 25, 50, 100],
		order: [[1, 'asc']],
		columnDefs: [{ orderable: false, targets: [0] }],
		language: { search: 'Search specimens:', emptyTable: 'No specimens in this loan.' }
	});
	function resolveIdentifiers() {
		const identifiers = Array.from(new Set(root.find('#loan-identifiers').val().split(/[\r\n,;\t]+/).map(function (value) { return value.trim(); }).filter(Boolean)));
		const target = root.find('#loan-target').val();
		const sampleType = root.find('#loan-sample-type').val();
		const ids = [], problems = [];
		identifiers.forEach(function (identifier) {
			const matches = table.rows().nodes().toArray().filter(function (row) {
				if (sampleType && row.dataset.sampleType !== sampleType) return false;
				let otherIdentifiers = [];
				try { otherIdentifiers = JSON.parse(row.dataset.otherIdentifiers || '[]'); }
				catch (error) { otherIdentifiers = []; }
				const catalogMatch = row.dataset.catalog.trim() === identifier;
				const otherMatch = otherIdentifiers.indexOf(identifier) !== -1;
				if (target === 'catnum') return catalogMatch;
				if (target === 'other') return otherMatch;
				return catalogMatch || otherMatch;
			});
			if (matches.length === 1) ids.push(matches[0].dataset.occid);
			else problems.push(identifier + (matches.length ? ' (ambiguous)' : ' (not in this loan or sample type)'));
		});
		root.find('#loan-match-result').text(problems.length ? 'Correct these specimen identifiers before continuing: ' + problems.join(', ') : !ids.length ? 'Enter at least one specimen identifier.' : '');
		return problems.length ? [] : ids;
	}
	root.find('#loan-specimen-form').on('submit', function (event) {
		const ids = resolveIdentifiers();
		if (!ids.length) { event.preventDefault(); return; }
		const submitter = event.originalEvent && event.originalEvent.submitter;
		const removing = this.dataset.action === 'remove';
		delete this.dataset.action;
		if (!submitter && !removing) { event.preventDefault(); return; }
		if (removing && !window.confirm('Remove ' + ids.length + ' specimens matching the entered identifiers from this loan? Specimen records will be kept.')) { event.preventDefault(); return; }
		const inputs = root.find('#loan-action-inputs').empty();
		if (removing) $('<input>', {type: 'hidden', name: 'formsubmit', value: 'deleteSpecimens'}).appendTo(inputs);
		ids.forEach(function (id) { $('<input>', {type: 'hidden', name: 'occid[]', value: id}).appendTo(inputs); });
	});
	const scanQueue = [];
	let scanBusy = false;
	function scanResult(identifier, message) {
		$('<li>').text(identifier + ': ' + message).prependTo(root.find('#loan-scan-history'));
		root.find('#loan-scan-history li').slice(20).remove();
	}
	function processScanQueue() {
		if (scanBusy || !scanQueue.length) return;
		scanBusy = true;
		root.find('#loan-scan-refresh').prop('disabled', true);
		const job = scanQueue.shift();
		$.ajax({method: 'POST', url: 'rpc/processLoanSpecimens.php', dataType: 'text', data: job})
			.done(function (result) {
				const code = result.trim();
				const messagesByMode = {
					link: {'0': 'No matching specimen.', '1': 'Added to loan.', '2': 'Multiple matches; not added.', '3': 'Not added; may already be in this loan.'},
					checkin: {'0': 'No matching specimen.', '1': 'Marked returned.', '2': 'Multiple specimens marked returned.', '3': 'No update; already returned, not in this loan, or an error occurred.'},
					remove: {'0': 'No matching specimen.', '1': 'Removed from loan.', '2': 'Multiple matches in this loan; not removed.', '3': 'Not removed; specimen is not in this loan or an error occurred.'}
				};
				const messages = messagesByMode[job.processmode] || {};
				scanResult(job.catalognumber, messages[code] || 'Unexpected response. Check the loan before rescanning.');
			}).fail(function () { scanResult(job.catalognumber, 'Request failed; check the loan before rescanning.'); })
			.always(function () {
				scanBusy = false;
				root.find('#loan-scan-refresh').prop('disabled', scanQueue.length > 0);
				processScanQueue();
			});
	}
	root.find('#loan-input-mode').on('change', function () {
		const scanning = this.value === 'scan';
		const action = root.find('#loan-process-mode');
		const scanRestrictedActions = action.find('option[value="determination"]');
		if (scanning && action.val() === 'determination') action.val('link');
		scanRestrictedActions.prop('disabled', scanning);
		root.find('#loan-scan-panel').stop(true, true)[scanning ? 'slideDown' : 'slideUp'](180);
		root.find('#loan-identifiers').prop('required', !scanning).toggle(!scanning);
		root.find('label[for="loan-identifiers"], #loan-process-button').toggle(!scanning);
		action.trigger('change');
		if (scanning) root.find('#loan-scan-input').trigger('focus');
	});
	root.find('#loan-scan-input').on('keydown', function (event) {
		if (event.key !== 'Enter') return;
		event.preventDefault();
		const identifier = this.value.trim();
		if (!identifier) return;
		this.value = '';
		const mode = root.find('#loan-process-mode').val();
		if (mode === 'determination') {
			scanResult(identifier, 'Add determination is not available while scanning.');
			return;
		}
		const form = root.find('#loan-entry-form')[0];
		scanQueue.push({catalognumber: identifier, processmode: mode, target: root.find('#loan-target').val(), sampletype: root.find('#loan-sample-type').val(), collid: form.elements.collid.value, loanid: form.elements.loanid.value});
		processScanQueue();
	});
	root.find('#loan-scan-refresh').on('click', function () {
		if (scanBusy || scanQueue.length) return;
		const form = root.find('#loan-entry-form')[0];
		window.location.href = 'outgoing.php?tabindex=1&collid=' + encodeURIComponent(form.elements.collid.value) + '&loanid=' + encodeURIComponent(form.elements.loanid.value);
	});
	root.find('#loan-entry-form').on('submit', function (event) {
		const mode = root.find('#loan-process-mode').val();
		const scanning = root.find('#loan-input-mode').val() === 'scan';
		if (mode === 'remove' || mode === 'determination') {
			event.preventDefault();
			if (!resolveIdentifiers().length) return;
			if (mode === 'determination') {
				root.find('#newdet-div').stop(true, true).slideDown(window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 180);
				root.find('#dafsciname').trigger('focus');
			} else {
				const form = root.find('#loan-specimen-form')[0];
				form.dataset.action = 'remove';
				form.requestSubmit();
			}
		} else if (scanning) event.preventDefault();
	});
	root.find('#loan-process-mode').on('change', function () {
		const inputMode = root.find('#loan-input-mode');
		const scanning = inputMode.val() === 'scan';
		if (this.value === 'determination' && scanning) {
			inputMode.val('paste').trigger('change');
			return;
		}
		inputMode.find('option[value="scan"]').prop('disabled', this.value === 'determination');
		root.find('#loan-target, #loan-sample-type').prop('disabled', false);
		root.find('#loan-identifiers').prop('required', !scanning).toggle(!scanning);
		root.find('label[for="loan-identifiers"]').toggle(!scanning);
		root.find('#loan-process-button').toggle(!scanning).text({link: 'Add to loan', checkin: 'Mark as returned', remove: 'Remove from loan', determination: 'Continue to determination'}[this.value]);

		if (this.value !== 'determination') root.find('#newdet-div').hide();
	});
	root.find('button').not('[hidden]').addClass('MuiButtonBase-root MuiButton-root MuiButton-contained MuiButton-sizeSmall MuiButton-colorPrimary');
	$('#tabs').off('tabsactivate.loanSpecimens').on('tabsactivate.loanSpecimens', function () { table.columns.adjust(); });
})(jQuery);
/* #end neon edits */

