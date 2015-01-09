<?php

namespace Snowcap\Emarsys;

/**
 * @covers \Snowcap\Emarsys\Response
 */
class ResponseTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @expectedException \Snowcap\Emarsys\Exception\ClientException
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
		$result = (new Response($expectedResponse))->getData();

		$this->assertInternalType('array', $result);
		$this->assertNotEmpty($result);

	}

	public function testItSetsAndGetsReplyCode()
	{
		$expectedResponse = $this->createExpectedResponse('createContact');
		$result = (new Response($expectedResponse))->getReplyCode();

		$this->assertSame(Response::REPLY_CODE_OK, $result);
	}

	public function testItSetsAndGetsReplyText()
	{
		$expectedResponse = $this->createExpectedResponse('createContact');
		$result = (new Response($expectedResponse))->getReplyText();

		$this->assertEquals('OK', $result);
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
