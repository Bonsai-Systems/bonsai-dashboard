/**
 * admin-settings.js — Bonsai Dashboard settings screen: the logo media
 * picker, the custom-cards repeater (add/remove rows) and the
 * colour-override swatch pickers.
 * Enqueued only on Settings → Bonsai Dashboard, see
 * class-admin-page.php::enqueue_assets().
 *
 * Row indices only ever increment (never reused, even after a remove) so two
 * rows can never collide on the same `settings[custom_cards][N][...]` name —
 * simpler than reindexing the DOM on every remove, and PHP doesn't care that
 * the saved indices aren't contiguous (class-settings.php loops the array's
 * values, not its keys).
 */
(function ($) {
	'use strict';

	/**
	 * Logo picker — opens the core media library modal (enqueued via
	 * wp_enqueue_media()) and stores the chosen attachment's ID in a hidden
	 * input. The preview uses the modal's own "medium" size URL where one
	 * exists, falling back to the full-size URL (e.g. for SVGs, which get no
	 * intermediate sizes).
	 */
	function bonsai_initLogoPicker() {
		var $select = $('#bonsai-dashboard-logo-select');
		var $remove = $('#bonsai-dashboard-logo-remove');
		var $input = $('#bonsai-dashboard-logo-id');
		var $preview = $('.bonsai-dashboard-logo-preview');
		var frame;

		if (!$select.length || typeof wp === 'undefined' || !wp.media) {
			return;
		}

		$select.on('click.bonsai_dashboard', function (e) {
			e.preventDefault();

			if (!frame) {
				frame = wp.media({
					title: $select.text(),
					library: { type: 'image' },
					multiple: false
				});

				frame.on('select', function () {
					var attachment = frame.state().get('selection').first().toJSON();
					var url = attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url;

					$input.val(attachment.id);
					$preview.empty().append(
						$('<img>', { src: url, alt: attachment.alt || '' })
					).prop('hidden', false);
					$remove.prop('hidden', false);
				});
			}

			frame.open();
		});

		$remove.on('click.bonsai_dashboard', function (e) {
			e.preventDefault();
			$input.val('0');
			$preview.empty().prop('hidden', true);
			$remove.prop('hidden', true);
		});
	}

	$(function () {
		bonsai_initLogoPicker();

		var $container = $('#bonsai-dashboard-custom-cards');
		var $template = $('#bonsai-dashboard-card-template');

		if (!$container.length || !$template.length) {
			return;
		}

		$('#bonsai-dashboard-add-card').on('click', function (e) {
			e.preventDefault();

			var index = parseInt($container.attr('data-next-index'), 10) || 0;
			$container.attr('data-next-index', index + 1);

			var html = $template.html().replace(/__INDEX__/g, index);
			$container.append(html);
		});

		$container.on('click', '.bonsai-dashboard-remove-card', function (e) {
			e.preventDefault();
			$(this).closest('.bonsai-dashboard-card-row').remove();
		});

		// Colour overrides are all optional — clearable so a site can drop
		// back to the built-in default instead of being forced to pick a colour.
		$('.bonsai-dashboard-color-picker').wpColorPicker({
			defaultColor: false
		});
	});
})(jQuery);
