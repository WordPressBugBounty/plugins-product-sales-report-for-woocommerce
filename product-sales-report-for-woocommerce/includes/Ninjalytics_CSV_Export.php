<?php
/**
 * Author:      BerryPress
 * License:     GNU General Public License version 3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.en.html
 */
if ( !defined( 'ABSPATH' ) ) {
	exit;
}
if (!class_exists('Ninjalytics_CSV_Export')) {
	class Ninjalytics_CSV_Export {

		private $handle, $delimiter, $surround, $escapeSearch, $escapeReplace, $outputBom = true;
		
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
			if ($this->outputBom) {
				require_once(__DIR__.'/../lib/League.Csv/ByteSequence.php');
				fwrite($this->handle, \League\Csv\ByteSequence::BOM_UTF8);
				$this->outputBom = false;
			}
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

if (!class_exists('Ninjalytics_CSV_ASCII_Export')) {
	class Ninjalytics_CSV_ASCII_Export extends Ninjalytics_CSV_Export {
		
		public function putRow($data, $header=false, $footer=false) {
			foreach ($data as $key => &$value)
				$value = mb_convert_encoding($value, 'ISO-8859-1');
			$this->outputBom = false;
			return parent::putRow($data, $header, $footer);
		}
	}
}