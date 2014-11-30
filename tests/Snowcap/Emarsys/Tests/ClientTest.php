<?php

namespace Snowcap\Emarsys\Tests;

use Snowcap\Emarsys\Client;
use Snowcap\Emarsys\Response;

/**
 * @covers \Snowcap\Emarsys\Client
 * @covers \Snowcap\Emarsys\Response
 */
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
	 * @expectedException \Guzzle\Http\Exception\CurlException
	 * @expectedExceptionMessage Couldn't resolve host 'dummy.url' [url] http://dummy.url/condition
	 */
	public function testItUsesBaseUrlOverride()
	{
		$client = new Client('dummy_username', 'dummy_password', 'http://dummy.url/');
		$client->getConditions();
	}

	public function testItAddsFieldsMapping()
	{
		$customField1Id = 7147;
		$customField1Name = 'myCustomField1';
		$customField2Id = 7148;
		$customField2Name = 'myCustomField2';

		$mapping = array(
			$customField1Name => $customField1Id,
			$customField2Name => $customField2Id
		);

		$this->client->addFieldsMapping($mapping);

		$resultField1Id = $this->client->getFieldId($customField1Name);
		$resultField1Name = $this->client->getFieldName($customField1Id);
		$resultField2Id = $this->client->getFieldId($customField2Name);
		$resultField2Name = $this->client->getFieldName($customField2Id);

		$this->assertEquals($customField1Id, $resultField1Id);
		$this->assertEquals($customField1Name, $resultField1Name);
		$this->assertEquals($customField2Id, $resultField2Id);
		$this->assertEquals($customField2Name, $resultField2Name);
	}

	public function testItAddsChoicesMapping()
	{
		$customFieldName = 'myCustomField';
		$customChoice1Id = 1;
		$customChoice1Name = 'myCustomChoice1';
		$customChoice2Id = 2;
		$customChoice2Name = 'myCustomChoice2';
		$customChoice3Id = 3;
		$customChoice3Name = 'myCustomChoice3';

		$mapping = array(
			$customFieldName => array(
				$customChoice1Name => $customChoice1Id
			)
		);

		/* Adding one choice first to test later that it is not overwritten by adding more choices */
		$this->client->addChoicesMapping($mapping);

		$mapping = array(
			$customFieldName => array(
				$customChoice2Name => $customChoice2Id,
				$customChoice3Name => $customChoice3Id
			)
		);

		$this->client->addChoicesMapping($mapping);

		$resultField1Id = $this->client->getChoiceId($customFieldName, $customChoice1Name);
		$resultField1Name = $this->client->getChoiceName($customFieldName, $customChoice1Id);
		$resultField2Id = $this->client->getChoiceId($customFieldName, $customChoice2Name);
		$resultField2Name = $this->client->getChoiceName($customFieldName, $customChoice2Id);
		$resultField3Id = $this->client->getChoiceId($customFieldName, $customChoice3Name);
		$resultField3Name = $this->client->getChoiceName($customFieldName, $customChoice3Id);

		$this->assertEquals($customChoice1Id, $resultField1Id);
		$this->assertEquals($customChoice1Name, $resultField1Name);
		$this->assertEquals($customChoice2Id, $resultField2Id);
		$this->assertEquals($customChoice2Name, $resultField2Name);
		$this->assertEquals($customChoice3Id, $resultField3Id);
		$this->assertEquals($customChoice3Name, $resultField3Name);
	}

	/**
	 * @expectedException \Snowcap\Emarsys\Exception\ClientException
	 * @expectedExceptionMessage Unrecognized field name "non-existing-field-name"
	 */
	public function testItThrowsAnExceptionIfFieldDoesNotExist()
	{
		$this->client->getFieldId('non-existing-field-name');
	}

	/**
	 * @expectedException \Snowcap\Emarsys\Exception\ClientException
	 * @expectedExceptionMessage Unrecognized field "non-existing-field-name" for choice "choice-name"
	 */
	public function testItThrowsAnExceptionIfChoiceFieldDoesNotExist()
	{
		$this->client->getChoiceId('non-existing-field-name', 'choice-name');
	}

	/**
	 * @expectedException \Snowcap\Emarsys\Exception\ClientException
	 * @expectedExceptionMessage Unrecognized choice "choice-name" for field "myCustomField"
	 */
	public function testItThrowsAnExceptionIfChoiceDoesNotExist()
	{
		$fieldName = 'myCustomField';
		$mapping = array($fieldName => array());

		$this->client->addChoicesMapping($mapping);
		$this->client->getChoiceId($fieldName, 'choice-name');
	}

	public function testItReturnsChoiceIdIfChoiceNameIsNotFound()
	{
		$fieldName = 'myCustomField';
		$choiceId = 1;
		$mapping = array($fieldName => array());

		$this->client->addChoicesMapping($mapping);
		$result = $this->client->getChoiceName($fieldName, $choiceId);

		$this->assertEquals($choiceId, $result);
	}

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

    public function testGetContactIdSuccess()
    {
        $expectedResponse = new Response($this->createExpectedResponse('getContactId'));
        $this->client->expects($this->once())->method('send')->will($this->returnValue($expectedResponse));

        $response = $this->client->getContactId('3', 'sender@example.com');

        $expectedData = $expectedResponse->getData();
        $this->assertEquals($expectedData['id'], $response);
    }

	/**
	 * @expectedException \Snowcap\Emarsys\Exception\ClientException
	 * @expectedExceptionMessage Invalid result structure
	 */
	public function testItThrowsClientException()
	{
		$dummyResult = array('dummy');
		new Response($dummyResult);
	}

	public function testItSetsAndGetsData()
	{
		$data = array('key' => 'val');
		$response = new Response($this->createExpectedResponse('create'));
		$response->setData($data);
		$result = $response->getData();

		$this->assertEquals($data, $result);
	}

	public function testItSetsAndGetsReplyCode()
	{
		$replyCode = 200;
		$response = new Response($this->createExpectedResponse('create'));
		$response->setReplyCode($replyCode);
		$result = $response->getReplyCode();

		$this->assertEquals($replyCode, $result);
	}

	public function testItSetsAndGetsReplyText()
	{
		$replyText = 'text-reply';
		$response = new Response($this->createExpectedResponse('create'));
		$response->setReplyText($replyText);
		$result = $response->getReplyText();

		$this->assertEquals($replyText, $result);
	}

	public function testItReturnsContactData()
	{
		$expectedResponse = new Response($this->createExpectedResponse('getContactData'));
		$this->client->expects($this->once())->method('send')->will($this->returnValue($expectedResponse));

		$response = $this->client->getContactData(array());

		$this->assertEquals($expectedResponse, $response);

	}

	/**
     * Get a json test data and decode it
     *
     * @param string $fileName
     * @return mixed
     */
    private function createExpectedResponse($fileName)
    {
        $fileContent = file_get_contents(__DIR__ . '/TestData/' . $fileName . '.json');

        return json_decode($fileContent, true);
    }
}
