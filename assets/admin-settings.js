/**
 * admin-settings.js — Bonsai Dashboard settings screen: the media-library
 * image pickers (Welcome logo, White Label images), the custom-cards
 * repeater (add/remove rows) and the colour swatch pickers.
 * Enqueued only on Settings → Bonsai Dashboard, see
 * class-admin-page.php::enqueue_assets(). Only one tab is rendered per page
 * load, so every block below simply does nothing when its markup isn't on
 * the page.
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
	 * Image pickers — every .bonsai-dashboard-media-field (markup from
	 * Bonsai_Dashboard_Admin_Page::media_field()). Opens the core media
	 * library modal (enqueued via wp_enqueue_media()) and stores the chosen
	 * attachment's ID in the field's hidden input. The preview uses the
	 * "medium" size URL where one exists, falling back to the full-size URL
	 * (e.g. for SVGs, which get no intermediate sizes). One modal per field,
	 * created on first use and kept on the element.
	 */
	function bonsai_initMediaFields() {
		if (typeof wp === 'undefined' || !wp.media) {
			return;
		}

		$(document).on('click.bonsai_dashboard', '.bonsai-dashboard-media-field__select', function (e) {
			e.preventDefault();

			var $field = $(this).closest('.bonsai-dashboard-media-field');
			var frame = $field.data('bonsaiFrame');

			if (!frame) {
				frame = wp.media({
					title: $(this).text(),
					library: { type: 'image' },
					multiple: false
				});

				frame.on('select', function () {
					var attachment = frame.state().get('selection').first().toJSON();
					var url = attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url;

					$field.find('input[type="hidden"]').val(attachment.id);
					$field.find('.bonsai-dashboard-media-field__preview').empty().append(
						$('<img>', { src: url, alt: attachment.alt || '' })
					).prop('hidden', false);
					$field.find('.bonsai-dashboard-media-field__remove').prop('hidden', false);
				});

				$field.data('bonsaiFrame', frame);
			}

			frame.open();
		});

		$(document).on('click.bonsai_dashboard', '.bonsai-dashboard-media-field__remove', function (e) {
			e.preventDefault();

			var $field = $(this).closest('.bonsai-dashboard-media-field');
			$field.find('input[type="hidden"]').val('0');
			$field.find('.bonsai-dashboard-media-field__preview').empty().prop('hidden', true);
			$(this).prop('hidden', true);
		});
	}

	/** Custom-cards repeater (Quick links tab). */
	function bonsai_initCustomCards() {
		var $container = $('#bonsai-dashboard-custom-cards');
		var $template = $('#bonsai-dashboard-card-template');

		if (!$container.length || !$template.length) {
			return;
		}

		$('#bonsai-dashboard-add-card').on('click.bonsai_dashboard', function (e) {
			e.preventDefault();

			var index = parseInt($container.attr('data-next-index'), 10) || 0;
			$container.attr('data-next-index', index + 1);

			var html = $template.html().replace(/__INDEX__/g, index);
			$container.append(html);
		});

		$container.on('click.bonsai_dashboard', '.bonsai-dashboard-remove-card', function (e) {
			e.preventDefault();
			$(this).closest('.bonsai-dashboard-card-row').remove();
		});
	}

	$(function () {
		bonsai_initMediaFields();
		bonsai_initCustomCards();

		// Every colour field is optional — clearable so a site can drop back
		// to the default instead of being forced to pick a colour.
		$('.bonsai-dashboard-color-picker').wpColorPicker({
			defaultColor: false
		});
	});
})(jQuery);
