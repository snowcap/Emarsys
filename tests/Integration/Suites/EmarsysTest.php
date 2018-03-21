<?php

namespace Emarsys\Tests\Integration;

use Emarsys\Client;
use Emarsys\CurlClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;

class EmarsysTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Client
     */
    private $client;
    
    /**
     * @throws \Emarsys\Exception\ClientException
     * @throws \Emarsys\Exception\ServerException
     */
    public function canConnect()
    {
        $this->getGuzzleMock(200, file_get_contents(__DIR__ . '/TestData/languageResult.json'));
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
        $this->getGuzzleMock(200, file_get_contents(__DIR__ . '/TestData/languageResult.json'));
        $response    = $this->client->getLanguages();
        $expectation = ['id' => 'en', 'language' => 'english'];
        
        $this->assertContains($expectation, $response->getData());
    }
    
    /**
     * @test
     */
    public function itShouldGetAvailableFields()
    {
        $this->getGuzzleMock(200, file_get_contents(__DIR__ . '/TestData/fieldResult.json'));
        $response    = $this->client->getFields();
        $expectation = ['id' => 1, 'name' => 'First Name', 'application_type' => 'shorttext'];
        
        $this->assertContains($expectation, $response->getData());
    }
    
    /**
     * returns a emarsys client with mocked handler
     *
     * @param int    $status        http status code
     * @param string $response      the response of the request
     * @param int    $responseCount count of calls to generate responses
     */
    private function getGuzzleMock($status, $response, $responseCount = 1)
    {
        $responses = [];
        
        for ($count = 0; $count <= $responseCount; $count++) {
            $responses[$count] = new \GuzzleHttp\Psr7\Response($status, [], $response);
        }
        
        $mock = new MockHandler(
            $responses
        );
        
        $handler = HandlerStack::create($mock);
        $guzzleClient  = new \GuzzleHttp\Client(['handler' => $handler]);
        
        $this->client = new Client('test', 'cryptoString', $guzzleClient);
    }
}
