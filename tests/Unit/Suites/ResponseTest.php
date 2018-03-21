<?php

namespace Emarsys;

/**
 * @covers \Emarsys\Response
 */
class ResponseTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @expectedException \Emarsys\Exception\ClientException
	 * @expectedExceptionMessage Invalid result structure
	 */
	public function testItThrowsClientException()
	{
		$dummyResult = array('dummy');
		new Response($dummyResult);
	}

	public function testItGetsResponseData()
	{
		$expectedResponse = $this->createExpectedResponse('createContact');
		$result = new Response($expectedResponse);

		$this->assertInternalType('array', $result->getData());
		$this->assertNotEmpty($result);

	}

	public function testItSetsAndGetsReplyCode()
	{
		$expectedResponse = $this->createExpectedResponse('createContact');
		$result = new Response($expectedResponse);

		$this->assertSame(Response::REPLY_CODE_OK, $result->getReplyCode());
	}

	public function testItSetsAndGetsReplyText()
	{
		$expectedResponse = $this->createExpectedResponse('createContact');
		$result = new Response($expectedResponse);

		$this->assertEquals('OK', $result->getReplyText());
	}

	public function testItResponseWithoutData()
	{
		$expectedResponse = $this->createExpectedResponse('insertRecord');
		$result = new Response($expectedResponse);

		$this->assertEmpty($result->getData());
	}

	/**
     * @param string $fileName
     * @return mixed
     */
    private function createExpectedResponse($fileName)
    {
        $fileContent = file_get_contents(__DIR__ . '/TestData/' . $fileName . '.json');

        return json_decode($fileContent, true);
    }
}
