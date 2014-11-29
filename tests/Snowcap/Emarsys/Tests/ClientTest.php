<?php

namespace Snowcap\Emarsys\Tests;

use Snowcap\Emarsys\Client;
use Snowcap\Emarsys\Response;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Client
     */
    private $client;

    public function setUp()
    {
	    $this->client = $this->getMockBuilder(Client::class)
		    ->setMethods(array('send'))
		    ->setConstructorArgs(array('dummy-api-username', 'dummy-api-secret'))
	        ->getMock();
    }

    /**
     * @expectedException \Snowcap\Emarsys\Exception\ServerException
     * @expectedExceptionMessage Unauthorized
     * @expectedExceptionCode \Snowcap\Emarsys\Response::REPLY_CODE_INTERNAL_ERROR
     */
    public function testUnauthorizedException()
    {
        $client = new Client('wrong_username', 'wrong_secret');
        $client->getConditions();
    }

	/**
	 * @covers \Snowcap\Emarsys\Client::__construct
	 * @expectedException \Guzzle\Http\Exception\CurlException
	 * @expectedExceptionMessage Couldn't resolve host 'dummy.url' [url] http://dummy.url/condition
	 */
	public function testItUsesBaseUrlOverride()
	{
		$client = new Client('dummy_username', 'dummy_password', 'http://dummy.url/');
		$client->getConditions();
	}

    /**
     * @covers \Snowcap\Emarsys\Client::getEmails
     */
    public function testGetEmails()
    {
        $expectedResponse = new Response($this->createExpectedResponse('emails'));
        $this->client->expects($this->any())->method('send')->will($this->returnValue($expectedResponse));

        $response = $this->client->getEmails();

        $this->assertEquals($response->getReplyCode(), Response::REPLY_CODE_OK);

        $response = $this->client->getEmails(Client::EMAIL_STATUS_READY);

        $this->assertEquals($response->getReplyCode(), Response::REPLY_CODE_OK);

        $response = $this->client->getEmails(null, 123);

        $this->assertEquals($response->getReplyCode(), Response::REPLY_CODE_OK);

        $response = $this->client->getEmails(Client::EMAIL_STATUS_READY, 123);

        $this->assertEquals($response->getReplyCode(), Response::REPLY_CODE_OK);

        $this->assertNotEmpty($response->getData());

        foreach ($response->getData() as $data) {
            $this->assertArrayHasKey('id', $data);
            $this->assertArrayHasKey('name', $data);
            $this->assertArrayHasKey('status', $data);
        }
    }

    /**
     * @covers \Snowcap\Emarsys\Client::createEmail
     */
    public function testCreateEmail()
    {
        $expectedResponse = new Response($this->createExpectedResponse('create'));
        $this->client->expects($this->any())->method('send')->will($this->returnValue($expectedResponse));

        $data = array(
            'language' => 'en',
            'name' => 'test api 010',
            'fromemail' => 'sender@example.com',
            'fromname' => 'sender email',
            'subject' => 'subject here',
            'email_category' => '17',
            'html_source' => '<html>Hello $First Name$,... </html>',
            'text_source' => 'email text',
            'segment' => 1121,
            'contactlist' => 0,
            'unsubscribe' => 1,
            'browse' => 0,
        );

        $response = $this->client->createEmail($data);

        $this->assertEquals(Response::REPLY_CODE_OK, $response->getReplyCode());
        $this->assertArrayHasKey('id', $response->getData());
    }

    /**
     * @covers \Snowcap\Emarsys\Client::getContactId
     */
    public function testGetContactIdSuccess()
    {
        $expectedResponse = new Response($this->createExpectedResponse('getContactId'));
        $this->client->expects($this->once())->method('send')->will($this->returnValue($expectedResponse));

        $response = $this->client->getContactId('3', 'sender@example.com');

        $expectedData = $expectedResponse->getData();
        $this->assertEquals($expectedData['id'], $response);
    }

	/**
	 * @covers \Snowcap\Emarsys\Response::__construct
	 * @expectedException \Snowcap\Emarsys\Exception\ClientException
	 * @expectedExceptionMessage Invalid result structure
	 */
	public function testItThrowsClientException()
	{
		$dummyResult = array('dummy');
		new Response($dummyResult);
	}

	/**
	 * @covers \Snowcap\Emarsys\Response::getData
	 * @covers \Snowcap\Emarsys\Response::setData
	 */
	public function testItSetsAndGetsData()
	{
		$data = array('key' => 'val');
		$response = new Response($this->createExpectedResponse('create'));
		$response->setData($data);
		$result = $response->getData();

		$this->assertEquals($data, $result);
	}

	/**
	 * @covers \Snowcap\Emarsys\Response::getReplyCode
	 * @covers \Snowcap\Emarsys\Response::setReplyCode
	 */
	public function testItSetsAndGetsReplyCode()
	{
		$replyCode = 200;
		$response = new Response($this->createExpectedResponse('create'));
		$response->setReplyCode($replyCode);
		$result = $response->getReplyCode();

		$this->assertEquals($replyCode, $result);
	}

	/**
	 * @covers \Snowcap\Emarsys\Response::getReplyText
	 * @covers \Snowcap\Emarsys\Response::setReplyText
	 */
	public function testItSetsAndGetsReplyText()
	{
		$replyText = 'text-reply';
		$response = new Response($this->createExpectedResponse('create'));
		$response->setReplyText($replyText);
		$result = $response->getReplyText();

		$this->assertEquals($replyText, $result);
	}

	/**
     * Get a json test data and decode it
     *
     * @param string $fileName
     * @return mixed
     */
    protected function createExpectedResponse($fileName)
    {
        $fileContent = file_get_contents(__DIR__ . '/TestData/' . $fileName . '.json');

        return json_decode($fileContent, true);
    }
}
