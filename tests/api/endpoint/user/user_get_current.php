<?php

namespace libresignage\tests\api\endpoint\user;

use \JsonSchema\Validator;
use libresignage\tests\common\classes\APITestCase;
use libresignage\tests\common\classes\APITestUtils;

class user_get_current extends APITestCase {
	use \libresignage\tests\common\traits\TestEndpointNotAuthorizedWithoutLogin;

	public function setUp(): void {
		parent::setUp();

		$this->set_endpoint_method('GET');
		$this->set_endpoint_uri('user/user_get_current.php');
	}

	public function test_is_response_schema_correct(): void {
		$this->api->login('admin', 'admin');

		$resp = $this->api->call(
			$this->get_endpoint_method(),
			$this->get_endpoint_uri(),
			[],
			[],
			TRUE
		);
		$this->assert_object_matches_schema(
			$resp,
			dirname(__FILE__).'/schemas/user_get_current.schema.json'
		);

		$this->api->logout();
	}
}
