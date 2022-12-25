/* @package Joomla
 * @copyright Copyright (C) Open Source Matters. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @extension Phoca Documentation
 * @copyright Copyright (C) Jan Pavelka www.phoca.cz - https://www.phoca.cz/phocacart
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

document.addEventListener('DOMContentLoaded', function () {

	/* Popover for list of articles */
	var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
	var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
  		return new bootstrap.Popover(popoverTriggerEl)
	})

	/* Tooltip for prev, next and top links */
	var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
	var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
		return new bootstrap.Tooltip(tooltipTriggerEl)
	})

	/* Cannot use data-bs-trigger="focus" because it makes the links in popover inactive */
	document.querySelector('body').addEventListener('click', function(e) {
		var in_popover = e.target.closest(".popover");

		if (!in_popover) {
			var popovers = document.querySelectorAll('.popover.show');

			if (popovers[0]) {
				var triggler_selector = `[aria-describedby=${popovers[0].id}]`;

				if (!e.target.closest(triggler_selector)) {
					let the_trigger = document.querySelector(triggler_selector);
					if (the_trigger) {
						bootstrap.Popover.getInstance(the_trigger).hide();
					}
				}
			}
		}
	});

}, false);
