/* AI SEO Host Plugin — Admin JS
   Author: Aliyan Faisal
*/
(function ($) {
	'use strict';

	function showNotice(message, type) {
		var $notice = $('#aiscph-notice');
		$notice
			.removeClass('notice-success notice-error')
			.addClass('notice-' + type)
			.text(message)
			.hide()
			.slideDown(200);
		setTimeout(function () { $notice.slideUp(300); }, 4000);
	}

	// Save Settings
	$('#aiscph-settings-form').on('submit', function (e) {
		e.preventDefault();

		var $btn     = $('#aiscph-save-btn');
		var $text    = $btn.find('.btn-text');
		var $spinner = $btn.find('.btn-spinner');

		$text.hide();
		$spinner.show();
		$btn.prop('disabled', true);

		// Use FormData to safely handle large payloads
		var formData = new FormData(this);
		formData.append('action', 'aiscph_save_settings');
		formData.append('nonce', AISCPH.nonce);

		// Handle unchecked checkboxes
		$('#aiscph-settings-form input[type="checkbox"]').each(function () {
			if (!$(this).is(':checked')) {
				formData.set($(this).attr('name'), '0');
			}
		});

		$.ajax({
			url:         AISCPH.ajax_url,
			type:        'POST',
			data:        formData,
			processData: false,
			contentType: false,
			success: function (res) {
				$text.show();
				$spinner.hide();
				$btn.prop('disabled', false);
				if (res.success) {
					showNotice(res.data.message || AISCPH.strings.saved, 'success');
				} else {
					showNotice(res.data.message || AISCPH.strings.error, 'error');
				}
			},
			error: function () {
				$text.show();
				$spinner.hide();
				$btn.prop('disabled', false);
				showNotice(AISCPH.strings.error, 'error');
			}
		});
	});

	// Toggle API key visibility
	$('.aiscph-toggle-key-visibility').on('click', function () {
		var targetId = $(this).data('target');
		var $input   = $('#' + targetId);
		var isPass   = $input.attr('type') === 'password';
		$input.attr('type', isPass ? 'text' : 'password');
		$(this).find('.eye-show').text(isPass ? '🔒' : '👁');
	});

})(jQuery);

	// Clear Log
	$('#aiscph-clear-log-btn').on('click', function () {
		if (!confirm('Clear all log entries? This cannot be undone.')) return;
		var $btn = $(this);
		$btn.prop('disabled', true).text('Clearing...');
		$.post(AISCPH.ajax_url, {
			action: 'aiscph_clear_log',
			nonce:  AISCPH.nonce,
		}, function (res) {
			if (res.success) {
				location.reload();
			} else {
				$btn.prop('disabled', false).text('Clear Log');
				showNotice(res.data.message || AISCPH.strings.error, 'error');
			}
		});
	});

