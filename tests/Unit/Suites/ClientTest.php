<?php

namespace Snowcap\Emarsys;

/**
 * @covers \Snowcap\Emarsys\Client
 * @uses \Snowcap\Emarsys\Response
 */
class ClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Client
     */
    private $client;

	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject|HttpClient
	 */
	private $httpClient;

    protected function setUp()
    {
	    $this->httpClient = $this->getMock('\Snowcap\Emarsys\HttpClient');
	    $this->client = new Client($this->httpClient, 'dummy-api-username', 'dummy-api-secret');
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
        $expectedResponse = $this->createExpectedResponse('emails');
        $this->httpClient->expects($this->any())
	        ->method('send')
	        ->willReturn($expectedResponse);

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
        $expectedResponse = $this->createExpectedResponse('createContact');
	    $this->httpClient->expects($this->any())
		    ->method('send')
		    ->willReturn($expectedResponse);

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
        $expectedResponse = $this->createExpectedResponse('getContactId');
	    $this->httpClient->expects($this->any())
		    ->method('send')
		    ->willReturn($expectedResponse);

        $response = $this->client->getContactId('3', 'sender@example.com');

        $expectedData = json_decode($expectedResponse, true);
        $this->assertEquals($expectedData['data']['id'], $response);
    }

	public function testItReturnsContactData()
	{
		$expectedResponse = $this->createExpectedResponse('getContactData');
		$this->httpClient->expects($this->once())
			->method('send')
			->willReturn($expectedResponse);

		$response = $this->client->getContactData(array());

		$this->assertInstanceOf('\Snowcap\Emarsys\Response', $response);
	}

	public function testItCreatesContact()
	{
		$expectedResponse = $this->createExpectedResponse('createContact');
		$this->httpClient->expects($this->once())
			->method('send')
			->willReturn($expectedResponse);

		$data = array(
			'3'         => 'recipient@example.com',
			'source'    => '123',
		);
		$response = $this->client->createContact($data);

		$this->assertInstanceOf('\Snowcap\Emarsys\Response', $response);
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

        return $fileContent;
    }
}
