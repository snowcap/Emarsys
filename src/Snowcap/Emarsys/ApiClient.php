<?php

namespace Snowcap\Emarsys;

use Guzzle\Http\Client;
use Guzzle\Http\Message\Request;
use Guzzle\Http\Message\Response;

class ApiClient
{
    /**
     * @var string
     */
    protected $baseUrl = 'https://www1.emarsys.net/api/v2/';
    /**
     * @var string
     */
    protected $username;
    /**
     * @var string
     */
    protected $secret;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var array
     */
    protected $headers = array();

    /**
     * @param string $username
     * @param string $secret
     */
    public function __construct($username, $secret)
    {
        $this->username = $username;
        $this->secret = $secret;

        $this->prepareHeaders();
        $this->prepareClient();

    }

    /**
     * @param string $resource
     * @param array $data
     * @return Response
     */
    public function get($resource, $data = array())
    {
        $request = $this->client->get($resource);
        $request->addHeaders($this->headers);

        return $request->send();
    }

    /**
     * @return Response
     */
    public function getLanguage()
    {
        return $this->get('language');
    }

    /**
     * Create a new client with base url
     */
    protected function prepareClient()
    {
        $this->client = new Client();
        $this->client->setBaseUrl($this->baseUrl);

    }

    /**
     * Set content-type to json and create the custom authentication through the X-WSSE header
     */
    protected function prepareHeaders()
    {
        // the current time encoded as an ISO 8601 date string
        $created = new \DateTime();
        $iso8601 = $created->format(\DateTime::ISO8601);
        // the md5 of a random string . e.g. a timestamp
        $nonce = md5($created->modify('next friday')->getTimestamp());
        // The algorithm to generate the digest is as follows:
        // Concatenate: Nonce + Created + Secret
        // Hash the result using the SHA1 algorithm
        // Encode the result to base64
        $digest = base64_encode(sha1($nonce . $iso8601 . $this->secret));

        $signature = sprintf(
            'UsernameToken Username="%s", PasswordDigest="%s", Nonce="%s", Created="%s"',
            $this->username,
            $digest,
            $nonce,
            $iso8601
        );

        $this->headers += array('X-WSSE' => $signature);
        $this->headers += array('Content-Type' => 'application/json');
    }
}