/*!
This file is part of Ninjalytics. For licensing and copyright notices, please see ../license.txt.
*/
jQuery(document).ready(function($) {
	var hm_psr_table_search = null, hm_psr_chart, hm_psr_table;

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
						failure();
						return undefined;
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
		if (hm_psr_chart) {
			hm_psr_chart.destroy();
		}
		if (hm_psr_table) {
			hm_psr_table_search = hm_psr_table.search();
			hm_psr_table.destroy(true);
			// $('#ninjalytics_output_container h2').remove();
		} else {
			hm_psr_table_search = null;
		}
		var mode = $('.ninjalytics-display-options button[aria-pressed="true"]').data('display-mode');
		$('#ninjalytics-chart-no-fields').addClass('berrypress-hidden');
		$('#ninjalytics_output_container').removeClass('ninjalytics-output-chart ninjalytics-output-table').addClass('ninjalytics-output-loading ninjalytics-output-' + mode).show();
		hm_psr_get_chart_data( mode == 'chart' ? hm_psr_build_chart : hm_psr_build_table );
	}

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
		}
	});
	$('.berrypress-modal-close').on('click', function() {
		$(this).closest('.berrypress-modal').removeClass('berrypress-active');
		if ($(this).closest('.ninjalytics-templates').length) {
			$('#ninjalytics-template-search').val('');
		}
	});

	(function() {
		var $templateModal = $('.ninjalytics-modal-templates');
		if (!$templateModal.length) {
			return;
		}
		var $templateCards = $templateModal.find('[data-ninjalytics-template-card="true"]');
		var $templateFilters = $templateModal.find('.js-ninjalytics-template-filter');
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
		}

		function openTemplateModal(filter) {
			if (filter) {
				activeFilter = filter;
			}
			if ($searchInput.length) {
				$searchInput.val('');
			}
			$templateModal.addClass('berrypress-active');
			setFilterButtonState();
			applyTemplateFilters();
		}

		$('#ags-psr-template-modal').on('click', function(e) {
			e.preventDefault();
			openTemplateModal();
		});

		$(document).on('click', '.js-ninjalytics-modal-trigger', function(e) {
			e.preventDefault();
			var filter = $(this).data('ninjalytics-template-filter');
			openTemplateModal(filter.toString());
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

	$('#ninjalytics-settings .ninjalytics-section-title').on('click', function(e) {
		if ($(e.target).closest('label').length) {
			return;
		}
		$(this).parent().toggleClass('ninjalytics-active');
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
	$('#hm_psr_field_groupby').change(function() {
		var $field = $(this);
		
		jQuery('#hm_psr_report_fields .hm_psr_groupby_field').remove();
		
		if ($field.val() == '') {
			hm_psr_update_sort_options();
		} else {
			hm_psr_add_custom_field('builtin::groupby_field', $field.find('option:selected:first').text());
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

	$('#ninjalytics-settings .hm-psr-select-other')
		.append($('<option>').html('Other...').prop('disabled', true).addClass('hm-psr-select-other-option'))
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
	
	var dataSeq = 0;
	function hm_psr_get_chart_data(callback) {
		var thisDataSeq = ++dataSeq;
		var request = $('#ninjalytics-form').serializeArray();
		var chartSeriesName, chartType, fields = [], allFieldNames = {}, mode = $('.ninjalytics-display-options button[aria-pressed="true"]').data('display-mode'), showHeader = false, showTotals = false;
		request = request.map(function(field) {
			if (field.name.substring(0, 12) == 'field_names[' && field.name[field.name.length - 1] == ']') {
				allFieldNames[ field.name.substring(12, field.name.length - 1) ] = field.value;
			}
			switch(field.name) {
				case 'include_header':
					showHeader = field.value;
					break;
				case 'include_totals':
					showTotals = field.value;
					break;
				case 'fields[]':
					if (mode === 'chart') {
						return {};
					}
					fields.push(field.value);
					break;
				case 'chart_fields[]':
					if (mode === 'chart') {
						field.name = 'fields[]';
						fields.push(field.value);
						break;
					}
				case 'chart_type':
					if (mode === 'chart') {
						chartType = field.value;
						break;
					}
				case 'total_fields[]':
					if (mode === 'chart') {
						return {};
					}
					break;
				case 'chart_series_name':
					if (mode === 'chart') {
						chartSeriesName = field.value;
						// no break
					}
				case 'email_to':
				case 'format':
					return {};
			}
			return field;
		});

		if (!fields.length) {
			$('#ninjalytics_output_container').removeClass('ninjalytics-output-loading');
			$('#ninjalytics-chart-no-fields').removeClass('berrypress-hidden');
			return;
		}

		request.push({name: 'format', value: (mode === 'chart' && chartType == 'line_totals') ? 'json-totals' : 'json'});
		if (mode === 'chart') {
			if (chartType == 'line_totals') {
				request = request.concat(fields.map(function(field) {
					return {name: 'total_fields[]', value: field};
				}));
				request.push({name: 'include_totals', value: 1});
				showTotals = 1;
			}
			request.unshift({name: 'fields[]', value: chartSeriesName});
			fields.unshift(chartSeriesName);

			request.push({name: '_chart', value: 1});
		}

		var fieldNames = fields.map(function(field) {
			return allFieldNames[field];
		});

		request.push({name: 'ninjalytics_action_free', value: 'run'});

		var targetRequestLength = 10;
		var $loader = $('#ninjalytics_output_container .ninjalytics-loading progress').val('');
		var reportTitle = null;

		var data = {};

		function buildData(batchStart, batchSize) {

			var headers = {
				'X-Psr-Chart-Run-Start': batchStart,
				'X-Psr-Chart-Run-Count': batchSize
			};
			var requestStart = Date.now();
			$.post({
				url: location.href,
				data: request.filter( function (item) { return item.name; } ),
				headers: headers,
				success: function(response, s, ajax) {
					response = extractJsonComments(response);
					var responseComments = response[1];
					if (responseComments.debugSql) {
						responseComments.debugSql.forEach(function(sqlLine) {
							console.log('Report SQL: ' + sqlLine);
						});
					}
					response = JSON.parse(response[0]);
					var meta = ajax.getResponseHeader('X-Psr-Meta');
					if (meta) {
						meta = JSON.parse(meta);
						if (meta.title) {
							reportTitle = meta.title;
						}
						$('#ninjalytics-dates-desc').val( meta.datesDesc );
						var dateMode = $('.ninjalytics-date-range-tabs :checked').val();
						if (dateMode === 'basic' || dateMode === 'dynamic') {
							$('#ninjalytics-date-range-' + dateMode + ' p').each(function(i) {
								$(this).text( i ? meta.endDate : meta.startDate );
							});
						}
					}
					if (mode === 'chart') {
						var requestDuration = (Date.now() - requestStart) / 1000;
						var labels = ajax.getResponseHeader('X-Psr-Chart-Labels');
						labels = labels ? labels.split('|') : [''];
						
						$('#ninjalytics-chart-duplicate-series').addClass('berrypress-hidden');

						for (var i = 0; i < labels.length; ++i) {
							var dataPoints = {};
							for (var j = 0; j < response[i].length; ++j) {
								var seriesValue = chartType == 'line_totals' ? 'TOTALS' : response[i][j][0];
								if (dataPoints.hasOwnProperty(seriesValue)) {
									$('#ninjalytics-chart-duplicate-series').removeClass('berrypress-hidden');
								} else {
									dataPoints[seriesValue] = response[i][j].slice(1);
								}
							}
							data[ labels[i] ] = dataPoints;
						}

						var runsRemaining = parseInt(ajax.getResponseHeader('X-Psr-Chart-Run-Remaining'));

					} else {
						var runsRemaining = 0;
						data = response;
					}

					if (thisDataSeq === dataSeq) {
						if (runsRemaining) {
							$loader.val( (batchStart + batchSize) / (batchStart + batchSize + runsRemaining) * 99 );
							buildData(batchStart + batchSize, Math.min(runsRemaining, Math.max(1, Math.floor(targetRequestLength / requestDuration))));
						} else {
							$loader.val(99);
							callback(data, fieldNames, showHeader, showTotals, reportTitle);
						}
					}
				},
				dataType: 'text'
			});
		}

		if (thisDataSeq === dataSeq) {
			buildData(1, 1);
		}

	}

	function extractJsonComments(str) {
		var comments = {};
		return [
			str.replaceAll(/\/\*(.+?)\:(.*?)\*\//g, function (substr, key, value) {
				if (!comments[key]) {
					comments[key] = [];
				}
				comments[key].push( JSON.parse(value) );
				return '';
			}),
			comments
		];
	}

	ninjalytics_update_chart();
	$('#ninjalytics-form').on('change', ':input:not(.ninjalytics-no-update,.dt-input)', ninjalytics_update_chart);


	function hm_psr_build_table(data, fieldNames, showHeader, showTotals, reportTitle) {
		// // Destroy existing table instance if it exists
		// if (hm_psr_table) {
		// 	hm_psr_table_search = hm_psr_table.search();
		// 	hm_psr_table.destroy(true);
		// 	hm_psr_table = null;
		// }

		var $table = $('<table>').append(
			$('<thead>').append(
				$('<tr>').append(
					fieldNames.map(function(fieldName) {
						return $('<th>').text(showHeader ? fieldName : '');
					})
				)
			),
			$('<tbody>').append(
				(showTotals ? data.slice(0,  -1) : data).map(function(row) {
					return $('<tr>').append(
						row.map(function(cell) {
							return $('<td>').text(cell);
						})
					);
				})
			)
		);

		if (showTotals && data.length) {
			$table.append(
				$('<tfoot>').append(
					$('<tr>').append(
						data[data.length - 1].map(function(cell) {
							return $('<td>').text(cell);
						})
					)
				)
			);
		}


		$('#ninjalytics_output_container')
			.append(reportTitle ? $('<h2>').text(reportTitle) : '', $table)
			.removeClass('ninjalytics-output-loading')
			.find('.ninjalytics-loading progress').val('');

		hm_psr_table = $table.DataTable({
			pageLength:25,
			order:[],
			colReorder:true,
			responsive:true,
			select:true,
			language: {
				lengthMenu: 'Show: _MENU_',
				searchPlaceholder: 'Search',
				search: '_INPUT_'
			},
			layout: {
				topStart: 'search',
				topEnd: 'pageLength',
			}
		});

		if (hm_psr_table_search !== null) {
			hm_psr_table.search(hm_psr_table_search);
			hm_psr_table.draw();
		}
	}

	function hm_psr_is_empty_chart(data) {
		for (var entry in data) {
			if (Object.values(data[entry]).length) {
				return false;
			}
		}
		return true;
	}

	function hm_psr_build_chart(data, fieldNames, showHeader, showTotals, reportTitle) {
		var $chart = $('#hm_psr_chart');
		$chart.next('.ninjalytics-chart-empty').remove();
		if (hm_psr_is_empty_chart(data)) {
			$chart.after( $('<p>').addClass('ninjalytics-chart-empty').text('No data') );
		} else {

			var multiDataset = false, multiSeries = true;
			switch ( $('#hm_psr_chart_type :radio:checked').val() ) {
				case 'bar':
					var chartType = 'bar';
					break;
				case 'pie':
					var chartType = 'pie';
					break;
				case 'line_totals':
					multiSeries = false;
				// no break
				case 'line_series':
					multiDataset = true;
				// no break
				default:
					var chartType = 'line';
			}

			if (!multiDataset) {
				data = Object.values(data)[0];
			}

			var chartData = {
				labels: Object.keys(data),
				datasets: []
			};

			if (multiDataset) {
				var chartSeries = {};
				for (var label in data) {
					for (var series in data[label]) {
						chartSeries[series] = [];
					}
				}

				for (label in data) {
					for (series in chartSeries) {
						chartSeries[series].push(data[label][series] ? data[label][series] : []);
					}
				}

				for (series in chartSeries) {
					for (var i = 1; i < fieldNames.length; ++i) {
						chartData.datasets.push({
							data: chartSeries[series].map(function(values) {
								return values[i - 1] ? values[i - 1] : 0;
							}),
							label: (multiSeries ? series + ' - ' : '') + fieldNames[i]
						});
					}
				}

			} else {
				for (var i = 1; i < fieldNames.length; ++i) {
					chartData.datasets.push(
						{
							label: fieldNames[i],
							data: Object.values(data).map(function(value) {
								return value[i - 1];
							})
						}
					);
				}
			}

			var chartParams = {
				type: chartType,
				data: chartData,
				options: {
					plugins: {
						title: {
							display: !(!reportTitle),
							text: reportTitle
						}
					}
				}
			};

			console.log(chartParams);

			hm_psr_chart = new Chart($chart[0], chartParams);
		}

		$chart.parent().removeClass('ninjalytics-output-loading').find('.ninjalytics-loading progress').val('');
	}

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

});

function hm_psr_add_custom_field(fieldId, fieldName) {
	var customFieldBox = jQuery('#hm_psr_report_fields > div:last').clone().removeClass('hm_psr_groupby_field hm_psr_variation_field');
	customFieldBox.children('input[type="hidden"]').attr('value', fieldId);
	customFieldBox.children('input[type="text"]').attr('name', 'field_names[' + fieldId + ']').val(fieldName);

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
