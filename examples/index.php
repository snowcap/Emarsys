<?php

require_once 'config.php';

use Snowcap\Emarsys\ApiClient;
use Guzzle\Http\Exception\ClientErrorResponseException;

try {
    $client = new ApiClient(EMARSYS_API_USERNAME, EMARSYS_API_SECRET);

    $response = $client->getLanguage();
    $status = $response->getStatusCode();

    var_dump($response);
} catch (ClientErrorResponseException $e) {
    echo $e->getRequest() . "\n";
    echo $e->getResponse() . "\n";
}