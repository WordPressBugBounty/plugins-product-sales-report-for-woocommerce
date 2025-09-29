<?php
/**
 * Author:      BerryPress
 * License:     GNU General Public License version 3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.en.html
 */
if (!class_exists('Ninjalytics_CSV_Export')) {
	class Ninjalytics_CSV_Export {

		private $handle, $delimiter, $surround, $escapeSearch, $escapeReplace;
		
		public function __construct($handle, $options=array()) {
			$this->handle = $handle;
			$this->delimiter = (isset($options['delimiter']) ? $options['delimiter'] : ',');
			$this->surround = (isset($options['surround']) ? $options['surround'] : '"');
			if (!empty($this->surround) && (!isset($options['escape']) || !empty($options['escape']))) {
				$escape = (isset($options['escape']) ? $options['escape'] : '\\');
				$this->escapeSearch = array($escape, $this->surround);
				$this->escapeReplace = array($escape.$escape, $escape.$this->surround);
			}
		}
		
		public function putTitle($title) {
			$this->putRow(array($title));
		}
		
		public function putRow($data, $header=false, $footer=false) {
			$row = '';
			foreach ($data as $field) {
				$row .= (empty($row) ? '' : $this->delimiter).$this->surround.(empty($this->escapeSearch) ? $field : str_replace($this->escapeSearch, $this->escapeReplace, $field)).$this->surround;
			}
// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- No equivalent function in WP_Filesystem
			fwrite($this->handle, $row."\n");
		}
		
		public function close() { }
	}
}
?>