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
    public function testException()
    {
        $client = new Client('wrong_username', 'wrong_secret');
        $client->getConditions();
    }

    /**
     * @covers \Snowcap\Emarsys\Client::getEmails
     */
    public function testGetEmails()
    {
        $response = new Response($this->getTestData('emails'));
        $this->client->expects($this->any())->method('send')->will($this->returnValue($response));

        $response = $this->client->getEmails();

        $this->assertEquals($response->getReplyCode(), Response::REPLY_CODE_OK);
        $this->assertEquals($response->getReplyText(), " OK");

    }

    /**
     * Get a json test data and decode it
     *
     * @param string $fileName
     * @return mixed
     */
    protected function getTestData($fileName)
    {
        $fileContent = file_get_contents(__DIR__ . '/TestData/' . $fileName . '.json');

        return json_decode($fileContent, true);
    }
}
 