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
	 * @param string[] $headers
	 * @param array $body
	 * @return string
	 */
	public function send($method, $uri, array $headers, array $body);
}
