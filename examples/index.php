<?php

/**
 * Copy/Paste the config.php.dist to config.php and set the right settings
 */

require_once 'config.php';

use Snowcap\Emarsys\Client;
use Snowcap\Emarsys\Response;

/**
 * @param Response $response
 */
function testAPI(Response $response)
{
    echo PHP_EOL . str_repeat("=", 80) . PHP_EOL;
    var_dump($response);
    echo PHP_EOL . str_repeat("=", 80) . PHP_EOL;
    echo PHP_EOL;
}

/**
 * Try to create or update a contact
 *
 * @param \Snowcap\Emarsys\Client $client
 * @return \Snowcap\Emarsys\Response
 * @throws Exception
 * @throws \Snowcap\Emarsys\Exception\ServerException
 */
function sendContact(Client $client)
{
    $data = array();
    try {
        $data = array(
            'email' => 'johndoe@gmail.com',
            'gender' => $client->getChoiceId('gender', 'male'),
            'salutation' => $client->getChoiceId('salutation', 'mr'),
            'firstName' => 'John',
            'lastName' => 'Doe',
            'birthDate' => '2014-03-27',
            'address' => 'Forgotten street 85B',
            'zip' => '1000',
            'city' => 'Brussels',
            'country' => 17,
            'language' => 3,
        );

        // Check if the user exists : this throws an exception if the user is not found
        $client->getContactId($client->getFieldId('email'), 'johndoe@gmail.com');

        // If no exception is thrown, update the contact
        $response = $client->updateContact($data);
    } catch(\Snowcap\Emarsys\Exception\ServerException $e) {
        switch($e->getCode()) {
            case Response::REPLY_CODE_CONTACT_NOT_FOUND:
                // If the contact is not found, create it
                $response = $client->createContact($data);
                break;
            default:
                throw $e;
                break;
        }
    }

    return $response;
}


$client = new Client(EMARSYS_API_USERNAME, EMARSYS_API_SECRET);


try {
    // Get available languages
    //testAPI($client->getLanguages());

    // Get availables fields
    //testAPI($client->getFields());

    // Create basic contact
    testAPI(sendContact($client));

    // Get a list of emails
    //testAPI($client->getEmails());

    // Get a list of emails with specific status
    //testAPI($client->getEmails(Client::EMAIL_STATUS_READY));

    // Get a list of emails for specific mailing list
    //testAPI($client->getEmails(null, 123));

    // Get a list of emails with specific status and for specific mailing list
    //testAPI($client->getEmails(Client::EMAIL_STATUS_READY, 123));

} catch (\Exception $e) {
    echo $e;
}
