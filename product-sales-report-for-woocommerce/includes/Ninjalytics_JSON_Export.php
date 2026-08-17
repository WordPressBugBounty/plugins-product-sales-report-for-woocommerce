<?php
/**
 * Author:      BerryPress
 * License:     GNU General Public License version 3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.en.html
 */
if ( !defined( 'ABSPATH' ) ) {
	exit;
}
if (!class_exists('Ninjalytics_JSON_Export')) {
	class Ninjalytics_JSON_Export {

		private $handle, $isTotals, $isFirstRow = true, $debugSql = [];
		
		public function __construct($handle, $isTotals=false) {
			$this->handle = $handle;
			$this->isTotals = (bool) $isTotals;
// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- No equivalent function in WP_Filesystem
			fwrite($this->handle, "[\n");
		}
		
		public function putTitle($title) {
			
		}
		
		public function putDebugSql($sql) {
			$this->debugSql[] = $sql;
		}
		
		public function writeDebugSql() {
			if ($this->debugSql) {
				foreach ($this->debugSql as $sqlLine) {
// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- No equivalent function in WP_Filesystem
					fwrite($this->handle, '/*debugSql:'.wp_json_encode($sqlLine).'*/');
				}
				$this->debugSql = [];
			}
		}
		
		public function putRow($data, $header=false, $footer=false) {
			if (!$header && (!$this->isTotals || $footer)) {
				$this->writeDebugSql();
				foreach ($data as &$field) {
					$field = $field ?? '';
				}
// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- No equivalent function in WP_Filesystem
				fwrite($this->handle, ($this->isFirstRow ? '' : ",\n")."\t".wp_json_encode($data));
				$this->isFirstRow = false;
			}
		}
		
		public function close() {
			$this->writeDebugSql();
// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- No equivalent function in WP_Filesystem
			fwrite($this->handle, "\n]\n");
		}
	}
}
?>
