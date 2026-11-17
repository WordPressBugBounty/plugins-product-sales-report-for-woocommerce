<?php
/**
 * Author:      BerryPress
 * License:     GNU General Public License version 3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.en.html
 *
 * Minimal inline HTML table output, used only when a report is rendered
 * directly into a page (e.g. by Frontend Reports for WooCommerce).
 * Not offered as a download format.
 */
if ( !defined( 'ABSPATH' ) ) {
	exit;
}
if (!class_exists('Ninjalytics_HTML_Table_Export')) {
	class Ninjalytics_HTML_Table_Export {

		private $handle, $inTable = false, $hasTHead = false, $inTBody = false;

		public function __construct($handle) {
			$this->handle = $handle;
		}

		public function putTitle($title) {
			if (!$this->inTable) {
// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- No equivalent function in WP_Filesystem
				fwrite($this->handle, '<h3>'.esc_html($title).'</h3>');
			}
		}

		public function putRow($data, $header=false, $footer=false) {
			$html = '';
			if (!$this->inTable) {
				$html .= '<table>';
				$this->inTable = true;
			}

			if ($header && !$this->hasTHead) {
				$html .= '<thead>';
			} else if ($footer) {
				if ($this->inTBody) {
					$html .= '</tbody>';
					$this->inTBody = false;
				}
				$html .= '<tfoot>';
			} else if (!$this->inTBody) {
				$html .= '<tbody>';
				$this->inTBody = true;
			}

			$cellTag = $header ? 'th' : 'td';
			$html .= '<tr>';
			foreach ($data as $field) {
				$html .= '<'.$cellTag.'>'.esc_html($field).'</'.$cellTag.'>';
			}
			$html .= '</tr>';

			if ($header && !$this->hasTHead) {
				$html .= '</thead>';
				$this->hasTHead = true;
			} else if ($footer) {
				$html .= '</tfoot>';
			}

// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- No equivalent function in WP_Filesystem
			fwrite($this->handle, $html);
		}

		public function close() {
			$html = '';
			if (!$this->inTable) {
				$html .= '<table>';
			}
			if ($this->inTBody) {
				$html .= '</tbody>';
			}
// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- No equivalent function in WP_Filesystem
			fwrite($this->handle, $html.'</table>');
		}
	}
}
