(function($) {

	'use strict';

	if (typeof _WELDPRESS == 'undefined' || _WELDPRESS === null) {
		return;
	}

	_WELDPRESS = $.extend({
		cached: 0
	}, _WELDPRESS);

	$.fn.WELDPRESSInitForm = function() {
		this.ajaxForm({
			beforeSubmit: function(arr, $form, options) {
				$form.WELDPRESSClearResponseOutput();
				$form.find('[aria-invalid]').attr('aria-invalid', 'false');
				$form.find('.ajax-loader').addClass('is-active');
				return true;
			},
			beforeSerialize: function($form, options) {
				$form.find('[placeholder].placeheld').each(function(i, n) {
					$(n).val('');
				});
				return true;
			},
			data: { '_WELD_PRESS_is_ajax_call': 1 },
			dataType: 'json',
			success: $.WELDPRESSAjaxSuccess,
			error: function(xhr, status, error, $form) {
				var e = $('<div class="ajax-error"></div>').text(error.message);
				$form.after(e);
			}
		});

		if (_WELDPRESS.cached) {
			this.WELDPRESSOnloadRefill();
		}

		this.WELDPRESSToggleSubmit();

		this.find('.WELDPRESS-submit').WELDPRESSAjaxLoader();

		this.find('.WELDPRESS-acceptance').click(function() {
			$(this).closest('form').WELDPRESSToggleSubmit();
		});

		this.find('.WELDPRESS-exclusive-checkbox').WELDPRESSExclusiveCheckbox();

		this.find('.WELDPRESS-list-item.has-free-text').WELDPRESSToggleCheckboxFreetext();

		this.find('[placeholder]').WELDPRESSPlaceholder();

		if (_WELDPRESS.jqueryUi && ! _WELDPRESS.supportHtml5.date) {
			this.find('input.WELDPRESS-date[type="date"]').each(function() {
				$(this).datepicker({
					dateFormat: 'yy-mm-dd',
					minDate: new Date($(this).attr('min')),
					maxDate: new Date($(this).attr('max'))
				});
			});
		}

		if (_WELDPRESS.jqueryUi && ! _WELDPRESS.supportHtml5.number) {
			this.find('input.WELDPRESS-number[type="number"]').each(function() {
				$(this).spinner({
					min: $(this).attr('min'),
					max: $(this).attr('max'),
					step: $(this).attr('step')
				});
			});
		}

		this.find('.WELDPRESS-character-count').WELDPRESSCharacterCount();

		this.find('.WELDPRESS-validates-as-url').change(function() {
			$(this).WELDPRESSNormalizeUrl();
		});

		this.find('.WELDPRESS-recaptcha').WELDPRESSRecaptcha();
	};

	$.WELDPRESSAjaxSuccess = function(data, status, xhr, $form) {
		if (! $.isPlainObject(data) || $.isEmptyObject(data)) {
			return;
		}

		var $responseOutput = $form.find('div.WELDPRESS-response-output');

		$form.WELDPRESSClearResponseOutput();

		$form.find('.WELDPRESS-form-control').removeClass('WELDPRESS-not-valid');
		$form.removeClass('invalid spam sent failed');

		if (data.captcha) {
			$form.WELDPRESSRefillCaptcha(data.captcha);
		}

		if (data.quiz) {
			$form.WELDPRESSRefillQuiz(data.quiz);
		}

		if (data.invalids) {
			$.each(data.invalids, function(i, n) {
				$form.find(n.into).WELDPRESSNotValidTip(n.message);
				$form.find(n.into).find('.WELDPRESS-form-control').addClass('WELDPRESS-not-valid');
				$form.find(n.into).find('[aria-invalid]').attr('aria-invalid', 'true');
			});

			$responseOutput.addClass('WELDPRESS-validation-errors');
			$form.addClass('invalid');

			$(data.into).trigger('WELDPRESS:invalid');
			$(data.into).trigger('invalid.WELDPRESS'); // deprecated

		} else if (1 == data.spam) {
			$form.find('[name="g-recaptcha-response"]').each(function() {
				if ('' == $(this).val()) {
					var $recaptcha = $(this).closest('.WELDPRESS-form-control-wrap');
					$recaptcha.WELDPRESSNotValidTip(_WELDPRESS.recaptcha.messages.empty);
				}
			});

			$responseOutput.addClass('WELDPRESS-spam-blocked');
			$form.addClass('spam');

			$(data.into).trigger('WELDPRESS:spam');
			$(data.into).trigger('spam.WELDPRESS'); // deprecated

		} else if (1 == data.mailSent) {
			$responseOutput.addClass('WELDPRESS-mail-sent-ok');
			$form.addClass('sent');

			if (data.onSentOk) {
				$.each(data.onSentOk, function(i, n) { eval(n) });
			}

			$(data.into).trigger('WELDPRESS:mailsent');
			$(data.into).trigger('mailsent.WELDPRESS'); // deprecated

		} else {
			$responseOutput.addClass('WELDPRESS-mail-sent-ng');
			$form.addClass('failed');

			$(data.into).trigger('WELDPRESS:mailfailed');
			$(data.into).trigger('mailfailed.WELDPRESS'); // deprecated
		}

		if (data.onSubmit) {
			$.each(data.onSubmit, function(i, n) { eval(n) });
		}

		$(data.into).trigger('WELDPRESS:submit');
		$(data.into).trigger('submit.WELDPRESS'); // deprecated

		if (1 == data.mailSent) {
			$form.resetForm();
		}

		$form.find('[placeholder].placeheld').each(function(i, n) {
			$(n).val($(n).attr('placeholder'));
		});

		$responseOutput.append(data.message).slideDown('fast');
		$responseOutput.attr('role', 'alert');

		$.WELDPRESSUpdateScreenReaderResponse($form, data);
	};

	$.fn.WELDPRESSExclusiveCheckbox = function() {
		return this.find('input:checkbox').click(function() {
			var name = $(this).attr('name');
			$(this).closest('form').find('input:checkbox[name="' + name + '"]').not(this).prop('checked', false);
		});
	};

	$.fn.WELDPRESSPlaceholder = function() {
		if (_WELDPRESS.supportHtml5.placeholder) {
			return this;
		}

		return this.each(function() {
			$(this).val($(this).attr('placeholder'));
			$(this).addClass('placeheld');

			$(this).focus(function() {
				if ($(this).hasClass('placeheld'))
					$(this).val('').removeClass('placeheld');
			});

			$(this).blur(function() {
				if ('' == $(this).val()) {
					$(this).val($(this).attr('placeholder'));
					$(this).addClass('placeheld');
				}
			});
		});
	};

	$.fn.WELDPRESSAjaxLoader = function() {
		return this.each(function() {
			$(this).after('<span class="ajax-loader"></span>');
		});
	};

	$.fn.WELDPRESSToggleSubmit = function() {
		return this.each(function() {
			var form = $(this);

			if (this.tagName.toLowerCase() != 'form') {
				form = $(this).find('form').first();
			}

			if (form.hasClass('WELDPRESS-acceptance-as-validation')) {
				return;
			}

			var submit = form.find('input:submit');
			if (! submit.length) return;

			var acceptances = form.find('input:checkbox.WELDPRESS-acceptance');
			if (! acceptances.length) return;

			submit.removeAttr('disabled');
			acceptances.each(function(i, n) {
				n = $(n);
				if (n.hasClass('WELDPRESS-invert') && n.is(':checked')
				|| ! n.hasClass('WELDPRESS-invert') && ! n.is(':checked')) {
					submit.attr('disabled', 'disabled');
				}
			});
		});
	};

	$.fn.WELDPRESSToggleCheckboxFreetext = function() {
		return this.each(function() {
			var $wrap = $(this).closest('.WELDPRESS-form-control');

			if ($(this).find(':checkbox, :radio').is(':checked')) {
				$(this).find(':input.WELDPRESS-free-text').prop('disabled', false);
			} else {
				$(this).find(':input.WELDPRESS-free-text').prop('disabled', true);
			}

			$wrap.find(':checkbox, :radio').change(function() {
				var $cb = $('.has-free-text', $wrap).find(':checkbox, :radio');
				var $freetext = $(':input.WELDPRESS-free-text', $wrap);

				if ($cb.is(':checked')) {
					$freetext.prop('disabled', false).focus();
				} else {
					$freetext.prop('disabled', true);
				}
			});
		});
	};

	$.fn.WELDPRESSCharacterCount = function() {
		return this.each(function() {
			var $count = $(this);
			var name = $count.attr('data-target-name');
			var down = $count.hasClass('down');
			var starting = parseInt($count.attr('data-starting-value'), 10);
			var maximum = parseInt($count.attr('data-maximum-value'), 10);
			var minimum = parseInt($count.attr('data-minimum-value'), 10);

			var updateCount = function($target) {
				var length = $target.val().length;
				var count = down ? starting - length : length;
				$count.attr('data-current-value', count);
				$count.text(count);

				if (maximum && maximum < length) {
					$count.addClass('too-long');
				} else {
					$count.removeClass('too-long');
				}

				if (minimum && length < minimum) {
					$count.addClass('too-short');
				} else {
					$count.removeClass('too-short');
				}
			};

			$count.closest('form').find(':input[name="' + name + '"]').each(function() {
				updateCount($(this));

				$(this).keyup(function() {
					updateCount($(this));
				});
			});
		});
	};

	$.fn.WELDPRESSNormalizeUrl = function() {
		return this.each(function() {
			var val = $.trim($(this).val());

			if (val && ! val.match(/^[a-z][a-z0-9.+-]*:/i)) { // check the scheme part
				val = val.replace(/^\/+/, '');
				val = 'http://' + val;
			}

			$(this).val(val);
		});
	};

	$.fn.WELDPRESSNotValidTip = function(message) {
		return this.each(function() {
			var $into = $(this);

			$into.find('span.WELDPRESS-not-valid-tip').remove();
			$into.append('<span role="alert" class="WELDPRESS-not-valid-tip">' + message + '</span>');

			if ($into.is('.use-floating-validation-tip *')) {
				$('.WELDPRESS-not-valid-tip', $into).mouseover(function() {
					$(this).WELDPRESSFadeOut();
				});

				$(':input', $into).focus(function() {
					$('.WELDPRESS-not-valid-tip', $into).not(':hidden').WELDPRESSFadeOut();
				});
			}
		});
	};

	$.fn.WELDPRESSFadeOut = function() {
		return this.each(function() {
			$(this).animate({
				opacity: 0
			}, 'fast', function() {
				$(this).css({'z-index': -100});
			});
		});
	};

	$.fn.WELDPRESSOnloadRefill = function() {
		return this.each(function() {
			var url = $(this).attr('action');

			if (0 < url.indexOf('#')) {
				url = url.substr(0, url.indexOf('#'));
			}

			var id = $(this).find('input[name="_WELDPRESS"]').val();
			var unitTag = $(this).find('input[name="_WELD_PRESS_unit_tag"]').val();

			$.getJSON(url,
				{ _WELD_PRESS_is_ajax_call: 1, _WELDPRESS: id, _WELD_PRESS_request_ver: $.now() },
				function(data) {
					if (data && data.captcha) {
						$('#' + unitTag).WELDPRESSRefillCaptcha(data.captcha);
					}

					if (data && data.quiz) {
						$('#' + unitTag).WELDPRESSRefillQuiz(data.quiz);
					}
				}
			);
		});
	};

	$.fn.WELDPRESSRefillCaptcha = function(captcha) {
		return this.each(function() {
			var form = $(this);

			$.each(captcha, function(i, n) {
				form.find(':input[name="' + i + '"]').clearFields();
				form.find('img.WELDPRESS-captcha-' + i).attr('src', n);
				var match = /([0-9]+)\.(png|gif|jpeg)$/.exec(n);
				form.find('input:hidden[name="_WELD_PRESS_captcha_challenge_' + i + '"]').attr('value', match[1]);
			});
		});
	};

	$.fn.WELDPRESSRefillQuiz = function(quiz) {
		return this.each(function() {
			var form = $(this);

			$.each(quiz, function(i, n) {
				form.find(':input[name="' + i + '"]').clearFields();
				form.find(':input[name="' + i + '"]').siblings('span.WELDPRESS-quiz-label').text(n[0]);
				form.find('input:hidden[name="_WELD_PRESS_quiz_answer_' + i + '"]').attr('value', n[1]);
			});
		});
	};

	$.fn.WELDPRESSClearResponseOutput = function() {
		return this.each(function() {
			$(this).find('div.WELDPRESS-response-output').hide().empty().removeClass('WELDPRESS-mail-sent-ok WELDPRESS-mail-sent-ng WELDPRESS-validation-errors WELDPRESS-spam-blocked').removeAttr('role');
			$(this).find('span.WELDPRESS-not-valid-tip').remove();
			$(this).find('.ajax-loader').removeClass('is-active');
		});
	};

	$.fn.WELDPRESSRecaptcha = function() {
		return this.each(function() {
			var events = 'WELDPRESS:spam WELDPRESS:mailsent WELDPRESS:mailfailed';
			$(this).closest('div.WELDPRESS').on(events, function(e) {
				if (recaptchaWidgets && grecaptcha) {
					$.each(recaptchaWidgets, function(index, value) {
						grecaptcha.reset(value);
					});
				}
			});
		});
	};

	$.WELDPRESSUpdateScreenReaderResponse = function($form, data) {
		$('.WELDPRESS .screen-reader-response').html('').attr('role', '');

		if (data.message) {
			var $response = $form.siblings('.screen-reader-response').first();
			$response.append(data.message);

			if (data.invalids) {
				var $invalids = $('<ul></ul>');

				$.each(data.invalids, function(i, n) {
					if (n.idref) {
						var $li = $('<li></li>').append($('<a></a>').attr('href', '#' + n.idref).append(n.message));
					} else {
						var $li = $('<li></li>').append(n.message);
					}

					$invalids.append($li);
				});

				$response.append($invalids);
			}

			$response.attr('role', 'alert').focus();
		}
	};

	$.WELDPRESSSupportHtml5 = function() {
		var features = {};
		var input = document.createElement('input');

		features.placeholder = 'placeholder' in input;

		var inputTypes = ['email', 'url', 'tel', 'number', 'range', 'date'];

		$.each(inputTypes, function(index, value) {
			input.setAttribute('type', value);
			features[value] = input.type !== 'text';
		});

		return features;
	};

	$(function() {
		_WELDPRESS.supportHtml5 = $.WELDPRESSSupportHtml5();
		$('div.WELDPRESS > form').WELDPRESSInitForm();
	});

})(jQuery);
