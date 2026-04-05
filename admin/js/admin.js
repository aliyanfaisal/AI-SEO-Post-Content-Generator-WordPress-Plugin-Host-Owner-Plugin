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

		// Reindex before saving so array keys are sequential
		reindexSections();

		// Use FormData to safely handle large payloads and nested arrays
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

	// =====================
	// Section Prompts Repeater
	// =====================
	function reindexSections() {
		$('#aiscph-sections-repeater .aiscph-section-row').each(function (i) {
			$(this).attr('data-index', i);
			$(this).find('.aiscph-section-row-num').text(i + 1);
			$(this).find('.aiscph-section-name').attr('name', 'sections[' + i + '][name]');
			$(this).find('.aiscph-section-prompt').attr('name', 'sections[' + i + '][prompt]');
		});
	}

	$('#aiscph-add-section').on('click', function () {
		var count = $('#aiscph-sections-repeater .aiscph-section-row').length;
		var $row  = $(
			'<div class="aiscph-section-row" data-index="' + count + '">' +
				'<div class="aiscph-section-row-header">' +
					'<span class="aiscph-section-row-num">' + (count + 1) + '</span>' +
					'<input type="text" name="sections[' + count + '][name]" class="aiscph-section-name" placeholder="e.g. Introduction, Main Content, FAQ, Summary">' +
					'<button type="button" class="aiscph-remove-section" title="Remove">&times;</button>' +
				'</div>' +
				'<textarea name="sections[' + count + '][prompt]" class="aiscph-section-prompt" rows="4" placeholder="Write the specific prompt for this section. Claude will follow this exactly when generating this part of the post."></textarea>' +
			'</div>'
		);
		$('#aiscph-sections-repeater').append($row);
		$row.find('.aiscph-section-name').focus();
	});

	$(document).on('click', '.aiscph-remove-section', function () {
		var total = $('#aiscph-sections-repeater .aiscph-section-row').length;
		if (total <= 1) {
			// Clear instead of remove if it's the last row
			$(this).closest('.aiscph-section-row').find('input, textarea').val('');
			return;
		}
		$(this).closest('.aiscph-section-row').remove();
		reindexSections();
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

