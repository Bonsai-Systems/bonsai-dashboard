/**
 * admin-settings.js — Bonsai Dashboard settings screen: the custom-cards
 * repeater (add/remove rows) and the colour-override swatch pickers.
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

	$(function () {
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
