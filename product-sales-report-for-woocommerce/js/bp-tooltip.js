/**
 * BerryPress Tooltip Library
 * Minimalist vanilla JS tooltip system with lazy loading support
 * 
 * Part of Ninjalytics – GPL v3 or later
 * For licensing and copyright notices, please see ../license.txt.
 *
 */

(function() {
	'use strict';

	const Tooltip = {
		/**
		 * Sanitize HTML content to prevent XSS attacks
		 * Removes script tags, event handlers, and dangerous attributes
		 * 
		 * @param {string} html - HTML string to sanitize
		 * @return {string} Sanitized HTML string
		 */
		sanitizeHTML: function(html) {
			// Create temporary div for parsing
			const temp = document.createElement('div');
			temp.innerHTML = html;
			
			// Remove script tags and their content
			const scripts = temp.querySelectorAll('script, iframe, object, embed');
			scripts.forEach(el => el.remove());
			
			// Remove event handlers and dangerous attributes from all elements
			const allElements = temp.querySelectorAll('*');
			allElements.forEach(el => {
				// Remove all event handlers (onclick, onerror, etc.)
				Array.from(el.attributes).forEach(attr => {
					if (attr.name.startsWith('on') || 
						attr.name === 'javascript:' ||
						attr.name.toLowerCase().includes('expression')) {
						el.removeAttribute(attr.name);
					}
				});
				
				// Remove data: and javascript: URLs from href/src
				if (el.hasAttribute('href')) {
					const href = el.getAttribute('href');
					if (href && (href.startsWith('javascript:') || href.startsWith('data:'))) {
						el.removeAttribute('href');
					}
				}
				if (el.hasAttribute('src')) {
					const src = el.getAttribute('src');
					if (src && (src.startsWith('javascript:') || src.startsWith('data:'))) {
						el.removeAttribute('src');
					}
				}
			});
			
			return temp.innerHTML;
		},

		/**
		 * Adjust tooltip position to keep it within viewport
		 * 
		 * @param {string} position - Preferred position (top, bottom, left, right)
		 * @param {Object} tooltipPos - Current tooltip position {top, left}
		 * @param {Object} tooltipSize - Tooltip dimensions {width, height}
		 * @param {Object} triggerPos - Trigger element position and size
		 * @param {Object} viewport - Viewport dimensions and scroll
		 * @param {number} padding - Distance from trigger
		 * @param {number} viewportPadding - Distance from viewport edge
		 * @return {Object} Adjusted position {position, top, left}
		 */
		adjustPosition: function(position, tooltipPos, tooltipSize, triggerPos, viewport, padding, viewportPadding) {
			let adjustedPos = { position, top: tooltipPos.top, left: tooltipPos.left };
			
			// Check if tooltip fits in viewport for current position
			const fits = {
				top: tooltipPos.top >= viewport.scrollTop + viewportPadding &&
				     tooltipPos.top + tooltipSize.height <= viewport.scrollTop + viewport.viewportHeight - viewportPadding,
				bottom: tooltipPos.top >= viewport.scrollTop + viewportPadding &&
				        tooltipPos.top + tooltipSize.height <= viewport.scrollTop + viewport.viewportHeight - viewportPadding,
				left: tooltipPos.left >= viewport.scrollLeft + viewportPadding &&
				      tooltipPos.left + tooltipSize.width <= viewport.scrollLeft + viewport.viewportWidth - viewportPadding,
				right: tooltipPos.left >= viewport.scrollLeft + viewportPadding &&
				       tooltipPos.left + tooltipSize.width <= viewport.scrollLeft + viewport.viewportWidth - viewportPadding
			};
			
			const horizontalFits = tooltipPos.left >= viewport.scrollLeft + viewportPadding &&
			                       tooltipPos.left + tooltipSize.width <= viewport.scrollLeft + viewport.viewportWidth - viewportPadding;
			const verticalFits = tooltipPos.top >= viewport.scrollTop + viewportPadding &&
			                    tooltipPos.top + tooltipSize.height <= viewport.scrollTop + viewport.viewportHeight - viewportPadding;
			
			// Adjust horizontal position for top/bottom tooltips
			if (position === 'top' || position === 'bottom') {
				if (!horizontalFits) {
					if (tooltipPos.left < viewport.scrollLeft + viewportPadding) {
						adjustedPos.left = viewport.scrollLeft + viewportPadding;
					} else if (tooltipPos.left + tooltipSize.width > viewport.scrollLeft + viewport.viewportWidth - viewportPadding) {
						adjustedPos.left = viewport.scrollLeft + viewport.viewportWidth - tooltipSize.width - viewportPadding;
					}
				}
				
				// Try alternative vertical position if doesn't fit
				if (!fits[position]) {
					const altPosition = position === 'top' ? 'bottom' : 'top';
					const altTop = altPosition === 'top' 
						? triggerPos.top - tooltipSize.height - padding
						: triggerPos.bottom + padding;
					
					const altFits = altTop >= viewport.scrollTop + viewportPadding &&
					                altTop + tooltipSize.height <= viewport.scrollTop + viewport.viewportHeight - viewportPadding;
					
					if (altFits) {
						adjustedPos.position = altPosition;
						adjustedPos.top = altTop;
					} else {
						// If neither fits, center vertically in viewport
						adjustedPos.top = Math.max(
							viewport.scrollTop + viewportPadding,
							Math.min(
								viewport.scrollTop + viewport.viewportHeight - tooltipSize.height - viewportPadding,
								tooltipPos.top
							)
						);
					}
				}
			}
			
			// Adjust vertical position for left/right tooltips
			if (position === 'left' || position === 'right') {
				if (!verticalFits) {
					if (tooltipPos.top < viewport.scrollTop + viewportPadding) {
						adjustedPos.top = viewport.scrollTop + viewportPadding;
					} else if (tooltipPos.top + tooltipSize.height > viewport.scrollTop + viewport.viewportHeight - viewportPadding) {
						adjustedPos.top = viewport.scrollTop + viewport.viewportHeight - tooltipSize.height - viewportPadding;
					}
				}
				
				// Try alternative horizontal position if doesn't fit
				if (!fits[position]) {
					const altPosition = position === 'left' ? 'right' : 'left';
					const altLeft = altPosition === 'left'
						? triggerPos.left - tooltipSize.width - padding
						: triggerPos.right + padding;
					
					const altFits = altLeft >= viewport.scrollLeft + viewportPadding &&
					                altLeft + tooltipSize.width <= viewport.scrollLeft + viewport.viewportWidth - viewportPadding;
					
					if (altFits) {
						adjustedPos.position = altPosition;
						adjustedPos.left = altLeft;
					} else {
						// If neither fits, center horizontally in viewport
						adjustedPos.left = Math.max(
							viewport.scrollLeft + viewportPadding,
							Math.min(
								viewport.scrollLeft + viewport.viewportWidth - tooltipSize.width - viewportPadding,
								tooltipPos.left
							)
						);
					}
				}
			}
			
			return adjustedPos;
		},

		positionArrow: function(tooltip, triggerPos, tooltipRect, position) {
			const arrow = tooltip;

			if (position === 'top' || position === 'bottom') {
				const triggerCenter = triggerPos.left + (triggerPos.width / 2);
				const tooltipCenter = tooltipRect.left + (tooltipRect.width / 2);
				const offset = triggerCenter - tooltipCenter;

				tooltip.style.setProperty('--bp-arrow-offset', offset + 'px');
			} else {
				const triggerCenter = triggerPos.top + (triggerPos.height / 2);
				const tooltipCenter = tooltipRect.top + (tooltipRect.height / 2);
				const offset = triggerCenter - tooltipCenter;

				tooltip.style.setProperty('--bp-arrow-offset', offset + 'px');
			}
		},

		init: function() {
			// Find all tooltip triggers (both data-bp-tooltip and data-bp-tooltip-id)
			const triggers = document.querySelectorAll('[data-bp-tooltip], [data-bp-tooltip-id], [data-bp-tooltip-html]');
			
			if (triggers.length === 0) return;

			triggers.forEach(trigger => {
				// Get tooltip content
				let tooltipContent = '';
				
				// Check for HTML content attribute
				if (trigger.hasAttribute('data-bp-tooltip-html')) {
					tooltipContent = trigger.getAttribute('data-bp-tooltip-html');
				} 
				// Check for element ID reference
				else if (trigger.hasAttribute('data-bp-tooltip-id')) {
					const sourceId = trigger.getAttribute('data-bp-tooltip-id');
					const sourceEl = document.getElementById(sourceId);
					if (sourceEl) {
						tooltipContent = sourceEl.innerHTML;
					}
				}
				// Default: plain text
				else {
					tooltipContent = trigger.getAttribute('data-bp-tooltip') || '';
				}
				
				// Create tooltip element
				const tooltip = document.createElement('div');
				const tooltipId = 'bp-tooltip-' + Math.random().toString(36).slice(2);
				tooltip.id = tooltipId;
				tooltip.className = 'bp-tooltip';
				tooltip.setAttribute('role', 'tooltip');
				
				// Set content (HTML or text)
				if (trigger.hasAttribute('data-bp-tooltip-html') || trigger.hasAttribute('data-bp-tooltip-id')) {
					// Sanitize HTML content to prevent XSS
					tooltipContent = Tooltip.sanitizeHTML(tooltipContent);
					tooltip.innerHTML = tooltipContent;
				} else {
					tooltip.textContent = tooltipContent;
				}
				
				document.body.appendChild(tooltip);

				// Position tooltip
				let timeout;
				
				const showTooltip = (e) => {
					clearTimeout(timeout);
					
					// Get preferred position from attribute or default to 'top'
					const preferredPosition = (trigger.getAttribute('data-bp-tooltip-position') || 'top').toLowerCase();
					
					// First, make tooltip have dimensions (but invisible) to calculate position
					tooltip.style.display = 'block';
					tooltip.style.visibility = 'hidden';
					tooltip.style.opacity = '0';
					
					const rect = trigger.getBoundingClientRect();
					const tooltipRect = tooltip.getBoundingClientRect();
					const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
					const scrollLeft = window.pageXOffset || document.documentElement.scrollLeft;
					const padding = 8; // Distance from trigger element
					const viewportPadding = 8; // Distance from viewport edges
					
					// Calculate position based on preferred position
					let position = preferredPosition;
					let top = 0;
					let left = 0;
					
					// Calculate initial position
					switch (preferredPosition) {
						case 'top':
							top = rect.top + scrollTop - tooltipRect.height - padding;
							left = rect.left + scrollLeft + (rect.width / 2) - (tooltipRect.width / 2);
							break;
						case 'bottom':
							top = rect.bottom + scrollTop + padding;
							left = rect.left + scrollLeft + (rect.width / 2) - (tooltipRect.width / 2);
							break;
						case 'left':
							top = rect.top + scrollTop + (rect.height / 2) - (tooltipRect.height / 2);
							left = rect.left + scrollLeft - tooltipRect.width - padding;
							break;
						case 'right':
							top = rect.top + scrollTop + (rect.height / 2) - (tooltipRect.height / 2);
							left = rect.right + scrollLeft + padding;
							break;
						default:
							position = 'top';
							top = rect.top + scrollTop - tooltipRect.height - padding;
							left = rect.left + scrollLeft + (rect.width / 2) - (tooltipRect.width / 2);
					}
					
					// Auto-adjust position if tooltip goes outside viewport
					const adjusted = Tooltip.adjustPosition(
						position,
						{ top, left },
						{ width: tooltipRect.width, height: tooltipRect.height },
						{ 
							top: rect.top + scrollTop, 
							bottom: rect.bottom + scrollTop,
							left: rect.left + scrollLeft,
							right: rect.right + scrollLeft,
							width: rect.width,
							height: rect.height
						},
						{
							viewportWidth: window.innerWidth,
							viewportHeight: window.innerHeight,
							scrollTop,
							scrollLeft
						},
						padding,
						viewportPadding
					);
					
					position = adjusted.position;
					top = adjusted.top;
					left = adjusted.left;
					
					// Apply position class
					tooltip.className = 'bp-tooltip bp-tooltip--' + position;
					
					tooltip.style.top = top + 'px';
					tooltip.style.left = left + 'px';

					// Move arrow
					Tooltip.positionArrow(
						tooltip,
						{
							top: rect.top + scrollTop,
							bottom: rect.bottom + scrollTop,
							left: rect.left + scrollLeft,
							right: rect.right + scrollLeft,
							width: rect.width,
							height: rect.height
						},
						{
							left: left,
							top: top,
							width: tooltipRect.width,
							height: tooltipRect.height
						},
						position
					);


					// Now show with animation
					setTimeout(() => {
						tooltip.style.visibility = 'visible';
						tooltip.style.opacity = '';
						tooltip.classList.add('is-visible');
					}, 10);
					
					// Update aria
					trigger.setAttribute('aria-describedby', tooltipId);
				};

				const hideTooltip = () => {
					timeout = setTimeout(() => {
						tooltip.classList.remove('is-visible');
						tooltip.style.visibility = 'hidden';
						trigger.removeAttribute('aria-describedby');
					}, 100);
				};

				// Events
				trigger.addEventListener('mouseenter', showTooltip);
				trigger.addEventListener('mouseleave', hideTooltip);
				trigger.addEventListener('focus', showTooltip);
				trigger.addEventListener('blur', hideTooltip);
				
				// Cleanup on scroll/resize
				window.addEventListener('scroll', hideTooltip, true);
				window.addEventListener('resize', hideTooltip);
			});
		}
	};

	// Initialize when DOM is ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', Tooltip.init);
	} else {
		Tooltip.init();
	}

	// Expose globally
	window.BPTooltip = Tooltip;
})();

