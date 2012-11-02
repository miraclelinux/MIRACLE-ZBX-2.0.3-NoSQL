<?php

define("HGL_SUCCESS", 0);
define("HISTORY_GLUON_SORT_ASCENDING",  0);

define("HISTORY_GLUON_SEC_START",  0);
define("HISTORY_GLUON_SEC_END",    0x7fffffff);

define("HISTORY_GLUON_DATA_KEY_ARRAY_ARRAY",      "array");
define("HISTORY_GLUON_DATA_KEY_SEC",    "sec");
define("HISTORY_GLUON_DATA_KEY_VALUE",  "value");

define("HISTORY_GLUON_NUM_ENTRIES_UNLIMITED",  0);

class HistoryGluon
{
	// ------------------------------------------------------------------
	// private member
	// ------------------------------------------------------------------
	private static $instance = null;
	private static $ctx = null;

	// ------------------------------------------------------------------
	// public methods
	// ------------------------------------------------------------------
	public static function getInstance() {
		if (is_null($instance))
			$instance = new HistoryGluon();
		return $instance;
	}

	public function getHistory($itemid, $from_time, $to_time) {
		$sec0 = $from_time;
		if ($from_time == 0)
			$sec0 = HISTORY_GLUON_SEC_START;
		$sec1 = $to_time;
		if ($to_time == 0)
			$sec1 = HISTORY_SEC_END;
		$sortRequest = HISTORY_GLUON_SORT_ASCENDING;
		$numMaxEntries = HISTORY_GLUON_NUM_ENTRIES_UNLIMITED;
		$array = null;
		$ret = history_gluon_range_query($this->ctx, $itemid,
		                                 $sec0, 0, $sec1, 0,
		                                 $sortRequest, $numMaxEntries, $array);
		if ($ret != $HGL_SUCCESS) {
			error_log("Failed to call history_gluon_range_query: " . $ret);
			return null;
		}
		return $array;
	}

	public function calcStatisticalParam(&$data_arr, $amp, $ofs, $duration) {
		$row_arr = array();
		$i_idx_map = array();

		$idx = 0;
		// enumerate 
		foreach ($data_arr[HISTORY_GLUON_DATA_KEY_ARRAY_ARRAY] as $key => $data) {
			$clock = $data[HISTORY_GLUON_DATA_KEY_SEC];
			$value = $data[HISTORY_GLUON_DATA_KEY_VALUE];
			$i = round($amp * (($clock + $ofs) % $duration) / $duration);

			if (!isset($i_idx_map[$i])) {
				$row_arr[$idx]['i'] = $i;
				$row_arr[$idx]['count'] = 1;
				$row_arr[$idx]['min'] = $value;
				$row_arr[$idx]['max'] = $value;
				$row_arr[$idx]['avg'] = $value;
				$row_arr[$idx]['clock'] = $clock;
				$i_idx_map[$i] = $idx;
				$idx++;
			} else {
				$odx = $i_idx_map[$i];
				$row_arr[$odx]['count']++;
				if ($value < $row_arr[$odx]['min'])
					$row_arr[$odx]['min'] = $value;
				if ($value > $row_arr[$odx]['max'])
					$row_arr[$odx]['max'] = $value;
				if ($clock > $row_arr[$odx]['clock'])
					$row_arr[$odx]['odx'] = $clock;
				$row_arr[$odx]['avg'] += $value;
			}
		}

		// calculate average if needed
		foreach ($row_arr as $idx => $row) {
			if ($row_arr[$idx]['count'] == 1)
				continue;
			$row_arr[$idx]['avg'] /= $row_arr[$idx]['count'];
		}

		return $row_arr;
	}

	// ------------------------------------------------------------------
	// private methods
	// ------------------------------------------------------------------
	private function __construct() {
		if (!extension_loaded('History Gluon PHP Extension'))
			dl("history_gluon.so");
		$this->ctx = history_gluon_create_context();
	}
}
