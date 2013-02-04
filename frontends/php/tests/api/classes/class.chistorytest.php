<?php
require_once dirname(__FILE__).'/../../../include/classes/core/ZBase.php';
require_once dirname(__FILE__).'/../../../include/classes/api/API.php';
require_once dirname(__FILE__).'/../../../include/classes/api/CZBXAPI.php';
require_once dirname(__FILE__).'/../../../include/history-gluon.inc.php';
require_once dirname(__FILE__).'/../../../api/classes/CTriggerGeneral.php';
require_once dirname(__FILE__).'/../../../api/classes/CGraphGeneral.php';
require_once dirname(__FILE__).'/../../../api/classes/CHostGeneral.php';
require_once dirname(__FILE__).'/../../include/class.capitest.php';

class Z extends ZBase {
	public static function getRootDir() {
		return realpath(dirname(__FILE__).'/../../../');
	}
}

define("HISTORY_TYPE",   0);
define("HISTORY_ITEMID", 1);
define("HISTORY_CLOCK",  2);
define("HISTORY_NS",     3);
define("HISTORY_VALUE",  4);

class CHistoryTest extends CApiTest {
	static private $history = array(
		array(ITEM_VALUE_TYPE_FLOAT, 22188, 1351090000, 000000000, 0.1500),
		array(ITEM_VALUE_TYPE_FLOAT, 22188, 1351090934, 999999999, 0.1528),
		array(ITEM_VALUE_TYPE_FLOAT, 22188, 1351090935, 000000000, 0.1529),
		array(ITEM_VALUE_TYPE_FLOAT, 22188, 1351090935, 999999999, 0.1530),
		array(ITEM_VALUE_TYPE_FLOAT, 22188, 1351090936, 549216402, 0.1600),
		array(ITEM_VALUE_TYPE_FLOAT, 22188, 1351090936, 999999999, 0.1429),
		array(ITEM_VALUE_TYPE_FLOAT, 22188, 1351090937, 000000000, 0.1541),
		array(ITEM_VALUE_TYPE_FLOAT, 22188, 1351090937, 999999999, 0.1641),
		array(ITEM_VALUE_TYPE_FLOAT, 22188, 1351090938, 000000000, 0.1642),
		array(ITEM_VALUE_TYPE_FLOAT, 22188, 1351092000, 549216402, 0.2000),
		array(ITEM_VALUE_TYPE_FLOAT, 22189, 1351090936, 549216402, 0.1600),
		array(ITEM_VALUE_TYPE_STR,   22188, 1351090936, 549216403, 'Test string in time range'),
	);

	public function setUp() {
		parent::setUp();

		$this->setUpTestHost();
		$this->setUpHistoryDB();
		$this->api = API::History();
	}

	public function setUpHistoryDB() {
		$writer = HistoryGluon::getInstance();
		foreach (self::$history as $data) {
			$writer->setHistory($data[HISTORY_ITEMID], $data[HISTORY_TYPE],
								$data[HISTORY_CLOCK], $data[HISTORY_NS],
								$data[HISTORY_VALUE]);
			$result = $this->setSQLHistory($data);
		}
	}

	public function setSQLHistory($history) {
		$tableName = "history";
		switch ($history[HISTORY_TYPE]) {
		case ITEM_VALUE_TYPE_UINT64:
			$tableName = "history_uint";
			break;
		case ITEM_VALUE_TYPE_STR:
			$tableName = "history_str";
			break;
		case ITEM_VALUE_TYPE_LOG:
			$tableName = "history_log";
			break;
		case ITEM_VALUE_TYPE_TEXT:
			$tableName = "history_text";
			break;
		case ITEM_VALUE_TYPE_FLOAT:
		default:
			break;
		}
		return DBexecute(
				'INSERT INTO ' . $tableName . ' (itemid, clock, value, ns) VALUES(' .
				$history[HISTORY_ITEMID] . ',' . $history[HISTORY_CLOCK] . ',"' .
				$history[HISTORY_VALUE] . '",' . $history[HISTORY_NS] . ')');
	}

	public function tearDown() {
		$result = DBexecute('DELETE FROM history');
		$result = DBexecute('DELETE FROM history_uint');
		$result = DBexecute('DELETE FROM history_str');
		$result = DBexecute('DELETE FROM history_text');
		$result = DBexecute('DELETE FROM history_log');
		parent::tearDown();
	}

	public function providerCreateValid() {
	}

	private function getExpected($histories, $extend) {
		$expected = array();
		foreach ($histories as $history) {
			$element = array("itemid" => $history[HISTORY_ITEMID],
							 "clock" => $history[HISTORY_CLOCK]);
			if ($extend) {
				$element["value"] = $history[HISTORY_VALUE];
				$element["ns"] = $history[HISTORY_NS];
			}
			array_push($expected, $element);
		}
		return $expected;
	}

	public function providerGet() {
		$query_simple = array (
			"history" => ITEM_VALUE_TYPE_FLOAT,
			"itemids" => array("22188"),
			"time_from" => 1351090935,
			"time_till" => 1351090937,
		);
		$query_extend = array_merge($query_simple, array("output" => "extend"));
		$query_unmatch = array_merge($query_simple, array("history" => ITEM_VALUE_TYPE_LOG));
		$query_2items = $query_simple;
		$query_2items["itemids"] = array("22188", "22189");
		$query_notime = array (
			"history" => ITEM_VALUE_TYPE_FLOAT,
			"itemids" => array("22188"),
		);

		$h = &self::$history;
		$targetBegin = 2;
		$targetEnd = 7;
		$targetHistories = array_slice($h, $targetBegin, $targetEnd - $targetBegin + 1);
		$targetHistoriesForAllItems = array_merge($targetHistories, array($h[10]));

		$expected_simple = $this->getExpected($targetHistories, FALSE);
		$expected_extend = $this->getExpected($targetHistories, TRUE);
		$expected_2items = $this->getExpected($targetHistoriesForAllItems, FALSE);
		$expected_notime = $this->getExpected(array_slice(self::$history, 0, 10), FALSE);

		if (!function_exists(dataSet)) {
			function dataSet($query, $expected) {
				return array(
					array("query" => $query,
						  "expected" => $expected),
				);
			}
		}

		return array (
			dataSet($query_simple, $expected_simple),
			dataSet($query_extend, $expected_extend),
			dataSet($query_unmatch, array()),
			dataSet($query_2items, $expected_2items),
			dataSet($query_notime, $expected_notime),
		);
	}

	public function testCreateValid() {
		$this->markTestIncomplete("History doesn't have create method");
	}

	public function testDelete() {
		$this->markTestIncomplete("History doesn't have delete method");
	}

	private function assertGet($object, $useHistoryGluon) {
		global $HISTORY_DB;
		$use_history_db_saved = $HISTORY_DB['USE'];
		if ($useHistoryGluon) {
			$HISTORY_DB['USE']  = 'yes';
		} else {
			$HISTORY_DB['USE']  = 'no';
		}

		$actual = $this->api->get($object["query"]);
		$this->assertEquals($object["expected"], $actual);

		$HISTORY_DB['USE'] = $use_history_db_saved;
	}

	/**
	 * @dataProvider providerGet
	 */
	public function testGet(array $object) {
		$this->assertGet($object, FALSE);
	}

	/**
	 * @dataProvider providerGet
	 */
	public function testGetHistoryGluon(array $object) {
		$this->assertGet($object, TRUE);
	}
}
?>
