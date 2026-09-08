/*!
This file is part of Ninjalytics. For licensing and copyright notices, please see ../license.txt.
*/
jQuery(document).ready(function($) {

	var $productSelect = $('#ninjalytics-product-ids');
	if ($productSelect.length && typeof $productSelect.selectWoo === 'function') {
		var productSelectConfig = window.ninjalyticsProductSelect || {};
		var $productSelectHidden = $('#ninjalytics-product-ids-input');

		function formatProductLabel(product) {
			if (!product) {
				return '';
			}
			var baseLabel = product.name || '';
			if (!baseLabel) {
				baseLabel = wp.i18n.__( 'Product #%d', 'product-sales-report-for-woocommerce' ).replace('%d', product.id);
			}
			return baseLabel + ' (' + (product.sku ? product.sku : '#' + product.id) + ')';
		}

		function updateHiddenProductIds() {
			var currentValues = $productSelect.val() || [];
			$productSelectHidden.val(currentValues.join(','));
		}

		function ensureProductsLoaded(ids) {
			if (!Array.isArray(ids) || !ids.length || !productSelectConfig.restUrl) {
				updateHiddenProductIds();
				return;
			}
			var requiredIds = [];
			$.each(ids, function(_, id) {
				var trimmedId = $.trim(id);
				if (!trimmedId) {
					return;
				}
				var $option = $productSelect.find('option[value="' + trimmedId + '"]');
				if ($option.length && $option.attr('data-ninjalytics-loaded')) {
					return;
				}
				if (requiredIds.indexOf(trimmedId) === -1) {
					requiredIds.push(trimmedId);
				}
				if (!$option.length) {
					$('<option>')
						.val(trimmedId)
						.text(wp.i18n.__( 'Product #%d', 'product-sales-report-for-woocommerce' ).replace('%d', trimmedId))
						.prop('selected', true)
						.attr('data-ninjalytics-loaded', '0')
						.appendTo($productSelect);
				}
			});
			if (!requiredIds.length) {
				updateHiddenProductIds();
				return;
			}
			$.ajax({
				url: productSelectConfig.restUrl,
				method: 'GET',
				dataType: 'json',
				data: {
					include: requiredIds.join(','),
					per_page: requiredIds.length,
					context: 'view'
				},
				headers: productSelectConfig.nonce ? { 'X-WP-Nonce': productSelectConfig.nonce } : {}
			}).success(function(response) {
				var indexed = {};
				$.each(response || [], function(_, product) {
					indexed[String(product.id)] = product;
				});
				$.each(requiredIds, function(_, id) {
					var lookupId = String(id);
					var $option = $productSelect.find('option[value="' + lookupId + '"]');
					var productData = indexed[lookupId];
					var optionLabel;
					if (productData) {
						optionLabel = formatProductLabel(productData);
					} else {
						optionLabel = wp.i18n.__( 'Product #%d (not found)', 'product-sales-report-for-woocommerce' ).replace('%d', lookupId);
					}
					if ($option.length) {
						$option
							.text(optionLabel)
							.attr('data-ninjalytics-loaded', '1');
					} else {
						$('<option>')
							.val(lookupId)
							.text(optionLabel)
							.attr('data-ninjalytics-loaded', '1')
							.prop('selected', true)
							.appendTo($productSelect);
					}
				});
				$productSelect.trigger('change.select2');
				updateHiddenProductIds();
			}).fail(function() {
				updateHiddenProductIds();
			});
		}

		$productSelect.selectWoo({
			width: '100%',
			multiple: true,
			placeholder: wp.i18n.__( 'Search for products by name or ID', 'product-sales-report-for-woocommerce' ) || '',
			minimumInputLength: 1,
			allowClear: true,
			tags: true,
			tokenSeparators: [','],
			containerCssClass: 'ninjalytics-select2-multiple-container',
			dropdownCssClass: 'berrypress-multiple-dropdown ninjalytics-select2-multiple-dropdown',
			createTag: function(params) {
				var term = params.term ? params.term.trim() : '';
				if (!term || !/^\d+$/.test(term)) {
					return null;
				}
				if ($productSelect.find('option[value="' + term + '"]').length) {
					return null;
				}
				return {
					id: term,
					text: wp.i18n.__( 'Product #%d', 'product-sales-report-for-woocommerce' ).replace('%d', term),
					isNewId: true
				};
			},
			insertTag: function(data, tag) {
				data.push(tag);
			},
			ajax: {
				delay: 250,
				cache: true,
				transport: function(params, success, failure) {
					if (!productSelectConfig.restUrl) {
						setTimeout(failure, 250);
						return {};
					}
					var request = $.ajax({
						url: productSelectConfig.restUrl,
						method: 'GET',
						data: params.data,
						dataType: 'json',
						headers: productSelectConfig.nonce ? { 'X-WP-Nonce': productSelectConfig.nonce } : {}
					});
					request.then(function(data, textStatus, jqXHR) {
						params._totalPages = parseInt(jqXHR.getResponseHeader('X-WP-TotalPages') || '1', 10);
						success(data);
					}, failure);
					return request;
				},
				data: function(params) {
					var term = params.term ? params.term.trim() : '';
					var query = {
						per_page: 50,
						page: params.page || 1,
						context: 'view',
						orderby: 'title',
						order: 'asc'
					};
					if (term) {
						query.search = term;
						if (/^\d+$/.test(term)) {
							query.include = term;
						}
					}
					return query;
				},
				processResults: function(data, params) {
					params.page = params.page || 1;
					var results = $.map(data || [], function(product) {
						return {
							id: String(product.id),
							text: formatProductLabel(product),
							productData: product
						};
					});
					return {
						results: results,
						pagination: {
							more: params._totalPages && params.page < params._totalPages
						}
					};
				}
			},
			templateResult: function(item) {
				return item.text || item.id;
			},
			templateSelection: function(item) {
				return item.text || item.id;
			}
		});

		$productSelect.on('change', updateHiddenProductIds);
		$productSelect.on('select2:select', function(event) {
			var selectedData = event && event.params ? event.params.data : null;
			if (selectedData && selectedData.isNewId) {
				ensureProductsLoaded([selectedData.id]);
			}
		});

		var initialIds = $productSelectHidden.val()
			? $productSelectHidden.val().split(',').filter(function(value) {
				return value !== '';
			})
			: [];
		if (initialIds.length) {
			ensureProductsLoaded(initialIds);
		} else {
			updateHiddenProductIds();
		}
	}

	window.ninjalytics_update_chart = function() {
		if (window.ninjalytics_chart && window.ninjalytics_chart.destroy) {
			ninjalytics_chart.destroy();
		}
		if (window.ninjalytics_table && window.ninjalytics_table.destroy) {
			window.ninjalytics_table_search = ninjalytics_table.search();
			window.hm_psr_freeze_field_order = true;
			ninjalytics_table.destroy(true);
			window.hm_psr_freeze_field_order = false;
			 $('#ninjalytics_output_container h2').remove();
		} else {
			window.ninjalytics_table_search = null;
		}
		var mode = $('.ninjalytics-display-options button[aria-pressed="true"]').data('display-mode');
		$('#ninjalytics-chart-no-fields').addClass('berrypress-hidden');
		$('#ninjalytics_output_container').removeClass('ninjalytics-output-chart ninjalytics-output-table').addClass('ninjalytics-output-' + mode).show();
		$('#hm_sbp_field_orderby, #ninjalytics-orderdir,.hm_psr_field_name').toggleClass('ninjalytics-no-update', mode == 'table');
		hm_psr_get_chart_data( $('#ninjalytics_output_container'), mode, mode == 'chart' ? hm_psr_build_chart : hm_psr_build_table, null, hm_psr_update_debug_sql_box );
	}

	$('#ninjalytics-refresh-report-button').on('click', function() {
		ninjalytics_update_chart();
	});

	$('.ninjalytics-display-options button').on('click', function () {

		// update aria-pressed state
		$('.ninjalytics-display-options button').attr('aria-pressed', 'false');
		$(this).attr('aria-pressed', 'true');
		ninjalytics_update_chart();

	});

	$('#ninjalytics-dates-desc').on('focus', function() {
		$('#ninjalytics-date-range-dropdown').removeClass('berrypress-hidden');
	});
	$(document.body).on('click', function(ev) {
		if (!$(ev.target).closest('#ninjalytics-date-range').length) {
			$('#ninjalytics-date-range-dropdown').addClass('berrypress-hidden');
			$('#ninjalytics-date-range-dropdown .ninjalytics-date-range-dropdown-alt-dates').addClass('berrypress-hidden');
			$('#ninjalytics-date-range-dropdown .ninjalytics-alt-dates-toggle-btn').text('Change');
		}
	});
	// Generic focus management for .berrypress-modal (dialog semantics,
	// focus trap while open, focus returned to whatever opened it on close).
	// Only one modal is ever active at a time in this UI, so a single
	// namespaced document handler is enough for the Tab trap.
	var FOCUSABLE_SELECTOR = 'a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), [tabindex]:not([tabindex="-1"])';
	var $lastModalTrigger = null;
	var $lastDropdownTrigger = null;

	function trapModalTab(e, $modal) {
		if (e.key !== 'Tab' && e.keyCode !== 9) {
			return;
		}
		var $focusable = $modal.find(FOCUSABLE_SELECTOR).filter(':visible');
		if (!$focusable.length) {
			return;
		}
		var first = $focusable[0];
		var last = $focusable[$focusable.length - 1];
		if (e.shiftKey && document.activeElement === first) {
			e.preventDefault();
			last.focus();
		} else if (!e.shiftKey && document.activeElement === last) {
			e.preventDefault();
			first.focus();
		}
	}

	// If the trigger lives inside a dropdown menu, that menu closes as soon as
	// the modal opens (see closeAllDropdowns() below) — so by the time we'd
	// restore focus to it, it's display:none and unfocusable. Resolve to the
	// dropdown's own trigger button instead, since that's what's actually
	// still visible/focusable once the menu auto-closes.
	function resolveFocusTarget($trigger) {
		if ($trigger && $trigger.length && $trigger.closest('.berrypress-dropdown-menu').length) {
			var $dropdownTrigger = $trigger.closest('.berrypress-dropdown').find('.berrypress-dropdown-trigger');
			if ($dropdownTrigger.length) {
				return $dropdownTrigger;
			}
		}
		return $trigger;
	}

	function openModalA11y($modal, $trigger) {
		if (!$modal.length) {
			return;
		}
		$lastModalTrigger = resolveFocusTarget($trigger && $trigger.length ? $trigger : $(document.activeElement));
		$modal.addClass('berrypress-active');
		$modal.find(FOCUSABLE_SELECTOR).filter(':visible').first().trigger('focus');
		$(document).on('keydown.bpModalTrap', function(e) {
			trapModalTab(e, $modal);
		});
	}

	function closeModalA11y($modal) {
		if (!$modal.length) {
			return;
		}
		$modal.removeClass('berrypress-active');
		$(document).off('keydown.bpModalTrap');
		if ($lastModalTrigger && $lastModalTrigger.length && document.contains($lastModalTrigger[0]) && $lastModalTrigger.is(':visible')) {
			$lastModalTrigger.trigger('focus');
		}
		$lastModalTrigger = null;
	}

	$('.berrypress-modal-close').on('click', function() {
		closeModalA11y($(this).closest('.berrypress-modal'));
		if ($(this).closest('.ninjalytics-templates').length) {
			$('#ninjalytics-template-search').val('');
		}
	});

	function closeAllDropdowns() {
		var focusWasInMenu = $lastDropdownTrigger && $(document.activeElement).closest('.berrypress-dropdown-menu').length;
		$('.berrypress-dropdown-menu').addClass('berrypress-hidden');
		$('.berrypress-dropdown-trigger').attr('aria-expanded', 'false');
		if (focusWasInMenu && $lastDropdownTrigger) {
			$lastDropdownTrigger.trigger('focus');
		}
		$lastDropdownTrigger = null;
	}

	function closeReportSidebarPanel() {
		if (!window.ninjalyticsReportSidebarTabs || !window.ninjalyticsReportSidebarTabs.getSelectedTabId) {
			return;
		}
		var selectedId = window.ninjalyticsReportSidebarTabs.getSelectedTabId();
		if (!selectedId) {
			return;
		}
		window.ninjalyticsReportSidebarTabs.unselect();
		$('#' + selectedId).trigger('focus');
	}

	$(document).on('click', '[data-ninjalytics-sidebar-close]', function(e) {
		e.preventDefault();
		closeReportSidebarPanel();
	});

	$(document).on('keydown', function(e) {
		if (e.key === 'Escape' || e.keyCode === 27) {
			var hadOpenModal = $('.berrypress-modal.berrypress-active').length > 0;
			$('.berrypress-modal.berrypress-active').each(function() {
				closeModalA11y($(this));
			});
			closeAllDropdowns();
			if (!hadOpenModal) {
				closeReportSidebarPanel();
			}
		}
	});

	$(document).on('click', '[data-ninjalytics-open-modal]', function(e) {
		e.preventDefault();
		openModalA11y($('#' + $(this).data('ninjalytics-open-modal')), $(this));
		closeAllDropdowns();
	});

	// Keep the menu within the viewport — same problem/approach as the
	// tooltip's Tooltip.adjustPosition: prefer below+right-aligned, but flip
	// above the trigger if there's no room below, and clamp horizontally so
	// it never runs off either edge. position:fixed also sidesteps clipping
	// by any scrolling/overflow:hidden ancestor between the trigger and body.
	function positionDropdownMenu($trigger, $menu) {
		var padding = 8;
		var gap = 5;
		var triggerRect = $trigger[0].getBoundingClientRect();

		$menu.css({ position: 'fixed', top: '-9999px', left: '-9999px', right: 'auto' });
		var menuRect = $menu[0].getBoundingClientRect();

		var top = triggerRect.bottom + gap;
		if (top + menuRect.height > window.innerHeight - padding) {
			var above = triggerRect.top - menuRect.height - gap;
			top = above >= padding ? above : Math.max(padding, window.innerHeight - menuRect.height - padding);
		}

		var left = triggerRect.right - menuRect.width;
		if (left < padding) {
			left = padding;
		}
		if (left + menuRect.width > window.innerWidth - padding) {
			left = window.innerWidth - menuRect.width - padding;
		}

		$menu.css({ top: top + 'px', left: left + 'px' });
	}

	// Generic dropdown: any .berrypress-dropdown-trigger toggles the
	// .berrypress-dropdown-menu inside its .berrypress-dropdown wrapper.
	$(document).on('click', '.berrypress-dropdown-trigger', function(e) {
		e.preventDefault();
		var $dropdown = $(this).closest('.berrypress-dropdown');
		var $menu = $dropdown.find('.berrypress-dropdown-menu');
		var isOpen = !$menu.hasClass('berrypress-hidden');
		closeAllDropdowns();
		if (!isOpen) {
			$lastDropdownTrigger = $(this);
			$menu.removeClass('berrypress-hidden');
			positionDropdownMenu($(this), $menu);
			$(this).attr('aria-expanded', 'true');
		}
	});

	$(document.body).on('click', function(ev) {
		if (!$(ev.target).closest('.berrypress-dropdown').length) {
			closeAllDropdowns();
		}
	});

(function() {
		var $templateModal = $('.ninjalytics-modal-templates');
		if (!$templateModal.length) {
			return;
		}
		var $templateGrid = $templateModal.find('.ninjalytics-template-modal-grid');
		var $templateCards = $templateModal.find('[data-ninjalytics-template-card="true"]');
		var $templateFilters = $templateModal.find('.js-ninjalytics-template-filter');
		var $sectionHeaders = $templateModal.find('[data-ninjalytics-template-section]');
		var $searchInput = $('#ninjalytics-template-search');
		var activeFilter = $('.ninjalytics-template-modal-filters > :first-child').attr('data-ninjalytics-template-filter');

		function parseDataObject(value) {
			if (typeof value === 'string' && value.length) {
				try {
					return JSON.parse(value);
				} catch (err) {
					return {};
				}
			}
			return value || {};
		}

		function getIntegrationsList($card) {
			var raw = ($card.data('ninjalytics-template-integrations') || '').toString();
			if (!raw.length) {
				return [];
			}
			return raw.split(',').map(function(entry) {
				return entry.trim();
			}).filter(function(entry) {
				return entry.length;
			});
		}

		function setActionVisibility($el, visible) {
			$el.toggleClass('ninjalytics-template-card-action-hidden', !visible);
		}

		function updateCardState($card) {

		}

		function setFilterButtonState() {
			$templateFilters.each(function() {
				var $btn = $(this);
				var filterValue = $btn.data('ninjalytics-template-filter') || 'all';
				var isActive = (filterValue === activeFilter) || (activeFilter === 'all' && filterValue === 'all');
				if (isActive) {
					$btn.removeClass('berrypress-btn-secondary').addClass('berrypress-btn-primary');
				} else {
					$btn.removeClass('berrypress-btn-primary').addClass('berrypress-btn-secondary');
				}
			});
		}

		function applyTemplateFilters() {
			var query = ($searchInput.val() || '').toLowerCase();
			$templateCards.each(function() {
				var $card = $(this);
				var integrations = getIntegrationsList($card);
				var searchContent = ($card.data('ninjalytics-template-search') || '').toString();
				var matchesFilter = false;

				if (activeFilter === 'all' || activeFilter === 'none') {
					matchesFilter = true;
				} else if (!integrations.length) {
					matchesFilter = true;
				} else {
					matchesFilter = integrations.indexOf(activeFilter) !== -1;
				}

				var matchesSearch = !query.length || searchContent.indexOf(query) !== -1;

				updateCardState($card);
				$card.toggle(matchesFilter && matchesSearch);
			});

			// Hide section headers that no longer have any visible card,
			// and track which reporters still have visible content.
			var visibleReporters = {};
			$sectionHeaders.each(function() {
				var $header = $(this);
				var section = ($header.data('ninjalytics-template-section') || '').toString().split('|');
				var reporter = section[0];
				var type = section[1];
				if (!reporter || !type) {
					return;
				}
				var hasVisible = $templateCards.filter(
					'[data-ninjalytics-template-integrations="' + reporter + '"]' +
					'[data-ninjalytics-template-type="' + type + '"]'
				).filter(':visible').length > 0;
				$header.toggle(hasVisible);
				if (hasVisible) {
					visibleReporters[reporter] = true;
				}
			});

			// Drop the reporter prefix when it is not needed for disambiguation:
			// either the active filter already names the reporter, or only one
			// reporter has any visible section right now. The actual hiding is
			// done via CSS based on these two state markers on the grid.
			var multipleReportersVisible = Object.keys(visibleReporters).length > 1;
			$templateGrid
				.attr('data-ninjalytics-active-filter', activeFilter || '')
				.toggleClass('is-single-reporter', !multipleReportersVisible);
		}

		// Apply once on init so the server-rendered prefix state matches the
		// initial activeFilter (e.g. when only one reporter is active, the
		// "WooCommerce - " prefix should be hidden before the modal is opened).
		applyTemplateFilters();

		function openTemplateModal(filter, $trigger) {
			if (filter) {
				activeFilter = filter;
			}
			if ($searchInput.length) {
				$searchInput.val('');
			}
			openModalA11y($templateModal, $trigger);
			setFilterButtonState();
			applyTemplateFilters();
		}

		$('#ags-psr-template-modal').on('click', function(e) {
			e.preventDefault();
			openTemplateModal(undefined, $(this));
		});

		$(document).on('click', '.js-ninjalytics-modal-trigger', function(e) {
			e.preventDefault();
			var filter = $(this).data('ninjalytics-template-filter');
			openTemplateModal(filter.toString(), $(this));
		});

		$templateFilters.on('click', function() {
			activeFilter = $(this).data('ninjalytics-template-filter');
			setFilterButtonState();
			applyTemplateFilters();
		});

		$searchInput.on('input', function() {
			applyTemplateFilters();
		});
	})();

	// Report sidebar tabs (Settings / AI / Schedule) — optional selection; no panel when none selected.
	(function initNinjalyticsReportSidebarTabs() {
		var $tablist = $('#ninjalytics-report-sidebar-tabs[role="tablist"]');
		if (!$tablist.length) {
			return;
		}

		var $sidebar = $('#ninjalytics-report-sidebar');
		var $tabs = $tablist.find('[role="tab"]');

		function showPanel($panel) {
			if (!$panel.length) {
				return;
			}
			$panel.removeClass('berrypress-hidden').attr({ 'aria-hidden': 'false' }).prop('hidden', false);
		}

		function hidePanel($panel) {
			if (!$panel.length) {
				return;
			}
			$panel.addClass('berrypress-hidden').attr({ 'aria-hidden': 'true' }).prop('hidden', true);
		}

		function hideAllPanels() {
			$tabs.each(function() {
				var id = $(this).attr('aria-controls');
				if (id) {
					hidePanel($('#' + id));
				}
			});
		}

		function unselectTabs() {
			$tabs.each(function() {
				$(this)
					.attr({ 'aria-selected': 'false', 'tabindex': '-1' })
					.removeClass('ninjalytics-report-sidebar-tab-active');
			});
			$tabs.first().attr('tabindex', '0');
			hideAllPanels();
			$sidebar.removeClass('ninjalytics-report-sidebar--panel-open');
			syncSidebarSplitWidth();
		}

		function activateTab($tab) {
			if (!$tab || !$tab.length) {
				return;
			}
			var panelId = $tab.attr('aria-controls');
			var $panel = panelId ? $('#' + panelId) : $();

			$tabs.each(function() {
				var $eachTab = $(this);
				var isSelected = $eachTab[0] === $tab[0];
				$eachTab
					.attr({ 'aria-selected': isSelected ? 'true' : 'false', 'tabindex': isSelected ? '0' : '-1' })
					.toggleClass('ninjalytics-report-sidebar-tab-active', isSelected);
			});

			hideAllPanels();
			showPanel($panel);
			$sidebar.addClass('ninjalytics-report-sidebar--panel-open');
			syncSidebarSplitWidth();
		}

		function syncSidebarSplitWidth() {
			if (!window.ninjalyticsSettingsSplit) {
				return;
			}
			if ($sidebar.hasClass('ninjalytics-report-sidebar--panel-open')) {
				window.ninjalyticsSettingsSplit.expand();
			} else {
				window.ninjalyticsSettingsSplit.collapse();
			}
		}

		function toggleTab($tab) {
			if ($tab.attr('aria-selected') === 'true') {
				unselectTabs();
			} else {
				activateTab($tab);
			}
		}

		window.ninjalyticsReportSidebarTabs = {
			activate: function(tabId) {
				var $tab = tabId ? $('#' + tabId) : $();
				if ($tab.length) {
					activateTab($tab);
				}
			},
			unselect: unselectTabs,
			getSelectedTabId: function() {
				var $selected = $tabs.filter('[aria-selected="true"]');
				return $selected.length ? $selected.attr('id') : null;
			}
		};

		$tabs.on('click', function() {
			toggleTab($(this));
		});

		$tabs.on('keydown', function(e) {
			var index = $tabs.index(this);
			var next = -1;

			if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
				next = (index + 1) % $tabs.length;
			} else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
				next = (index - 1 + $tabs.length) % $tabs.length;
			} else if (e.key === 'Home') {
				next = 0;
			} else if (e.key === 'End') {
				next = $tabs.length - 1;
			} else if (e.key === 'Enter' || e.key === ' ') {
				e.preventDefault();
				toggleTab($(this));
				return;
			} else {
				return;
			}

			e.preventDefault();
			$tabs.eq(next).focus();
		});

		if ($tabs.filter('[aria-selected="true"]').length) {
			$sidebar.addClass('ninjalytics-report-sidebar--panel-open');
		} else {
			hideAllPanels();
		}
		// Split init runs later; defer width sync until expand/collapse API exists.
		$(function() {
			syncSidebarSplitWidth();
		});
	})();

	$('#ninjalytics-report-sidebar .ninjalytics-section-title').on('click', function(e) {
		if ($(e.target).closest('label, input').length) {
			return;
		}
		var $toggle = $(this).parent('.ninjalytics-settings-toggle');
		var isActive = $toggle.hasClass('ninjalytics-active');

		// Accordion: only one settings section open at a time in the side panel.
		$toggle.siblings('.ninjalytics-settings-toggle').removeClass('ninjalytics-active');
		$toggle.toggleClass('ninjalytics-active', !isActive);
	});

	$('#hm-psr-button-add-field').click(function() {
		var $fieldsSelect = $('#hm_psr_custom_field');
		var $selectedOption = $fieldsSelect.find('option:selected:first');
		var isOtherOption = $selectedOption.hasClass('hm-psr-select-other-option');
		if (isOtherOption) {
			var otherFieldName = $fieldsSelect.siblings('.hm-psr-select-other-field:first').val();
			if (!otherFieldName.length) {
				return;
			}
		}
		hm_psr_add_custom_field($selectedOption.val(), isOtherOption ? otherFieldName : $selectedOption.text());
		if (isOtherOption) {
			$fieldsSelect.val($fieldsSelect.find('option:first').val()).change();
		}
	});
	$('.hm_psr_variations_fld').change(function() {
		if ($(this).val() == 0) {
			$('#hm_psr_report_fields .hm_psr_variation_field input[type="checkbox"]').prop('checked', false).prop('disabled', true);
			$('#hm_psr_custom_field optgroup:first-child .hm_psr_variation_field').prop('disabled', true);
		} else {
			$('#hm_psr_report_fields .hm_psr_variation_field input[type="checkbox"]').prop('disabled', false);
			$('#hm_psr_custom_field optgroup:first-child .hm_psr_variation_field').prop('disabled', false);
		}
	});
	$('.hm_psr_variations_fld:checked').change();

	var preSortColumnOrder;
	$('#hm_psr_report_fields').sortable({
		start: function() {
			preSortColumnOrder = [];
			$('#hm_psr_report_fields input[name="fields[]"]').each(function() {
				preSortColumnOrder.push(this.value);
			});
		},
		update: function() {
			hm_psr_update_sort_options();

			var newOrder = [];
			$('#hm_psr_report_fields input[name="fields[]"]').each(function() {
				newOrder.push(preSortColumnOrder.indexOf(this.value));
			});

			if (window.ninjalytics_table) {
				window.hm_psr_freeze_field_order = true;
				try {
					ninjalytics_table.colReorder.order(newOrder);
				} catch (x) {}
				window.hm_psr_freeze_field_order = false;
			}

			if ($('.ninjalytics-display-options button[aria-pressed="true"]').data('display-mode') == 'chart') {
				ninjalytics_update_chart();
			}
		}
	}).on('input', '.hm_psr_field_name', function() {
		$(ninjalytics_table.table().header()).find('.dt-column-title').eq( $(this).closest('.ninjalytics-report-field').index() ).text(this.value);
		hm_psr_update_sort_options();
	});

	$('#hm_psr_field_groupby').change(function() {
		var $field = $(this);

		jQuery('#hm_psr_report_fields .hm_psr_groupby_field' + fieldNum).remove();

		if ($field.val() == '') {
			hm_psr_update_sort_options();
		} else {
			hm_psr_add_custom_field('builtin::groupby_field', $field.find('option:selected:first').text());
		}
	});

	$('#ninjalytics-date-range-dropdown .ninjalytics-alt-dates-toggle-btn').on('click', function(e) {
		e.preventDefault();
		var $altDates = $('#ninjalytics-date-range-dropdown .ninjalytics-date-range-dropdown-alt-dates');
		var $label = $('#ninjalytics-date-range-dropdown .ninjalytics-alt-dates-label');
		var isHidden = $altDates.hasClass('berrypress-hidden');
		$altDates.toggleClass('berrypress-hidden', !isHidden);
		$(this).text(isHidden ? 'Hide' : 'Change');
		if (!isHidden) {
		} else {
			$label.html('<span class="berrypress-fw-bold berrypress-text-primary">Advanced Options</span>');
		}
	});

	$('#hm_psr_field_include_totals').change(function() {
		if ($(this).is(':checked')) {
			$('.hm_psr_total_field').removeClass('berrypress-hidden');
		} else {
			$('.hm_psr_total_field').addClass('berrypress-hidden');
		}
	});

	$('#hm_psr_field_include_totals').change();

	$('#hm_psr_field_format_amounts').change(function() {
		if ($(this).is(':checked')) {
			$('.hm_psr_round_field').removeClass('berrypress-hidden');
		} else {
			$('.hm_psr_round_field').addClass('berrypress-hidden');
		}
	});
	$('#hm_psr_field_format_amounts').change();

	// Enable/disable custom segments
	$('#hm_psr_enable_custom_segments').change(function() {
		var isEnabled = $(this).is(':checked');

		if (!isEnabled) {
			jQuery('#hm_psr_report_fields .hm_psr_groupby_field, #hm_psr_report_fields .hm_psr_groupby_field2, #hm_psr_report_fields .hm_psr_groupby_field3, #hm_psr_report_fields .hm_psr_groupby_field4, #hm_psr_report_fields .hm_psr_groupby_field5').remove();
			hm_psr_update_sort_options();
		}
	});
	$('#hm_psr_enable_custom_segments').change();

	// Reset segment to None when X icon is clicked
	$('.ninjalytics-segment-reset').click(function() {
		var fieldName = $(this).data('field');
		$('#hm_psr_field_' + fieldName).val('').change();
	});
	hm_psr_update_sort_options();

	$('#ninjalytics-report-sidebar .hm-psr-select-other')
		.append($('<option>').html('Other...').addClass('hm-psr-select-other-option'))
		.closest('select')
		.change(function() {
			var $this = $(this);
			var $selectedOtherOption = $this.find('option.hm-psr-select-other-option:selected')
			if ($this.hasClass('hm-psr-select-other-selected')) {
				$this.removeClass('hm-psr-select-other-selected').siblings('.hm-psr-select-other-field').remove();
				$selectedOtherOption.html('Other...');
			}
			if ($selectedOtherOption.length) {
				var selectName = $this.attr('name');
				var $optGroup = $selectedOtherOption.closest('optgroup');
				var $otherField = $('<input>')
					.attr({placeholder: '(enter value)', type: 'text'})
					.addClass('hm-psr-select-other-field')
					.change(function() {
						var $this = $(this);
						var thisValue = $(this).val();
						var otherFieldPrefix = $optGroup.data('hm-psr-other-field-prefix');
						$selectedOtherOption.val((otherFieldPrefix ? otherFieldPrefix : '') + thisValue);
						
						// Special handling for the Group By field
						if ($this.siblings('#hm_psr_field_groupby').length) {
							$('#hm_psr_report_fields .hm_psr_groupby_field .hm_psr_field_name').val(thisValue);
						}
					});
				$selectedOtherOption.text(($optGroup.length ? $optGroup.attr('label') + ' - ' : '') + 'Other:');

				$this
					.addClass('hm-psr-select-other-selected')
					.after($otherField);
			}
		});
		
	$('.ags-psr-disable-product-grouping').change(function() {
		var state = parseInt($(this).val());
		var productFieldSelector = '#hm_psr_custom_field .hm-psr-product-field' + (state === 2 ? ':not([value="builtin::product_categories"])' : '');
		$('#hm_psr_custom_field .hm-psr-product-fields, ' + productFieldSelector).toggle(state < 1);
		if (state === 2) {
			$('#hm_psr_custom_field .hm-psr-product-field[value="builtin::product_categories"]').show();
		}
		if (state > 0) {
			$('#hm_psr_custom_field .hm-psr-product-fields option, ' + productFieldSelector).each(function() {
				$('#hm_psr_report_fields > div > input[value="' + this.value + '"]').parent().remove();
			});
			if ( $(productFieldSelector + ':selected:first, #hm_psr_custom_field .hm-psr-product-fields option:selected:first').length ) {
				$('#hm_psr_custom_field').val( $('#hm_psr_custom_field :not(.hm-psr-product-fields) option:not(.hm-psr-product-field):first').val() );
			}
		}
	}).first().change();

	function ninjalyticsFormatDebugSql(sql) {
		var normalized = String(sql).replace(/\s+/g, ' ').trim();
		if (!normalized) {
			return '';
		}
		var keywords = [
			'LEFT JOIN', 'RIGHT JOIN', 'INNER JOIN', 'GROUP BY', 'ORDER BY',
			'SELECT', 'FROM', 'WHERE', 'HAVING', 'LIMIT', 'UNION', 'AND', 'OR'
		];
		keywords.forEach(function(keyword) {
			var pattern = new RegExp('\\s*\\b' + keyword.replace(/\s+/g, '\\s+') + '\\b', 'gi');
			normalized = normalized.replace(pattern, '\n' + keyword.toUpperCase());
		});
		return normalized.replace(/\n(AND|OR)\b/gi, '\n  $1').trim();
	}

	function hm_psr_update_debug_sql_box(queries) {
		var $box = $('#ninjalytics-debug-sql-box');
		var $list = $('#ninjalytics-debug-sql-list');
		var $count = $('#ninjalytics-debug-sql-count');
		var labelTemplate = $box.data('query-label-template') || 'Query %d';

		if (queries && queries.length && $('#ninjalytics-enable-debug').prop('checked')) {
			$list.empty();
			queries.forEach(function(sql, index) {
				var $item = $('<div class="ninjalytics-debug-sql-query"/>');
				var $label = $('<div class="ninjalytics-debug-sql-query-label"/>')
					.text(labelTemplate.replace('%d', String(index + 1)));
				var $pre = $('<pre class="ninjalytics-debug-sql-query-code"/>')
					.text(ninjalyticsFormatDebugSql(sql));
				$item.append($label, $pre);
				$list.append($item);
			});
			$count.text('(' + queries.length + ')').prop('hidden', false);
			$box.removeClass('berrypress-hidden').attr('aria-hidden', 'false');
		} else {
			$list.empty();
			$count.text('').prop('hidden', true);
			$box.addClass('berrypress-hidden').attr('aria-hidden', 'true');
		}
	}

	ninjalytics_update_chart();
	$('#ninjalytics-form').on('change', ':input:not(.ninjalytics-no-update,.dt-input)', ninjalytics_update_chart);

	$('#ninjalytics-enable-debug').on('change', function() {
		if (!$(this).prop('checked')) {
			hm_psr_update_debug_sql_box(null);
		}
	});


	// Conditional setting visibility
	function getScopeFromInput($input) {
		var $group = $input.closest(".ninjalytics-switch-conditional-group");
		return $group.length ? $group : $input.closest(".ninjalytics-field-switch-conditional");
	}

	function refreshScope($scope) {
		// collect panels in this scope only
		var $panels = $scope.find("> .ninjalytics-field-child[data-toggle-panel]");
		if (!$panels.length) {
			$panels = $scope.find("> .ninjalytics-field-switch-conditional > .ninjalytics-field-child[data-toggle-panel]");
		}

		// hide all panels
		$panels.hide();

		// find active toggle
		var $active = $scope.find("input:checked[data-toggle-key]").first();
		if (!$active.length) return;

		// show matching panel
		var key = $active.attr("data-toggle-key");
		$panels.filter('[data-toggle-panel="' + key + '"]').first().show();
	}

	// handle both groups and single toggles
	$(document).on(
		"change click",
		".ninjalytics-switch-conditional-group input[type='radio'], .ninjalytics-switch-conditional-group input[type='checkbox'], input[data-toggle-key]",
		function () {
			refreshScope(getScopeFromInput($(this)));
		}
	);

	// Initial state: reuse existing change handler so toggle panels match saved checkboxes
	$('#ninjalytics-form input[data-toggle-key]:checked').trigger('change');

	// When a section is expanded or "Advanced" is checked, refresh toggle panels in that section
	$(document).on('click change', '#ninjalytics-settings', function(e) {
		var $toggle = (e.type === 'click' && $(e.target).closest('.ninjalytics-section-title').length && !$(e.target).closest('label').length)
			? $(e.target).closest('.ninjalytics-settings-toggle')
			: (e.type === 'change' && $(e.target).is('input.ninjalytics-no-update') ? $(e.target).closest('.ninjalytics-settings-toggle') : $());
		if (!$toggle.length) return;
		var $body = $toggle.find('.ninjalytics-section-body');
		setTimeout(function() {
			if (e.type === 'change' || $toggle.hasClass('ninjalytics-active')) {
				var seen = {};
				$body.find('input[data-toggle-key]').each(function() {
					var $scope = getScopeFromInput($(this));
					if ($scope.length && !seen[$scope[0]]) { seen[$scope[0]] = 1; refreshScope($scope); }
				});
			}
		}, 0);
	});

	$('#hm_sbp_field_orderby, #ninjalytics-orderdir').on('change', function() {
		if (window.ninjalytics_table) {
			try {
				ninjalytics_table.order([
					$('#hm_sbp_field_orderby > :selected').index(),
					$('#ninjalytics-orderdir').val()
				]);
				ninjalytics_table.draw();
			} catch (x) {}
			
		}
	});

	// Rename report by editing the breadcrumb label.
	var $breadcrumbReportName = $('#ninjalytics-breadcrumb-report-name');
	var $presetNameInput = $('input[name="preset_name"]');
	if ($breadcrumbReportName.length && $presetNameInput.length) {
		var syncBreadcrumbFromInput = function() {
			var inputValue = $presetNameInput.val() || '';
			if ($breadcrumbReportName.text() !== inputValue) {
				$breadcrumbReportName.text(inputValue);
			}
		};

		// Push raw breadcrumb text to the hidden input (no change event until commit).
		var syncInputFromBreadcrumb = function() {
			var text = ($breadcrumbReportName.text() || '').replace(/[\r\n\t]+/g, ' ');
			if ($presetNameInput.val() !== text) {
				$presetNameInput.val(text);
			}
		};

		var breadcrumbEditOriginal = '';
		var skipBreadcrumbCommitOnBlur = false;

		// On blur, normalize the visible text (trim) and push to the input.
		var commitBreadcrumb = function() {
			var text = ($breadcrumbReportName.text() || '').replace(/[\r\n\t]+/g, ' ').trim();
			if ($breadcrumbReportName.text() !== text) {
				$breadcrumbReportName.text(text);
			}
			if ($presetNameInput.val() !== text) {
				$presetNameInput.val(text).trigger('change');
			}
		};

		var isBreadcrumbEditing = function() {
			return $breadcrumbReportName.attr('contenteditable') === 'true';
		};

		var enableBreadcrumbEdit = function() {
			if (isBreadcrumbEditing()) {
				return;
			}
			breadcrumbEditOriginal = ($breadcrumbReportName.text() || '').replace(/[\r\n\t]+/g, ' ').trim();
			$breadcrumbReportName
				.attr('contenteditable', 'true')
				.addClass('is-editing');
		};

		var disableBreadcrumbEdit = function() {
			$breadcrumbReportName
				.attr('contenteditable', 'false')
				.removeClass('is-editing');
		};

		var selectBreadcrumbText = function() {
			var el = $breadcrumbReportName[0];
			if (!el) {
				return;
			}
			var range = document.createRange();
			range.selectNodeContents(el);
			var sel = window.getSelection();
			sel.removeAllRanges();
			sel.addRange(range);
		};

		syncBreadcrumbFromInput();

		$breadcrumbReportName.on('mousedown', function(e) {
			e.stopPropagation();
		});

		$breadcrumbReportName.on('click', function(e) {
			e.stopPropagation();
			enableBreadcrumbEdit();
			if (document.activeElement !== this) {
				this.focus();
			}
		});

		$breadcrumbReportName.on('focus', function() {
			enableBreadcrumbEdit();
			// Select all text on focus for easy replacement.
			selectBreadcrumbText();
		});

		$breadcrumbReportName.on('keydown', function(e) {
			// Enter confirms the rename.
			if (e.key === 'Enter' || e.keyCode === 13) {
				e.preventDefault();
				$breadcrumbReportName.trigger('blur');
				return;
			}
			// Escape cancels and restores the value from when editing started.
			if (e.key === 'Escape' || e.keyCode === 27) {
				e.preventDefault();
				skipBreadcrumbCommitOnBlur = true;
				$breadcrumbReportName.text(breadcrumbEditOriginal);
				$presetNameInput.val(breadcrumbEditOriginal);
				disableBreadcrumbEdit();
				$breadcrumbReportName.blur();
			}
		});

		$breadcrumbReportName.on('paste', function(e) {
			e.preventDefault();
			var text = '';
			if (e.originalEvent && e.originalEvent.clipboardData) {
				text = e.originalEvent.clipboardData.getData('text/plain');
			} else if (window.clipboardData) {
				text = window.clipboardData.getData('Text');
			}
			text = text.replace(/[\r\n\t]+/g, ' ');
			if (document.execCommand) {
				document.execCommand('insertText', false, text);
			} else {
				$breadcrumbReportName.text(($breadcrumbReportName.text() || '') + text);
			}
		});

		$breadcrumbReportName.on('input', syncInputFromBreadcrumb);
		$breadcrumbReportName.on('blur', function() {
			if (skipBreadcrumbCommitOnBlur) {
				skipBreadcrumbCommitOnBlur = false;
				return;
			}
			commitBreadcrumb();
			disableBreadcrumbEdit();
		});
		$presetNameInput.on('change input', function() {
			// Don't fight the user while they are editing the breadcrumb.
			if (document.activeElement === $breadcrumbReportName[0]) {
				return;
			}
			syncBreadcrumbFromInput();
		});
	}

	// Resizable split: report data vs settings panel (Split.js, viewport >= 1101px).
	(function initNinjalyticsSettingsSplit() {
		var $layoutRoot = $('#ninjalytics-settings-settings');
		var $splitHost = $layoutRoot.find('.ninjalytics-settings-split');
		if (!$layoutRoot.length || !$splitHost.length || typeof Split !== 'function') {
			return;
		}

		var dataEl = $splitHost.children('.ninjalytics-settings-data')[0];
		var sidebarEl = $splitHost.children('.ninjalytics-settings-sidebar')[0];
		var panelEl = sidebarEl ? sidebarEl.querySelector('.ninjalytics-settings-panel') : null;
		if (!dataEl || !sidebarEl || !panelEl) {
			return;
		}

		var storageKey = 'ninjalytics_settings_panel_width';
		var splitMq = window.matchMedia('(min-width: 1101px)');
		var splitInstance = null;
		var defaultPanelWidthPx = 380;
		var minPanelWidthPx = 320;
		var maxPanelWidthPx = 560;
		var minDataWidthPx = 280;
		// Must match .gutter.gutter-horizontal width in _settings-split.scss (8px → calc(N% - 4px) per pane).
		var splitGutterSize = 8;

		function clampPanelWidth(px) {
			return Math.max(minPanelWidthPx, Math.min(maxPanelWidthPx, Math.round(px)));
		}

		function readPanelWidthPx() {
			var hostWidth = $splitHost.width();
			try {
				var raw = localStorage.getItem(storageKey);
				if (raw !== null && raw !== '') {
					var px = parseInt(raw, 10);
					if (isFinite(px) && px > 0) {
						return clampPanelWidth(px);
					}
				}
			} catch (err) {}
			return defaultPanelWidthPx;
		}

		function measureSidebarTabsWidthPx() {
			var $tabsEl = $('#ninjalytics-report-sidebar-tabs');
			if (!$tabsEl.length) {
				return 52;
			}
			return $tabsEl.outerWidth();
		}

		function savePanelWidthPx() {
			try {
				var sidebarW = $(sidebarEl).outerWidth();
				var contentW = sidebarW - measureSidebarTabsWidthPx();
				localStorage.setItem(storageKey, String(clampPanelWidth(contentW)));
			} catch (err) {}
		}

		function sidebarTotalWidthPx(contentPx) {
			return measureSidebarTabsWidthPx() + clampPanelWidth(contentPx);
		}

		function panelWidthToSplitSizes(contentPx) {
			var hostWidth = $splitHost.width();
			if (!hostWidth) {
				return [65, 35];
			}
			var sidebarPx = sidebarTotalWidthPx(contentPx);
			var maxSidebarPx = hostWidth - splitGutterSize - minDataWidthPx;
			if (maxSidebarPx < sidebarTotalWidthPx(minPanelWidthPx)) {
				maxSidebarPx = sidebarTotalWidthPx(minPanelWidthPx);
			}
			sidebarPx = Math.min(sidebarPx, maxSidebarPx);
			var sidebarPercent = (sidebarPx / hostWidth) * 100;
			return [100 - sidebarPercent, sidebarPercent];
		}

		function hostHasWidth() {
			return $splitHost.width() > 0;
		}

		function createSplit() {
			if (splitInstance || !hostHasWidth()) {
				return;
			}
			$layoutRoot.addClass('ninjalytics-layout-enabled');
			var minSidebarPx = sidebarTotalWidthPx(minPanelWidthPx);
			splitInstance = Split([dataEl, sidebarEl], {
				sizes: panelWidthToSplitSizes(readPanelWidthPx()),
				minSize: [minDataWidthPx, minSidebarPx],
				gutterSize: splitGutterSize,
				snapOffset: 0,
				cursor: 'col-resize',
				onDragEnd: function() {
					savePanelWidthPx();
					if (typeof window.ninjalyticsAdjustReportTable === 'function') {
						window.ninjalyticsAdjustReportTable();
					}
				}
			});
			window.requestAnimationFrame(function() {
				if (typeof window.ninjalyticsAdjustReportTable === 'function') {
					window.ninjalyticsAdjustReportTable();
				}
			});
		}

		function destroySplit() {
			if (!splitInstance) {
				return;
			}
			splitInstance.destroy();
			splitInstance = null;
			$layoutRoot.removeClass('ninjalytics-layout-enabled');
		}

		function isSidebarCollapsed() {
			return $layoutRoot.hasClass('ninjalytics-sidebar-collapsed');
		}

		function collapsePanel() {
			$layoutRoot.addClass('ninjalytics-sidebar-collapsed');
			panelEl.style.display = 'none';
			if (splitMq.matches) {
				destroySplit();
			}
		}

		function expandPanel() {
			$layoutRoot.removeClass('ninjalytics-sidebar-collapsed');
			panelEl.style.display = '';
			if (!splitMq.matches) {
				return;
			}
			window.requestAnimationFrame(function() {
				if (!hostHasWidth() || isSidebarCollapsed()) {
					return;
				}
				createSplit();
				if (typeof window.ninjalyticsAdjustReportTable === 'function') {
					window.ninjalyticsAdjustReportTable();
				}
			});
		}

		function syncSplit() {
			if (!splitMq.matches) {
				destroySplit();
				return;
			}
			if (!hostHasWidth()) {
				return;
			}
			if (isSidebarCollapsed()) {
				destroySplit();
				return;
			}
			createSplit();
		}

		function scheduleSync() {
			window.requestAnimationFrame(function() {
				syncSplit();
			});
		}

		window.ninjalyticsSettingsSplit = {
			collapse: collapsePanel,
			expand: expandPanel,
			isCollapsed: isSidebarCollapsed
		};

		scheduleSync();
		$(window).on('load', scheduleSync);
		if (typeof splitMq.addEventListener === 'function') {
			splitMq.addEventListener('change', function() {
				if (splitMq.matches && !isSidebarCollapsed()) {
					scheduleSync();
				} else if (!splitMq.matches) {
					scheduleSync();
				} else {
					destroySplit();
				}
			});
		} else if (typeof splitMq.addListener === 'function') {
			splitMq.addListener(function() {
				if (splitMq.matches && !isSidebarCollapsed()) {
					scheduleSync();
				} else if (!splitMq.matches) {
					scheduleSync();
				} else {
					destroySplit();
				}
			});
		}
	})();
});

