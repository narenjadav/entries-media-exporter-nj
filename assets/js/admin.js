(function($) {
	'use strict';

	$(document).ready(function() {
		const $overlay = $('#gfme-loading-overlay');
		const $message = $('#gfme-loading-message');
		const $container = $('#gfme-dashboard-container');
		let $calTooltip = $('#gfme-calendar-tooltip');

		if ($calTooltip.length === 0) {
			$calTooltip = $('<div id="gfme-calendar-tooltip" style="display:none;position:absolute;z-index:999999;background:#1e293b;color:#ffffff;padding:6px 10px;font-size:11px;font-weight:600;border-radius:4px;box-shadow:0 4px 6px -1px rgba(0,0,0,0.1);pointer-events:none;white-space:nowrap;line-height:1.2;"></div>');
			$('body').append($calTooltip);
		}

		// Helper to initialize date pickers inside loaded dashboard container.
		function initDatePickers() {
			if (typeof flatpickr !== 'undefined') {
				$container.find('#date_start, #date_end, #rm_date_start, #rm_date_end').each(function() {
					const $el = $(this);
					const minDateVal = $el.data('min-date') || '';
					flatpickr(this, {
						dateFormat: 'Y-m-d',
						allowInput: true,
						maxDate: 'today',
						minDate: minDateVal
					});
				});
			}
		}

		// Initialize Select2 Searchable Dropdown.
		if ($.fn.select2) {
			$('#gfme_form_id').select2({
				placeholder: '— Select a form —',
				width: '320px'
			}).on('change', function() {
				loadFormDetails($(this).val());
			});
		}

		// AJAX: Load Form Details.
		function loadFormDetails(formId) {
			if (!formId || formId === '0') {
				$container.html('');
				return;
			}

			$message.text('Loading form details...');
			$overlay.css('display', 'flex');

			$.ajax({
				url: gfme_admin.ajax_url,
				type: 'GET',
				dataType: 'json',
				data: {
					action: 'gfme_get_form_details',
					form_id: formId,
					nonce: gfme_admin.nonce
				},
				success: function(response) {
					$overlay.hide();
					if (response.success) {
						$container.html(response.data.html);
						initDatePickers();
					} else {
						$container.html('');
						Swal.fire({
							icon: 'error',
							title: 'Error',
							text: response.data.message || 'Failed to load form details.',
							toast: true,
							position: 'bottom-end',
							showConfirmButton: false,
							timer: 5000,
							timerProgressBar: true
						});
					}
				},
				error: function() {
					$overlay.hide();
					$container.html('');
					Swal.fire({
						icon: 'error',
						title: 'Error',
						text: 'An error occurred while loading form details.',
						toast: true,
						position: 'bottom-end',
						showConfirmButton: false,
						timer: 5000,
						timerProgressBar: true
					});
				}
			});
		}

		// Check if a form is selected on load.
		const initialFormId = $('#gfme_form_id').val();
		if (initialFormId && initialFormId !== '0') {
			loadFormDetails(initialFormId);
		}

		// AJAX: Submit Seed Data Form.
		$(document).on('submit', '.gfme-seed-form', function(e) {
			e.preventDefault();
			const $form = $(this);
			const formId = $form.find('input[name="form_id"]').val();
			const count = $form.find('input[name="count"]').val();

			$message.text('Seeding dummy entries and files...');
			$overlay.css('display', 'flex');

			$.ajax({
				url: gfme_admin.ajax_url,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'gfme_seed_entries',
					form_id: formId,
					count: count,
					nonce: gfme_admin.nonce
				},
				success: function(response) {
					$overlay.hide();
					if (response.success) {
						$('.gfme-entry-count-badge').text(response.data.entry_count);
						
						// Update data-min-date attributes.
						$container.find('#date_start, #date_end, #rm_date_start, #rm_date_end')
							.data('min-date', response.data.min_date)
							.attr('data-min-date', response.data.min_date);

						initDatePickers();

						Swal.fire({
							icon: 'success',
							title: 'Success',
							text: response.data.message,
							toast: true,
							position: 'bottom-end',
							showConfirmButton: false,
							timer: 5000,
							timerProgressBar: true
						});
					} else {
						Swal.fire({
							icon: 'error',
							title: 'Error',
							text: response.data.message,
							toast: true,
							position: 'bottom-end',
							showConfirmButton: false,
							timer: 5000,
							timerProgressBar: true
						});
					}
				},
				error: function() {
					$overlay.hide();
					Swal.fire({
						icon: 'error',
						title: 'Error',
						text: 'An error occurred while seeding data.',
						toast: true,
						position: 'bottom-end',
						showConfirmButton: false,
						timer: 5000,
						timerProgressBar: true
					});
				}
			});
		});

		// AJAX: Submit Danger Zone Removal Form.
		$(document).on('submit', '.gfme-remove-form', function(e) {
			e.preventDefault();
			const $form = $(this);

			// Verify confirmation checkbox
			const $confirmBox = $form.find('#gfme_confirm_box');
			if (!$confirmBox.prop('checked')) {
				if (typeof Swal !== 'undefined') {
					Swal.fire({
						icon: 'error',
						title: 'Confirmation Required',
						text: 'Please check the confirmation box before deleting entries.',
						toast: true,
						position: 'bottom-end',
						showConfirmButton: false,
						timer: 5000,
						timerProgressBar: true
					});
				} else {
					alert('Please check the confirmation box before deleting entries.');
				}
				return;
			}

			const formId = $form.find('input[name="form_id"]').val();
			const dateStart = $form.find('input[name="date_start"]').val();
			const dateEnd = $form.find('input[name="date_end"]').val();

			if (typeof Swal !== 'undefined') {
				Swal.fire({
					title: 'Are you sure?',
					text: 'This will permanently delete entries and erase files from the server. This action cannot be undone!',
					icon: 'warning',
					showCancelButton: true,
					confirmButtonColor: '#d33',
					cancelButtonColor: '#3085d6',
					confirmButtonText: 'Yes, delete permanently!'
				}).then((result) => {
					if (result.isConfirmed) {
						executeRemoval(formId, dateStart, dateEnd, $form);
					}
				});
			} else {
				if (confirm('Are you sure? This will permanently delete matching entries and uploaded files!')) {
					executeRemoval(formId, dateStart, dateEnd, $form);
				}
			}
		});

		function executeRemoval(formId, dateStart, dateEnd, $form) {
			$message.text('Purging entries and files...');
			$overlay.css('display', 'flex');

			$.ajax({
				url: gfme_admin.ajax_url,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'gfme_remove_entries',
					form_id: formId,
					date_start: dateStart,
					date_end: dateEnd,
					gfme_confirm: 1,
					nonce: gfme_admin.nonce
				},
				success: function(response) {
					$overlay.hide();
					if (response.success) {
						$('.gfme-entry-count-badge').text(response.data.entry_count);
						
						$form.find('input[name="date_start"]').val('');
						$form.find('input[name="date_end"]').val('');
						$form.find('#gfme_confirm_box').prop('checked', false);

						$container.find('#date_start, #date_end, #rm_date_start, #rm_date_end')
							.data('min-date', response.data.min_date)
							.attr('data-min-date', response.data.min_date);

						initDatePickers();

						Swal.fire({
							icon: 'success',
							title: 'Purged',
							text: response.data.message,
							toast: true,
							position: 'bottom-end',
							showConfirmButton: false,
							timer: 5000,
							timerProgressBar: true
						});
					} else {
						Swal.fire({
							icon: 'error',
							title: 'Error',
							text: response.data.message,
							toast: true,
							position: 'bottom-end',
							showConfirmButton: false,
							timer: 5000,
							timerProgressBar: true
						});
					}
				},
				error: function() {
					$overlay.hide();
					Swal.fire({
						icon: 'error',
						title: 'Error',
						text: 'An error occurred while deleting entries.',
						toast: true,
						position: 'bottom-end',
						showConfirmButton: false,
						timer: 5000,
						timerProgressBar: true
					});
				}
			});
		}

		// Handle disabled date picker popovers.
		$(document).on('mouseenter', '.flatpickr-day.flatpickr-disabled', function() {
			const dayEl = this;
			if (!dayEl.dateObj) {
				return;
			}

			const calendarEl = $(dayEl).closest('.flatpickr-calendar')[0];
			if (!calendarEl || !calendarEl._flatpickr) {
				return;
			}

			const fp = calendarEl._flatpickr;
			const date = new Date(dayEl.dateObj.getTime());
			date.setHours(0, 0, 0, 0);

			const today = new Date();
			today.setHours(0, 0, 0, 0);

			let minDate = fp.config.minDate;
			if (minDate && !(minDate instanceof Date)) {
				minDate = new Date(minDate);
			}
			if (minDate) {
				minDate.setHours(0, 0, 0, 0);
			}

			let text = '';
			if (date > today) {
				text = 'No future entries found';
			} else if (minDate && date < minDate) {
				text = 'No entries found before this date';
			}

			if (!text) {
				return;
			}

			$calTooltip.text(text);
			$calTooltip.show();

			const rect = dayEl.getBoundingClientRect();
			const tooltipWidth = $calTooltip.outerWidth();
			const tooltipHeight = $calTooltip.outerHeight();

			const top = rect.top + window.scrollY - tooltipHeight - 6;
			const left = rect.left + window.scrollX + (rect.width / 2) - (tooltipWidth / 2);

			$calTooltip.css({
				top: top + 'px',
				left: left + 'px'
			});
		});

		$(document).on('mouseleave', '.flatpickr-day.flatpickr-disabled', function() {
			$calTooltip.hide();
		});

		// Dynamic download spinner using cookies.
		$(document).on('submit', '.gfme-export-form', function() {
			const token = new Date().getTime();
			$(this).append($('<input type="hidden" name="gfme_download_token" />').val(token));

			$message.text('Preparing ZIP export package... Please wait.');
			$overlay.css('display', 'flex');

			const checkInterval = setInterval(function() {
				if (getCookie('gfme_download_token') === String(token)) {
					clearInterval(checkInterval);
					document.cookie = 'gfme_download_token=; Max-Age=-99999999; path=/;';
					$overlay.hide();
					Swal.fire({
						icon: 'success',
						title: 'Export Complete',
						text: 'The ZIP package was generated successfully and streamed to your browser for download.',
						toast: true,
						position: 'bottom-end',
						showConfirmButton: false,
						timer: 5000,
						timerProgressBar: true
					});
				}
			}, 500);
		});

		function getCookie(name) {
			const parts = ('; ' + document.cookie).split('; ' + name + '=');
			if (parts.length === 2) {
				return parts.pop().split(';').shift();
			}
		}
	});
})(jQuery);
