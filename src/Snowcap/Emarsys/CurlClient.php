<?php

namespace Snowcap\Emarsys;

use Snowcap\Emarsys\Exception\ClientException;

class CurlClient implements HttpClient
{
	/**
	 * List of Emarsys http codes that don't throw an exception
	 *
	 * @var unknown
	 */
	private $ignoredHttpCodes = array(
		200=>true,
		400 =>true
	);

	/**
	 * Number of times to attempt the curl request
	 *
	 * @var integer
	 */
	const MAX_ATTEMPTS = 5;

	/**
	 * Number of microseconds to sleep between failed curl requests
	 *
	 * @var integer
	 */
	const RETRY_DELAY_MICROSECONDS = 500;

	/**
	 * Maximum number of seconds to attempt to conenct to the Emarsys server
	 *
	 * @var integer
	 */
	const CONNECT_TIMEOUT = 5;

	/**
	 * Maximum time to process the request once connected
	 *
	 * @var integer
	 */
	const RESPONSE_TIMEOUT = 20;

	/**
	 * @param string $method
	 * @param string $uri
	 * @param string[] $headers
	 * @param array $body
	 * @return string
	 * @throws ClientException
	 */
	public function send($method, $uri, array $headers = array(), array $body = array())
	{
		$ch = curl_init();
		$uri = $this->updateUri($method, $uri, $body);

		if ($method != self::GET) {
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
		}
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::CONNECT_TIMEOUT);
		curl_setopt($ch, CURLOPT_TIMEOUT, self::CONNECT_TIMEOUT + self::RESPONSE_TIMEOUT);
		curl_setopt($ch, CURLOPT_URL, $uri);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$maxRetry = self::MAX_ATTEMPTS;
		do {
			$output = curl_exec($ch);
			$curlErrorCode = curl_error($ch);
			$httpCode = curl_getinfo ($ch, CURLINFO_HTTP_CODE);
			if($curlErrorCode) {
				usleep(self::RETRY_DELAY_MICROSECONDS);
			}
		} while($curlErrorCode && $maxRetry--);

		if (false === $output || !isset($this->ignoredHttpCodes[$httpCode]) || $curlErrorCode) {
			$message = "Http status: {$httpCode}\nCurl error {$curlErrorCode}\nCurl error number {$curlErrorCode}\nsee http://curl.haxx.se/libcurl/c/libcurl-errors.html";
			curl_close($ch);
			throw new ClientException($message);
		}

		curl_close($ch);

		return $output;
	}

	/**
	 * @param string $method
	 * @param string $uri
	 * @param array $body
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
