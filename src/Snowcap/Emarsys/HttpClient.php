<?php

namespace Snowcap\Emarsys;

interface HttpClient
{
	const GET = 'GET';
	const POST = 'POST';
	const PUT = 'PUT';
	const DELETE = 'DELETE';

	/**
	 * @param string $method
	 * @param string $uri
	 * @param array $headers
	 * @param mixed $body
	 * @return mixed
	 */
	public function send($method, $uri, $headers, $body);
}
