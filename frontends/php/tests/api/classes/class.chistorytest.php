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

define("HISTORY_ITEMID", 0);
define("HISTORY_CLOCK",  1);
define("HISTORY_NS",     2);
define("HISTORY_VALUE",  3);

class CHistoryTest extends CApiTest {
	static private $history = array(
		array(22188, 1351090000, 000000000, 0.15000),
		array(22188, 1351090936, 549216402, 0.16000),
		array(22189, 1351090936, 549216402, 0.16000),
		array(22188, 1351092000, 549216402, 0.20000));

	public function setUp() {
		parent::setUp();

		$this->setUpTestHost();
		$this->setUpHistoryDB();
		$this->api = API::History();
	}

	public function setUpHistoryDB() {
		$writer = HistoryGluon::getInstance();
		foreach (self::$history as $data) {
			$writer->setHistory($data[HISTORY_ITEMID], 0, $data[HISTORY_CLOCK],
								$data[HISTORY_NS], $data[HISTORY_VALUE]);
			$result = $this->setSQLHistory($data);
		}
	}

	public function setSQLHistory($history) {
		return DBexecute(
				'INSERT INTO history (itemid, clock, value, ns) VALUES(' .
				$history[HISTORY_ITEMID] . ',' . $history[HISTORY_CLOCK] . ',' .
				$history[HISTORY_VALUE] . ',' . $history[HISTORY_NS] . ')');
	}

	public function tearDown() {
		$result = DBexecute('DELETE FROM history');
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
			"history" => 0,
			"itemids" => array("22188"),
			"time_from" => 1351090935,
			"time_till" => 1351090937,
		);
		$query_extend = array_merge($query_simple, array("output" => "extend"));
		$query_unmatch = array_merge($query_simple, array("history" => "1"));

		$expected_simple = $this->getExpected(array(self::$history[1]), FALSE);
		$expected_extend = $this->getExpected(array(self::$history[1]), TRUE);

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