function hm_psr_add_custom_field(fieldId, fieldName) {
	var customFieldBox = jQuery('#hm_psr_report_fields > div.ninjalytics-report-field:last');
	if (!customFieldBox.length && window.ninjalytics_new_field) {
		customFieldBox = window.ninjalytics_new_field;
	}
	customFieldBox = customFieldBox.clone().removeClass('hm_psr_groupby_field hm_psr_groupby_field2 hm_psr_groupby_field3 hm_psr_groupby_field4 hm_psr_groupby_field5 hm_psr_variation_field ninjalytics-editable-field');
	customFieldBox.children('input[type="hidden"]').attr('value', fieldId);
	customFieldBox.children('input[type="text"]').attr('name', 'field_names[' + fieldId + ']').val(fieldName);
	delete window.ninjalytics_new_field;

	if (fieldId == 'builtin::groupby_field') {
		customFieldBox.addClass( 'hm_psr_groupby_field' + (fieldId == 'builtin::groupby_field' ? '' : fieldId[22]) );
	} else if (fieldId == 'builtin::variation_id' || fieldId == 'builtin::variation_sku' || fieldId == 'builtin::variation_attributes') {
		customFieldBox.addClass('hm_psr_variation_field');
	}
	customFieldBox.find('.hm_psr_total_field').toggleClass('no-total', jQuery('#hm_psr_custom_field option[value="' + fieldId.replace('"', '\\"') + '"]').hasClass('no-total-field')).children('input').prop('checked', false).attr('value', fieldId);
	customFieldBox.find('.hm_psr_round_field').toggleClass('no-round', jQuery('#hm_psr_custom_field option[value="' + fieldId.replace('"', '\\"') + '"]').hasClass('no-round-field')).children('input').prop('checked', false).attr('value', fieldId);
	customFieldBox.find('.hm_psr_chart_field').toggleClass('no-chart', jQuery('#hm_psr_custom_field option[value="' + fieldId.replace('"', '\\"') + '"]').hasClass('no-chart-field')).children('input').prop('checked', false).attr('value', fieldId);
	jQuery('#hm_psr_report_fields').append(customFieldBox);
	hm_psr_update_sort_options();
	jQuery("#hm_psr_report_fields").scrollTop(jQuery("#hm_psr_report_fields")[0].scrollHeight);

	ninjalytics_update_chart();
}

function ninjalytics_remove_field(btn) {
	var $field = jQuery(btn).parent();
	if ($field.hasClass('hm_psr_groupby_field')) {
		alert('This field is a grouping field. To remove this field, please change the Custom segment 1 setting in the Grouping & Sorting tab.');
		return;
	}
	if (!$field.siblings().length) {
		window.ninjalytics_new_field = $field;
	}
	$field.remove();
	hm_psr_update_sort_options();
	ninjalytics_update_chart();
}

function hm_psr_update_sort_options() {
	['#hm_sbp_field_orderby', '#hm_sbp_field_chart_series_name'].forEach(function(fieldSelector) {
		var $select = jQuery(fieldSelector);
		var currentValue = $select.val();
		$select.empty();

		jQuery('#hm_psr_report_fields > div').each(function() {
			var $field = jQuery(this);
			var fieldId = $field.find('input[type="hidden"]').val();
			jQuery('<option>')
				.attr('value', fieldId)
				.text($field.find('input[type="text"]').val())
				.attr('selected', fieldId == currentValue)
				.appendTo($select);
		});
	});
}
