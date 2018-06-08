<?php

namespace Snowcap\Emarsys;

use Snowcap\Emarsys\Exception\ClientException;

/**
 * A cURL HTTP client implementation
 */
class CurlClient implements HttpClient
{
    /**
     * An array of predefined cURL options
     *
     * @var array
     */
    private $curlOptions = array();

    /**
     * CurlClient constructor.
     * @param array $curlOptions additional options for cURL (timeouts, etc.)
     */
    public function __construct(array $curlOptions = array())
    {
        $this->curlOptions = $curlOptions;
    }

    /**
     * Send an HTTP request
     *
     * @param string   $method
     * @param string   $uri
     * @param string[] $headers
     * @param array    $body
     * @return string
     * @throws ClientException
     */
    public function send($method, $uri, array $headers = array(), array $body = array())
    {
        $ch  = curl_init();
        $uri = $this->updateUri($method, $uri, $body);

        if ($method != self::GET) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        foreach ($this->curlOptions as $optionCode => $optionValue) {
            curl_setopt($ch, $optionCode, $optionValue);
        }

        curl_setopt($ch, CURLOPT_URL, $uri);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $output = curl_exec($ch);
        if (false === $output) {
            $message = sprintf(
                "Curl error \"%s\"\n Curl error number %d see http://curl.haxx.se/libcurl/c/libcurl-errors.html",
                curl_error($ch),
                curl_errno($ch)
            );
            curl_close($ch);
            throw new ClientException($message);
        }

        curl_close($ch);

        return $output;
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array  $body
     * @return string
     */
    private function updateUri($method, $uri, array $body)
    {
        if (self::GET == $method) {
            $uri .= '/' . http_build_query($body);
        }

        return $uri;
    }
}
