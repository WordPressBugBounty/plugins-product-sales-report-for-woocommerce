jQuery(document).ready(function($) {
	var hm_psr_table_search = null;
	
	$('#ags-psr-dates-desc').on('focus', function() {
		$('#ags-psr-date-range-dropdown').removeClass('hm-psr-hidden');
	});
	$(document.body).on('click', function(ev) {
		if (!$(ev.target).closest('#ags-psr-date-range').length) {
			$('#ags-psr-date-range-dropdown').addClass('hm-psr-hidden');
		}
	});
	$('.ags-psr-modal-close').on('click', function() {
		$(this).closest('.ags-psr-modal').removeClass('ags-psr-active');
	});
	$('.ags-psr-tooltip-modal > a').on('click', function() {
		$(this).next().addClass('ags-psr-active');
		return false;
	});
	$('#ags-psr-template-modal').on('click', function() {
		$('.ags-psr-modal-templates').addClass('ags-psr-active');
		return false;
	});
	
	$('#ninjalytics-settings .ags-psr-section-title').on('click', function(e) {
		if ($(e.target).closest('label').length) {
			return;
		}
		$(this).parent().toggleClass('hm-psr-active');
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
		hm_psr_update_sort_options();
		
		if ($field.val() != '') {
			hm_psr_add_custom_field('builtin::groupby_field', $field.find('option:selected:first').text());
		}
	});
	
	$('#hm_psr_field_include_totals').change(function() {
		$('.hm_psr_total_field').toggle( $(this).is(':checked') );
	});
	$('#hm_psr_field_include_totals').change();
	
	$('#hm_psr_field_format_amounts').change(function() {
		$('.hm_psr_round_field').toggle( $(this).is(':checked') );
	});
	$('#hm_psr_field_format_amounts').change();
	
	// Enable/disable custom segments
	$('#hm_psr_enable_custom_segments').change(function() {
		var isEnabled = $(this).is(':checked');
		var $segments = $('.ninjalytics-custom-segment');
		
		if (isEnabled) {
			// Add any grouping fields to the report
			$('#hm_psr_field_groupby, #hm_psr_field_groupby2, #hm_psr_field_groupby3, #hm_psr_field_groupby4, #hm_psr_field_groupby5').trigger('change');
			
			$segments.slideDown(300);
		} else {
			// Remove any grouping fields from the report
			jQuery('#hm_psr_report_fields .hm_psr_groupby_field, #hm_psr_report_fields .hm_psr_groupby_field2, #hm_psr_report_fields .hm_psr_groupby_field3, #hm_psr_report_fields .hm_psr_groupby_field4, #hm_psr_report_fields .hm_psr_groupby_field5').remove();
			hm_psr_update_sort_options();
			
			$segments.slideUp(300);
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
	
	var dataSeq = 0;
	function hm_psr_get_chart_data(callback) {
		var thisDataSeq = ++dataSeq;
		var request = $('#ninjalytics-form').serializeArray();
		var chartSeriesName, chartType, fields = [], allFieldNames = {}, mode = $('#ags-psr-display-mode :checked').val(), showHeader = false, showTotals = false;
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
			$('#hm_psr_output_container').removeClass('hm-psr-loading');
			$('#hm-psr-chart-no-fields').removeClass('hm-psr-hidden');
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
		
		request.push({name: 'ninjalytics_action', value: 'run'});
		
		var targetRequestLength = 10;
		var $loader = $('#hm_psr_output_container .hm_psr_output_loading progress').val('');
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
						$('#ags-psr-dates-desc').val( meta.datesDesc );
						var dateMode = $('.ags-psr-date-range-tabs :checked').val();
						if (dateMode === 'basic' || dateMode === 'dynamic') {
							$('#ags-psr-date-range-' + dateMode + ' p').each(function(i) {
								$(this).text( i ? meta.endDate : meta.startDate );
							});
						}
					}
					if (mode === 'chart') {
						var requestDuration = (Date.now() - requestStart) / 1000;
						var labels = ajax.getResponseHeader('X-Psr-Chart-Labels');
						labels = labels ? labels.split('|') : [''];
						
						for (var i = 0; i < labels.length; ++i) {
							var dataPoints = {};
							for (var j = 0; j < response[i].length; ++j) {
								dataPoints[response[i][j][0]] = response[i][j].slice(1);
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

	var hm_psr_chart, hm_psr_table;

	window.ninjalytics_update_chart = function() {
		if (hm_psr_chart) {
			hm_psr_chart.destroy();
		}
		if (hm_psr_table) {
			hm_psr_table_search = hm_psr_table.search();
			hm_psr_table.destroy(true);
			$('#hm_psr_output_container h2').remove();
		} else {
			hm_psr_table_search = null;
		}
		var mode = $('#ags-psr-display-mode :checked').val();
		$('#hm-psr-chart-no-fields').addClass('hm-psr-hidden');
		$('#hm_psr_output_container').removeClass('hm-psr-output-chart hm-psr-output-table').addClass('hm-psr-loading hm-psr-output-' + mode).show();
		hm_psr_get_chart_data( mode == 'chart' ? hm_psr_build_chart : hm_psr_build_table );
	}
	ninjalytics_update_chart();
	$('#ninjalytics-form').on('change', ':input:not(.ags-psr-no-update,.dt-input)', ninjalytics_update_chart);
	
	
	function hm_psr_build_table(data, fieldNames, showHeader, showTotals, reportTitle) {
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
		
		if (showTotals) {
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
		
		
		$('#hm_psr_output_container')
			.append(reportTitle ? $('<h2>').text(reportTitle) : '', $table)
			.removeClass('hm-psr-loading')
			.find('.hm_psr_output_loading progress').val('');
		
		hm_psr_table = $table.DataTable({
			pageLength:25,
			order:[],
			colReorder:true,
			fixedHeader:true,
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

	function hm_psr_build_chart(data, fieldNames, showHeader, showTotals, reportTitle) {
		var $chart = $('#hm_psr_chart');
		
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
		$chart.parent().removeClass('hm-psr-loading').find('.hm_psr_output_loading progress').val('');
	}
	
	
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
	customFieldBox.children('.hm_psr_total_field').toggleClass('no-total', jQuery('#hm_psr_custom_field option[value="' + fieldId.replace('"', '\\"') + '"]').hasClass('no-total-field')).children('input').prop('checked', false).attr('value', fieldId);
	customFieldBox.children('.hm_psr_round_field').toggleClass('no-round', jQuery('#hm_psr_custom_field option[value="' + fieldId.replace('"', '\\"') + '"]').hasClass('no-round-field')).children('input').prop('checked', false).attr('value', fieldId);
	customFieldBox.children('.hm_psr_chart_field').children('input').prop('checked', false).attr('value', fieldId);
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
