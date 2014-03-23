<?php

namespace Snowcap\Emarsys\Tests;


use Snowcap\Emarsys\Client;
use Snowcap\Emarsys\Response;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Client
     */
    protected $client;

    public function setUp()
    {
        $this->client = $this->getMock('\Snowcap\Emarsys\Client', array('send'), array(EMARSYS_API_USERNAME, EMARSYS_API_SECRET));
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

        foreach($response->getData() as $data) {
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

        $response = $this->client->createEmail(
            'test api 010',
            'en',
            'subject here',
            'sender email',
            'sender@example.com',
            '17',
            '<html>Hello $First Name$,... </html>',
            'email text',
            1121,
            null,
            1,
            0
        );

        $this->assertEquals(Response::REPLY_CODE_OK, $response->getReplyCode());
        $this->assertArrayHasKey('id', $response->getData());

        $this->setExpectedException('\Snowcap\Emarsys\Exception\ClientException', 'Missing segment or contactList');

        $this->client->createEmail(
            'test api 010',
            'en',
            'subject here',
            'sender email',
            'sender@example.com',
            '17',
            '<html>Hello $First Name$,... </html>',
            'email text'
        );

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
 