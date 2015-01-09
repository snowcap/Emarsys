<?php

namespace Snowcap\Emarsys\Tests\Integration;

use Snowcap\Emarsys\Client;
use Snowcap\Emarsys\CurlClient;

class EmarsysTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @var Client
	 */
	private $client;

	protected function setUp()
	{
		if (!defined('EMARSYS_API_USERNAME') || !defined('EMARSYS_API_SECRET')) {
			$this->markTestSkipped('No Emarsys credentials are specified');
		}

		$httpClient = new CurlClient();
		$this->client = new Client($httpClient, EMARSYS_API_USERNAME, EMARSYS_API_SECRET);

		$connectionTestResponse = $this->client->getLanguages();

		if (0 !== $connectionTestResponse->getReplyCode()) {
			$this->markTestSkipped('Problem connecting to Emarsys. Check credentials in phpunit.xml.dist.');
		}
	}

	/**
	 * @test
	 */
	public function itShouldGetAvailableLanguages()
	{
		$response = $this->client->getLanguages();
		$expectation = ['id' => 'en', 'language' => 'english'];

		$this->assertContains($expectation, $response->getData());
	}

	/**
	 * @test
	 */
	public function itShouldGetAvailableFields()
	{
		$response = $this->client->getFields();
		$expectation = ['id' => 1, 'name' => 'First Name', 'application_type' => 'shorttext'];

		$this->assertContains($expectation, $response->getData());
	}
}
