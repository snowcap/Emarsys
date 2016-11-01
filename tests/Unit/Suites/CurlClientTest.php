<?php

namespace Snowcap\Emarsys;

/**
 * @covers \Snowcap\Emarsys\CurlClient
 */
class CurlClientTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @var CurlClient
	 */
	private $client;

	protected function setUp()
	{
		$this->client = new CurlClient();
	}

	/**
	 * @expectedException \Snowcap\Emarsys\Exception\ClientException
	 */
	public function testRequestToNonExistingHostFails()
	{
		$this->client->send('POST', 'http://foo.bar');
	}

	public function testRequestReturnsOutput()
	{
		$result = $this->client->send('GET', 'http://google.com', array(), array('foo' => 'bar'));

		$this->assertContains('<html', $result);
	}
}
