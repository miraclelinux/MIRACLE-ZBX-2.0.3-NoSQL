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

class CHistoryTest extends CApiTest {
	public function setUp() {
		parent::setUp();

		$this->setUpTestHost();
		$this->api = API::History();
	}

	public function providerCreateValid() {
	}

	public function providerGet() {
		return array (
			array(
				array(
					"data" => array(
						"history" => 0,
						"itemids" => array("22188"),
						"time_from" => 1351090935,
						"time_till" => 1351090937,
					),
					"expected" => 1,
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

	/**
	 * @dataProvider providerGet
	 */
	public function testGet(array $object) {
		$actual = $this->api->get($object["data"]);
		$this->assertCount($object["expected"], $actual,
						   'One of the objects has not been retrieved!');
	}
}
?>
