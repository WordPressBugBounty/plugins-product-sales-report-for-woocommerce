/*!
This file is part of Ninjalytics. For licensing and copyright notices, please see ../license.txt.
*/
jQuery(document).ready(function($) {
	
	var dataSeq = 0;
	window.hm_psr_get_chart_data = function($container, mode, callback, request, debugSqlCallback) {
		var thisDataSeq = ++dataSeq;
		$container.attr('data-ninjalytics-data-seq', thisDataSeq).addClass('ninjalytics-output-loading');
															   
		request = request || $('#ninjalytics-form').serializeArray();
		var sortOrder = [0, 'asc'];
		var chartSeriesName, chartType, fields = [], allFieldNames = {}, showHeader = false, showTotals = false;
		request = request.map(function(field) {
			if (field.name.substring(0, 12) == 'field_names[' && field.name[field.name.length - 1] == ']') {
				allFieldNames[ field.name.substring(12, field.name.length - 1) ] = field.value;
			}
			switch(field.name) {
				case 'include_header':
					showHeader = parseInt(field.value);
					break;
				case 'include_totals':
					showTotals = parseInt(field.value);
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
				case 'format':
					return {};
				case 'orderby':
					sortOrder[0] = field.value;
					return field;
				case 'orderdir':
					sortOrder[1] = field.value;
					return field;
			}
			return field;
		});

		if (sortOrder[0]) {
			sortOrder[0] = fields.indexOf(sortOrder[0]);
		}

		if (!sortOrder[0] || sortOrder[0] == -1) {
			sortOrder[0] = 0;
		}

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

		if ($('#ninjalytyics-display-mode').length) {
			request.push({name: 'display_mode', value: mode});
		}

		var fieldNames = fields.map(function(field) {
			return allFieldNames[field];
		});

		request.push({name: 'ninjalytics_action_free', value: 'run'});

		var targetRequestLength = 10;
		var $loader = $container.find('.ninjalytics-loading progress').val('');
		var reportTitle = null;

		var data = {};
		var debugSqlLog = [];
		debugSqlCallback && debugSqlCallback();

		function buildData(batchStart, batchSize) {

			var headers = {
				'X-Psr-Chart-Run-Start': batchStart,
				'X-Psr-Chart-Run-Count': batchSize
			};
			var requestStart = Date.now();
			$.post({
				url: 'admin.php?page=ninjalytics-free',
				data: request.filter( function (item) { return item.name; } ),
				headers: headers,
				success: function(response, s, ajax) {
					response = extractJsonComments(response);
					var responseComments = response[1];
					if (debugSqlCallback && responseComments.debugSql) {
						responseComments.debugSql.forEach(function(sqlLine) {
							debugSqlLog.push(sqlLine);
						});
						debugSqlCallback(debugSqlLog);
					}
					response = JSON.parse(response[0]);
					var meta = ajax.getResponseHeader('X-Psr-Meta');
					if (meta) {
						meta = JSON.parse(meta);
						if (meta.title) {
							reportTitle = meta.title;
						}
						$('#ninjalytics-dates-desc').text( meta.datesDesc );
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

					if (thisDataSeq == $container.attr('data-ninjalytics-data-seq')) {
						if (runsRemaining) {
							$loader.val( (batchStart + batchSize) / (batchStart + batchSize + runsRemaining) * 99 );
							buildData(batchStart + batchSize, Math.min(runsRemaining, Math.max(1, Math.floor(targetRequestLength / requestDuration))));
						} else {
							$loader.val(99);
							callback($container, data, chartType, fields, fieldNames, showHeader, showTotals, reportTitle, sortOrder);
						}
					}
				},
				dataType: 'text'
			}).fail(function() {
				alert('Something went wrong while running the report. Your site\'s PHP memory limit or timeout may be too low for the amount of data needing to be processed for this report, or something else may be malfunctioning. Try running the report on a smaller date range in case it is a memory or timeout issue, or enable debug mode and check your server\'s error log for clues. Contact BerryPress support for assistance in troubleshooting this issue.');
			});
		}

		if (thisDataSeq == $container.attr('data-ninjalytics-data-seq')) {
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

	window.ninjalyticsAdjustReportTable = function() {
		if (window.ninjalytics_table && typeof ninjalytics_table.columns === 'function') {
			try {
				ninjalytics_table.columns.adjust();
				if (ninjalytics_table.responsive && typeof ninjalytics_table.responsive.recalc === 'function') {
					ninjalytics_table.responsive.recalc();
				}
				ninjalytics_table.draw(false);
			} catch (e) { /* noop */ }
		}
	};

	$('#ninjalytics-preview-cells-multiline').on('change', function() {
		var previewCellsMultiline = $(this).prop('checked');
		$('#ninjalytics_output_container').toggleClass('ninjalytics-preview-nowrap', !previewCellsMultiline);
		window.ninjalyticsAdjustReportTable();
	});

	window.hm_psr_build_table = function($container, data, chartType, fields, fieldNames, showHeader, showTotals, reportTitle, reportSort) {
		// // Destroy existing table instance if it exists
		// if (ninjalytics_table) {
		// 	ninjalytics_table_search = ninjalytics_table.search();
		// 	ninjalytics_table.destroy(true);
		// 	ninjalytics_table = null;
		// }

		var orderIdFieldIndex = fields.indexOf('builtin::order_id');
		var orderParentFieldIndex = fields.indexOf('builtin::order_parent');
		var orderStatusFieldIndex = fields.indexOf('builtin::order_status');
		var productIdFieldIndex = fields.indexOf('builtin::product_id');
		var variationIdFieldIndex = fields.indexOf('builtin::variation_id');
		var productImageFieldIndex = fields.indexOf('builtin::product_image');
		var productNameFieldIndex = fields.indexOf('builtin::product_name');
		var orderItemNameFieldIndex = fields.indexOf('builtin::order_item_name');

		function trimmedNumericPostId(value) {
			if (value === null || value === undefined) {
				return null;
			}
			var s = String(value).trim();
			if (s !== '' && /^\d+$/.test(s)) {
				return s;
			}
			return null;
		}

		/**
		 * Post ID for wp-admin product edit URL. Some rows fail a plain ^\d+$ check on Product ID:
		 * grouped reports (comma-separated IDs), whitespace, or missing product_id when variation_id is set.
		 */
		function resolveProductEditPostId(row) {
			var fromProduct = trimmedNumericPostId(
				productIdFieldIndex !== -1 ? row[productIdFieldIndex] : null
			);
			if (fromProduct && fromProduct !== '0') {
				return fromProduct;
			}
			if (productIdFieldIndex !== -1) {
				var concat = String(row[productIdFieldIndex] != null ? row[productIdFieldIndex] : '').trim();
				var parts = concat.split(',').map(trimmedNumericPostId);
				if (parts.length && parts.every(Boolean)) {
					return parts[0] !== '0' ? parts[0] : null;
				}
			}
			return null;
		}
		var reporterId = $('[name="_reporter"]').val();
		var orderEditUrlTemplate = (ninjalyticsConfig && ninjalyticsConfig.orderEditUrlTemplates)
			? ninjalyticsConfig.orderEditUrlTemplates[reporterId]
			: null;
		var productEditUrlTemplate = (ninjalyticsConfig && ninjalyticsConfig.productEditUrlTemplate)
			? ninjalyticsConfig.productEditUrlTemplate
			: null;
		var linkOrderIdInPreview = !!(ninjalyticsConfig && ninjalyticsConfig.linkOrderIdInPreview);
		var linkProductIdInPreview = !!(ninjalyticsConfig && ninjalyticsConfig.linkProductIdInPreview);
		var linkProductNameInPreview = !!(ninjalyticsConfig && ninjalyticsConfig.linkProductNameInPreview);
		var styleOrderStatusInPreview = !!(ninjalyticsConfig && ninjalyticsConfig.styleOrderStatusInPreview);
		var highlightNegativeValuesInPreview = !!(ninjalyticsConfig && ninjalyticsConfig.highlightNegativeValuesInPreview);

		/** Parsed cell text; only used on td.dt-type-numeric after DataTables assigns column types. */
		function previewNumericCellIsNegative(raw) {
			if (raw === null || raw === undefined || raw === '') {
				return false;
			}
			if (typeof raw === 'number') {
				return isFinite(raw) && raw < 0;
			}
			var s = String(raw).trim();
			if (!s.length) {
				return false;
			}
			if (/^\(.*\)$/s.test(s) && /\d/.test(s)) {
				return true;
			}
			var t = s.replace(/[\u00a0\s]/g, '').replace(/,/g, '');
			var digitPos = t.search(/\d/);
			if (digitPos === -1) {
				return false;
			}
			var prefixHasMinus = /[\-\u2212−]/.test(t.slice(0, digitPos));
			var numMatch = t.slice(digitPos).match(/^\d+(?:[.,]\d+)?/);
			if (!numMatch) {
				return false;
			}
			var n = parseFloat(numMatch[0].replace(',', '.'));
			if (isNaN(n) || !isFinite(n)) {
				return false;
			}
			return prefixHasMinus ? n > 0 : n < 0;
		}

		function ninjalyticsApplyPreviewNegativeToNumericCells(dtApi) {
			if (!highlightNegativeValuesInPreview || !dtApi) {
				return;
			}
			dtApi.$('td.dt-type-numeric, tfoot td.dt-type-numeric').each(function() {
				var $td = $(this);
				$td.toggleClass('ninjalytics-preview-negative-value', previewNumericCellIsNegative($td.text()));
			});
		}
		var $previewMultilineCheckbox = $('#ninjalytics-preview-cells-multiline');
		var previewCellsMultiline = $previewMultilineCheckbox.length ? $previewMultilineCheckbox.prop('checked') : true;

		function fieldClassName(fieldId) {
			return 'ninjalytics-col-' + String(fieldId || '')
				.toLowerCase()
				.replace(/[^a-z0-9]+/g, '-')
				.replace(/^-+|-+$/g, '');
		}

		var $table = $('<table>').append(
			$('<thead>').append(
				$('<tr>').append(
					fieldNames.map(function(fieldName, fieldIndex) {
						var fieldId = fields[fieldIndex];
						return $('<th>')
							.attr('data-order-sequence', '["asc","desc"]')
							.attr('data-ninjalytics-field', fieldId)
							.addClass(fieldClassName(fieldId))
							.text(showHeader ? fieldName : '');
					})
				)
			),
			$('<tbody>').append(
				(showTotals ? data.slice(0,  -1) : data).map(function(row) {
					return $('<tr>').append(
						row.map(function(cell, cellIndex) {
							var fieldId = fields[cellIndex];
							var $cell = $('<td>')
								.attr('data-ninjalytics-field', fieldId)
								.addClass(fieldClassName(fieldId));
							var productEditPostId = resolveProductEditPostId(row);
							if (
								linkOrderIdInPreview
								&& orderEditUrlTemplate
								&& orderIdFieldIndex !== -1
								&& cellIndex === orderIdFieldIndex
								&& /^\d+$/.test(String(cell))
							) {
								/* Refunds are separate WC orders (e.g. shop_order_refund): edit URL must use the paid order ID (parent), not the refund record ID — when Parent order is on the row and non-zero. */
								var orderLinkTargetId = String(cell).trim();
								var parentForOrderLink = orderParentFieldIndex !== -1 ? trimmedNumericPostId(row[orderParentFieldIndex]) : null;
								if (parentForOrderLink && parentForOrderLink !== '0') {
									orderLinkTargetId = parentForOrderLink;
								}
								var orderEditUrl = orderEditUrlTemplate.replace('%d', orderLinkTargetId);
								return $cell.append($('<a>').attr({
									href: orderEditUrl,
									target: '_blank',
									rel: 'noopener noreferrer',
									class: 'berrypress-link'
								}).text(cell));
							}
							var parentOrderLinkId = trimmedNumericPostId(cell);
							if (
								linkOrderIdInPreview
								&& orderEditUrlTemplate
								&& orderParentFieldIndex !== -1
								&& cellIndex === orderParentFieldIndex
								&& parentOrderLinkId
								&& parentOrderLinkId !== '0'
							) {
								var parentOrderEditUrl = orderEditUrlTemplate.replace('%d', parentOrderLinkId);
								return $cell.append($('<a>').attr({
									href: parentOrderEditUrl,
									target: '_blank',
									rel: 'noopener noreferrer',
									class: 'berrypress-link'
								}).text(cell));
							}
							if (
								styleOrderStatusInPreview
								&& orderStatusFieldIndex !== -1
								&& cellIndex === orderStatusFieldIndex
								&& String(cell).trim().length
							) {
								var statusValueRaw = String(cell).trim();
								var statusClass = statusValueRaw
									.toLowerCase()
									.replace(/^wc-/, '')
									.replace(/_/g, '-')
									.replace(/\s+/g, '-')
									.replace(/[^a-z0-9-]/g, '');
								var statusLabel = statusValueRaw
									.replace(/^wc-/, '')
									.replace(/[_-]+/g, ' ')
									.replace(/\b\w/g, function(match) { return match.toUpperCase(); });
								return $cell.append(
									$('<mark>')
										.addClass('ninjalytics-order-status ninjalytics-status-' + statusClass)
										.text(statusLabel)
								);
							}
							if (
								productImageFieldIndex !== -1
								&& cellIndex === productImageFieldIndex
								&& String(cell).trim().length
							) {
								var imageUrl = String(cell).split(',')[0].trim();
								if (imageUrl) {
									var $thumb = $('<img>')
										.addClass('ninjalytics-product-image-thumb')
										.attr({
											src: imageUrl,
											alt: '',
											loading: 'lazy'
										});
									if (productEditUrlTemplate && productEditPostId) {
										var productImageEditUrl = productEditUrlTemplate.replace('%d', productEditPostId);
										return $cell.append($('<a>').attr({
											href: productImageEditUrl,
											target: '_blank',
											rel: 'noopener noreferrer',
											class: 'berrypress-link'
										}).append($thumb));
									}
									return $cell.append($thumb);
								}
							}
							if (
								productEditPostId
								&& productEditUrlTemplate
								&& (
									(linkProductIdInPreview && productIdFieldIndex !== -1 && cellIndex === productIdFieldIndex)
									|| (linkProductNameInPreview && (
										(productNameFieldIndex !== -1 && cellIndex === productNameFieldIndex)
										|| (orderItemNameFieldIndex !== -1 && cellIndex === orderItemNameFieldIndex)
									))
								)
							) {
								var productEditUrl = productEditUrlTemplate.replace('%d', productEditPostId);
								return $cell.append($('<a>').attr({
									href: productEditUrl,
									target: '_blank',
									rel: 'noopener noreferrer',
									class: 'berrypress-link'
								}).text(cell));
							}
							return $cell.text(cell);
						})
					);
				})
			)
		).on('order.dt', function(a, b, order) {
			if (order.length) {
				$('#hm_sbp_field_orderby').val( $('#hm_sbp_field_orderby > :eq(' + order[0].col + ')').val() );
				$('#ninjalytics-orderdir').val( order[0].dir );
			}
		}).on('column-reorder.dt', function(a, b, order) {
			if (window.hm_psr_freeze_field_order) {
				return;
			}
			var $c = $('#hm_psr_report_fields').children();
			var $move = order.from.map( function(i) { return $c.eq(i); } );
			if (order.to > order.from[0]) {
				$c.eq(order.to).after($move);
			} else if (order.to < $c.length) {
				$c.eq(order.to).before($move);
			}
		});

		if (showTotals && data.length) {
			$table.append(
				$('<tfoot>').append(
					$('<tr>').append(
						data[data.length - 1].map(function(cell, cellIndex) {
							var footerFieldId = fields[cellIndex];
							var $footerCell = $('<td>')
								.attr('data-ninjalytics-field', footerFieldId)
								.addClass(fieldClassName(footerFieldId))
								.text(cell);
							return $footerCell;
						})
					)
				)
			);
		}


		$container
			.append(reportTitle ? $('<h2>').text(reportTitle) : '', $table)
			.removeClass('ninjalytics-output-loading')
			.toggleClass('ninjalytics-preview-nowrap', !previewCellsMultiline)
			.find('.ninjalytics-loading progress').val('');
		
		var responsiveOption = true;
		if (
			typeof $.fn.dataTable !== 'undefined'
			&& $.fn.dataTable.Responsive
			&& $.fn.dataTable.Responsive.display
			&& $.fn.dataTable.Responsive.renderer
			&& typeof $.fn.dataTable.Responsive.renderer.listHiddenNodes === 'function'
		) {
			responsiveOption = {
				details: {
					display: $.fn.dataTable.Responsive.display.childRow,
					type: 'inline',
					renderer: $.fn.dataTable.Responsive.renderer.listHiddenNodes()
				}
			};
		}
		
		var numCells = [];
		data.map(function(row) {
			row.map(function(cell, i) {
				if (typeof numCells[i] == 'undefined' || numCells[i] == 1) {
					numCells[i] = isNaN(parseFloat(cell)) ? 0 : 1;
				}
			});
		});
		
		var numCellIndexes = [];
		numCells.forEach( function(isNum, columnIndex) {
			if (isNum) {
				numCellIndexes.push(columnIndex);
			}
		} );
		
		var tableConfig = {
			pageLength: 25,
			order:[reportSort],
			colReorder:true,
			responsive: responsiveOption,
			select:true,
			initComplete: function(settings) {
				ninjalyticsApplyPreviewNegativeToNumericCells(new $.fn.dataTable.Api(settings));
			},
			drawCallback: function(settings) {
				ninjalyticsApplyPreviewNegativeToNumericCells(new $.fn.dataTable.Api(settings));
			},
			language: {
				lengthMenu: 'Show: _MENU_',
				searchPlaceholder: 'Search',
				search: '_INPUT_'
			},
			layout: {
				topStart: 'search',
				topEnd: 'pageLength',
			}
		};
		
		if (numCellIndexes.length) {
			tableConfig.columnDefs = [{
				targets: numCellIndexes, type: 'num'
			}];
		}

		window.ninjalytics_table = $table.DataTable(tableConfig);

		$container.show();
		if (window.ninjalytics_table_search !== null) {
			ninjalytics_table.search(window.ninjalytics_table_search);
			ninjalytics_table.draw();
		}

		window.requestAnimationFrame(function() {
			window.requestAnimationFrame(window.ninjalyticsAdjustReportTable);
		});
	}

	function hm_psr_is_empty_chart(data) {
		for (var entry in data) {
			if (Object.values(data[entry]).length) {
				return false;
			}
		}
		return true;
	}

	window.hm_psr_build_chart = function($container, data, chartType, fields, fieldNames, showHeader, showTotals, reportTitle, reportSort) {
		var $chart = $container.find('canvas');
		$chart.next('.ninjalytics-chart-empty').remove();

		// Destroy existing chart instance if it exists
		// if (ninjalytics_chart) {
		// 	ninjalytics_chart.destroy();
		// 	ninjalytics_chart = null;
		// }
		
		$container.show();
		if (hm_psr_is_empty_chart(data)) {
			$chart.after( $('<p>').addClass('ninjalytics-chart-empty').text('No data') );
		} else {

			var multiDataset = false, multiSeries = true;
			switch ( chartType ) {
				case 'line_totals':
					multiSeries = false;
				// no break
				case 'line_series':
					multiDataset = true;
				// no break
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
				type: (chartType == 'pie' || chartType == 'bar') ? chartType : 'line',
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

			window.ninjalytics_chart = new Chart($chart[0], chartParams);
		}

		$chart.parent().removeClass('ninjalytics-output-loading').find('.ninjalytics-loading progress').val('');
	}
});

