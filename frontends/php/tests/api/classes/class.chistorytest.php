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

class HistoryGluonWriter {
	private static $instance = null;
	private static $ctx = null;

	public static function getInstance() {
		if (is_null(self::$instance))
			self::$instance = new HistoryGluonWriter();
		return self::$instance;
	}

	public function setHistory($itemid, $type, $seconds, $nanoseconds, $value) {
		switch($type) {
			case ITEM_VALUE_TYPE_LOG:
				// FIXME: not implemented in src/libs/zbxdbcache/dbcache.c
				break;
			case ITEM_VALUE_TYPE_TEXT:
				// FIXME: not implemented in src/libs/zbxdbcache/dbcache.c
				break;
			case ITEM_VALUE_TYPE_STR:
				history_gluon_add_string($this->ctx, $itemid,
										 $seconds, $nanoseconds, $value);
				break;
			case ITEM_VALUE_TYPE_UINT64:
				history_gluon_add_uint($this->ctx, $itemid,
									  $seconds, $nanoseconds, $value);
				break;
			case ITEM_VALUE_TYPE_FLOAT:
			default:
				history_gluon_add_float($this->ctx, $itemid,
										$seconds, $nanoseconds, $value);
		}
	}

	public function __construct() {
		global $HISTORY_DB;
		if (!extension_loaded('History Gluon PHP Extension'))
			dl("history_gluon.so");
		$server = null;
		$port = 0;
		if (isset($HISTORY_DB['SERVER'])) 
			$server = $HISTORY_DB['SERVER'];
		if (isset($HISTORY_DB['PORT'])) 
			$port = $HISTORY_DB['PORT'];
		$ctx = 0;
		$ret = history_gluon_create_context('zabbix', $server, $port, $ctx);
		$this->ctx = $ctx;
	}
}

class CHistoryTest extends CApiTest {
	// FIXME: should be unified with the data in provierGet()
	static private $historyFixture = array(
		array(22188, 1351090936, 549216402, 0.16000));

	public function setUp() {
		parent::setUp();

		$this->setUpTestHost();
		$this->api = API::History();

        $result = DBexecute('DELETE FROM history');
		$writer = HistoryGluonWriter::getInstance();
		foreach (self::$historyFixture as $data) {
			$writer->setHistory($data[0], 0, $data[1], $data[2], $data[3]);
			$result = DBexecute(
				'INSERT INTO history (itemid, clock, value, ns)'.
				' VALUES (' . $data[0] . ',' . $data[1] . ',' . $data[3] . ',' . $data[2] . ')');
		}
	}

	public function providerCreateValid() {
	}

	public function providerGet() {
		$query1 = array (
			"history" => 0,
			"itemids" => array("22188"),
			"time_from" => 1351090935,
			"time_till" => 1351090937,
		);

		return array (
			array(
				array(
					"query" => array_merge($query1, array("output" => "extend")),
					"expected" => array(
						array(
							"itemid" => "22188",
							"clock" => "1351090936",
							"value" => "0.16000",
							"ns" => "549216402",
						),
					),
				),
			),
			array(
				array(
					"query" => $query1,
					"expected" => array(
						array(
							"itemid" => "22188",
							"clock" => "1351090936",
						),
					),
				),
			),
			array(
				array(
					"query" => array_merge($query1, array("history" => "1")),
					"expected" => array(),
				),
			),
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
