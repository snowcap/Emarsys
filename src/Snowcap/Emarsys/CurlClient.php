<?php

namespace Snowcap\Emarsys;

use Snowcap\Emarsys\Exception\ClientException;

class CurlClient implements HttpClient
{
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
	    // Set to true to output received headers
	    $debugCurlHeaders = false;

		$ch = curl_init();
		$uri = $this->updateUri($method, $uri, $body);

		if ($method != self::GET) {
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
		}

		curl_setopt($ch, CURLOPT_URL, $uri);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		if ($debugCurlHeaders) {
            $headers = [];

            // this function is called by curl for each header received
            curl_setopt($ch, CURLOPT_HEADERFUNCTION,
                function ($curl, $header) use (&$headers) {
                    $len = strlen($header);
                    $header = explode(':', $header, 2);
                    if (count($header) < 2) // ignore invalid headers
                        return $len;

                    $name = trim($header[0]);
                    if (!array_key_exists($name, $headers))
                        $headers[$name] = [trim($header[1])];
                    else
                        $headers[$name][] = trim($header[1]);

                    return $len;
                }
            );
        }

		$output = curl_exec($ch);

		if ($debugCurlHeaders) {
            var_dump($headers);
        }

		if (false === $output) {
			$message = 'Curl error "'.curl_error($ch)."\" \nCurl error number ".curl_errno($ch)." see http://curl.haxx.se/libcurl/c/libcurl-errors.html";
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
