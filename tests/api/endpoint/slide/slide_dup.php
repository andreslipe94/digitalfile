<?php

namespace libresignage\tests\api\endpoint\slide;

use libresignage\tests\common\classes\APITestCase;
use libresignage\tests\common\classes\APIInterface;
use libresignage\api\HTTPStatus;
use libresignage\common\php\JSONUtils;
use libresignage\tests\common\classes\SlideUtils;

class slide_dup extends APITestCase {
	private $orig_slide_id = NULL;
	private $dup_slide_id = NULL;

	use \libresignage\tests\common\traits\TestEndpointNotAuthorizedWithoutLogin;

	public function setUp(): void {
		parent::setUp();

		$this->set_endpoint_method('POST');
		$this->set_endpoint_uri('slide/slide_dup.php');

		// Create an initial slide to duplicate.
		$this->api->login('admin', 'admin');
		$resp = APIInterface::assert_success(SlideUtils::save(
			$this->api,
			NULL,
			'Unit-Test-Slide',
			0,
			5000,
			'Test Markup',
			TRUE,
			FALSE,
			0,
			0,
			0,
			'default',
			[]
		), 'Failed to create initial slide.', [$this, 'abort']);
		$this->api->logout();

		$this->orig_slide_id = APIInterface::decode_raw_response($resp)->slide->id;
	}

	/**
	* @dataProvider params_provider
	*/
	public function test_fuzz_params(
		string $user,
		string $pass,
		array $params,
		int $error
	) {
		$resp = $this->call_api_and_assert_failed(
			$params,
			[],
			$error,
			$user,
			$pass
		);

		if ($resp->getStatusCode() === HTTPStatus::OK) {
			$this->dup_slide_id = APIInterface::decode_raw_response(
				$resp
			)->slide->id;
		}
	}

	public static function params_provider(): array {
		return [
			'Valid parameters' => [
				'admin',
				'admin',
				['id' => '1'],
				HTTPStatus::OK
			],
			'Missing id parameter' => [
				'admin',
				'admin',
				[],
				HTTPStatus::BAD_REQUEST
			],
			'Wrong type for id parameter' => [
				'admin',
				'admin',
				['id' => TRUE],
				HTTPStatus::BAD_REQUEST
			],
			'Empty id parameter' => [
				'admin',
				'admin',
				['id' => ''],
				HTTPStatus::NOT_FOUND
			],
			'Nonexistent id' => [
				'admin',
				'admin',
				['id' => 'aabbccddee'],
				HTTPStatus::NOT_FOUND
			],
			'Editor not in admin or user groups duplicates slide' => [
				'display',
				'display',
				['id' => '1'],
				HTTPStatus::UNAUTHORIZED
			]
		];
	}

	public function test_is_response_schema_correct() {
		$this->dup_slide_id = APIInterface::decode_raw_response(
			$this->call_api_and_check_response_schema(
				['id' => '1'],
				[],
				dirname(__FILE__).'/schemas/slide_dup.schema.json',
				'admin',
				'admin'
			)
		)->slide->id;
	}

	public function tearDown(): void {
		$this->api->login('admin', 'admin');

		// Remove the initial slide.
		APIInterface::assert_success(SlideUtils::lock(
			$this->api,
			$this->orig_slide_id
		), 'Failed to lock original slide.', [$this->api, 'logout']);
		APIInterface::assert_success(SlideUtils::remove(
			$this->api,
			$this->orig_slide_id
		), 'Failed to remove original slide.', [$this->api, 'logout']);
		$this->orig_slide_id = NULL;

		// Remove duplicated slide if it was created.
		if ($this->dup_slide_id !== NULL) {
			APIInterface::assert_success(SlideUtils::lock(
				$this->api,
				$this->dup_slide_id
			), 'Failed to lock duplicated slide.', [$this->api, 'logout']);
			APIInterface::assert_success(SlideUtils::remove(
				$this->api,
				$this->dup_slide_id
			), 'Failed to remove duplicated slide.', [$this->api, 'logout']);
			$this->dup_slide_id = NULL;
		}

		$this->api->logout();
	}
}
