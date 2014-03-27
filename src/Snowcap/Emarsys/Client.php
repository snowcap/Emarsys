<?php

namespace Snowcap\Emarsys;

use Guzzle\Http\Client as GuzzleClient;
use Guzzle\Http\Exception\ClientErrorResponseException;
use Guzzle\Http\Message\RequestInterface;

use Snowcap\Emarsys\Exception\ClientException;
use Snowcap\Emarsys\Exception\ServerException;

class Client
{
    const EMAIL_STATUS_IN_DESIGN = 1;
    const EMAIL_STATUS_TESTED = 2;
    const EMAIL_STATUS_LAUNCHED = 3;
    const EMAIL_STATUS_READY = 4;
    const EMAIL_STATUS_DEACTIVATED = -3;

    const LAUNCH_STATUS_NOT_LAUNCHED = 0;
    const LAUNCH_STATUS_IN_PROGRESS = 1;
    const LAUNCH_STATUS_SCHEDULED = 2;
    const LAUNCH_STATUS_ERROR = -10;

    /**
     * @var string
     */
    protected $baseUrl = 'https://suite6.emarsys.net/api/v2/';
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
     * @var array
     */
    protected $fieldsMapping = array();

    /**
     * @var array
     */
    protected $choicesMapping = array();

    /**
     * @param string $username The username requested by the Emarsys API
     * @param string $secret The secret requested by the Emarsys API
     * @param string $baseUrl Overrides the default baseUrl if needed
     * @param array $fieldsMapping Overrides the default fields mapping if needed
     * @param array $choicesMapping Overrides the default choices mapping if needed
     */
    public function __construct($username, $secret, $baseUrl = null, $fieldsMapping = array(), $choicesMapping = array())
    {
        $this->username = $username;
        $this->secret = $secret;

        if (null !== $baseUrl) {
            $this->baseUrl = $baseUrl;
        }

        if (count($fieldsMapping) > 0) {
            $this->fieldsMapping = $fieldsMapping;
        } else {
            $this->fieldsMapping = $this->parseIniFile('fields.ini');
        }

        if (count($choicesMapping) > 0) {
            $this->choicesMapping = $choicesMapping;
        } else {
            $this->choicesMapping = $this->parseIniFile('choices.ini');
        }

        $this->client = new GuzzleClient($this->baseUrl);
    }

    /**
     * Add your custom fields mapping
     * This is useful if you want to use string identifiers instead of ids when you play with contacts fields
     *
     * Example:
     *  $mapping = array(
     *      'myCustomField' => 7147,
     *      'myCustomField2' => 7148,
     *  );
     *
     * @param array $mapping
     */
    public function addFieldsMapping($mapping = array())
    {
        $this->fieldsMapping = array_merge($this->fieldsMapping, $mapping);
    }

    /**
     * Add your custom field choices mapping
     * This is useful if you want to use string identifiers instead of ids when you play with contacts field choices
     *
     * Example:
     *  $mapping = array(
     *      'myCustomField' => array(
     *          'myCustomChoice' => 1,
     *          'myCustomChoice2' => 2,
     *      )
     *  );
     *
     * @param array $mapping
     */
    public function addChoicesMapping($mapping = array())
    {
        $this->choicesMapping = array_merge($this->choicesMapping, $mapping);
    }

    /**
     * Returns a field id from a field name (specified in the fields mapping)
     *
     * @param string|int $field
     * @return int
     * @throws Exception\ClientException
     */
    public function getFieldId($field)
    {
        if (is_numeric($field)) {
            return (int)$field;
        }
        if (isset($this->fieldsMapping[$field])) {
            return (int)$this->fieldsMapping[$field];
        }

        throw new ClientException(sprintf('Unrecognized field name "%s"', $field));
    }

    /**
     * Returns a field name from a field id (specified in the fields mapping) or the field id if no mapping is found
     *
     * @param int $fieldId
     * @return string|int
     */
    public function getFieldName($fieldId)
    {
        $fieldName = array_search($fieldId, $this->fieldsMapping);

        if ($fieldName) {
            return $fieldName;
        }

        return $fieldId;
    }

    /**
     * Returns a choice id for a field from a choice name (specified in the choices mapping)
     *
     * @param string|int $field
     * @param string|int $choice
     * @throws Exception\ClientException
     * @return int
     */
    public function getChoiceId($field, $choice)
    {
        if (is_numeric($choice)) {
            return (int)$choice;
        }
        if (!isset($this->choicesMapping[$this->getFieldName($field)])) {
            throw new ClientException(sprintf('Unrecognized field "%s" for choice "%s"', $field, $choice));
        }
        if (!isset($this->choicesMapping[$this->getFieldName($field)][$choice])) {
            throw new ClientException(sprintf('Unrecognized choice "%s" for field "%s"', $choice, $field));
        }

        return (int)$this->choicesMapping[$this->getFieldName($field)][$choice];
    }

    /**
     * Returns a choice name for a field from a choice id (specified in the choices mapping) or the choice id if no mapping is found
     *
     * @param string|int $field
     * @param int $choiceId
     * @throws Exception\ClientException
     * @return string|int
     */
    public function getChoiceName($field, $choiceId)
    {
        if(!isset($this->choicesMapping[$this->getFieldId($field)])) {
            throw new ClientException(sprintf('Unrecognized field "%s" for choice id "%s"', $field, $choiceId));
        }
        $field = array_search($choiceId, $this->fieldsMapping[$this->getFieldId($field)]);

        if ($field) {
            return $field;
        }

        return $choiceId;
    }

    /**
     * Returns a list of condition rules.
     *
     * @return Response
     */
    public function getConditions()
    {
        return $this->send(RequestInterface::GET, 'condition');
    }

    /**
     * Creates one or more new contacts/recipients.
     * Example :
     *  $data = array(
     *      'key_id' => '3',
     *      '3' => recipient@example.com',
     *      'source_id' => '123',
     *  );
     * @param array $data
     * @return Response
     */
    public function createContact($data)
    {
        return $this->send(RequestInterface::POST, 'contact', array(), $this->mapFieldsToIds($data));
    }

    /**
     * Updates one or more contacts/recipients, identified by an external ID.
     *
     * @param array $data
     * @return Response
     */
    public function updateContact($data)
    {
        return $this->send(RequestInterface::PUT, 'contact', array(), $this->mapFieldsToIds($data));
    }

    /**
     * Returns the internal ID of a contact specified by its external ID.
     *
     * @param string $fieldId
     * @param string $fieldValue
     * @throws Exception\ClientException
     * @return Response
     */
    public function getContactId($fieldId, $fieldValue)
    {
        $response = $this->send(RequestInterface::GET, sprintf('contact/%s=%s', $fieldId, $fieldValue));

        $data = $response->getData();

        if (isset($data['id'])) {
            return $data['id'];
        }

        throw new ClientException('Missing "id" in response');
    }

    /**
     * Exports the selected fields of all contacts with properties changed in the time range specified.
     *
     * @param array $data
     * @return Response
     */
    public function getContactChanges($data)
    {
        return $this->send(RequestInterface::POST, 'contact/getchanges', array(), $data);
    }

    /**
     * Returns the list of emails sent to the specified contacts.
     *
     * @param array $data
     * @return Response
     */
    public function getContactHistory($data)
    {
        return $this->send(RequestInterface::POST, 'contact/getcontacthistory', array(), $data);
    }

    /**
     * Returns all data associated with a contact.
     *
     * Example:
     *
     *  $data = array(
     *      'keyId' => 3, // contact element used as a key to select the contacts.To use the internalID, pass"id" to the "keyId" parameter.
     *      'keyValues' => array('example@example.com', 'example2@example.com') // an array containing contactIDs or values of the column used to select contacts
     *  );
     *
     *
     * @param array $data
     * @param bool $responseWithFieldNames Select true if you want to map field ids to field names
     * @return Response
     */
    public function getContactData($data, $responseWithFieldNames = true)
    {
        $response = $this->send(RequestInterface::POST, 'contact/getdata', array(), $data);

        if ($responseWithFieldNames) {
            $data = $response->getData();
            foreach ($data['result'] as $key => $contact) {
                $data['result'][$key] = $this->mapIdsToFields($contact);
            }
            $response->setData($data);
        }

        return $response;
    }

    /**
     * Exports the selected fields of all contacts which registered in the specified time range.
     *
     * @param array $data
     * @return Response
     */
    public function getContactRegistrations($data)
    {
        return $this->send(RequestInterface::POST, 'contact/getregistrations', array(), $data);
    }

    /**
     * Returns a list of contact lists which can be used as recipient source for the email.
     *
     * @param array $data
     * @return Response
     */
    public function getContactList($data)
    {
        return $this->send(RequestInterface::GET, 'contactlist', array(), $data);
    }

    /**
     * Creates a contact list which can be used as recipient source for the email.
     *
     * @param array $data
     * @return Response
     */
    public function createContactList($data)
    {
        return $this->send(RequestInterface::POST, 'contactlist', array(), $data);
    }

    /**
     * Creates a contact list which can be used as recipient source for the email.
     *
     * @param string $listId
     * @param array $data
     * @return Response
     */
    public function addContactsToContactList($listId, $data)
    {
        return $this->send(RequestInterface::POST, sprintf('contactlist/%s/add', $listId), array(), $data);
    }

    /**
     * This deletes contacts from the contact list which can be used as recipient source for the email.
     *
     * @param string $listId
     * @param array $data
     * @return Response
     */
    public function removeContactsFromContactList($listId, $data)
    {
        return $this->send(RequestInterface::POST, sprintf('contactlist/%s/delete', $listId), array(), $data);
    }

    /**
     * Returns a list of emails.
     *
     * @param int|null $status
     * @param int|null $contactList
     * @return Response
     */
    public function getEmails($status = null, $contactList = null)
    {
        $data = array();
        if (null !== $status) {
            $data['status'] = $status;
        }
        if (null !== $contactList) {
            $data['contactlist'] = $contactList;
        }
        $url = 'email';
        if (count($data) > 0) {
            $url = sprintf('%s/%s', $url, http_build_query($data));
        }

        return $this->send(RequestInterface::GET, $url);
    }

    /**
     * Creates an email in eMarketing Suite and assigns it the respective parameters.
     * Example :
     *  $data = array(
     *      'language' => 'en',
     *      'name' => 'test api 010',
     *      'fromemail' => 'sender@example.com',
     *      'fromname' => 'sender email',
     *      'subject' => 'subject here',
     *      'email_category' => '17',
     *      'html_source' => '<html>Hello $First Name$,... </html>',
     *      'text_source' => 'email text',
     *      'segment' => 1121,
     *      'contactlist' => 0,
     *      'unsubscribe' => 1,
     *      'browse' => 0,
     *  );
     *
     * @param array|object $data
     * @return Response
     */
    public function createEmail($data)
    {
        return $this->send(RequestInterface::POST, 'email', array(), (object)$data);
    }

    /**
     * Returns the attributes of an email and the personalized text and HTML source.
     *
     * @param string $emailId
     * @param array $data
     * @return Response
     */
    public function getEmail($emailId, $data)
    {
        return $this->send(RequestInterface::GET, sprintf('email/%s', $emailId), array(), $data);
    }

    /**
     * Launches an email. This is an asynchronous call, which returns 'OK' if the email is able to launch.
     *
     * @param string $emailId
     * @param array $data
     * @return Response
     */
    public function launchEmail($emailId, $data)
    {
        return $this->send(RequestInterface::POST, sprintf('email/%s/launch', $emailId), array(), $data);
    }

    /**
     * Returns the HTML or text version of the email either as content type 'application/json' or 'text/html'.
     *
     * @param string $emailId
     * @param array $data
     * @return Response
     */
    public function previewEmail($emailId, $data)
    {
        return $this->send(RequestInterface::POST, sprintf('email/%s/launch', $emailId), array(), $data);
    }

    /**
     * Returns the summary of the responses of a launched, paused, activated or deactivated email.
     *
     * @param string $emailId
     * @param array $data
     * @return Response
     */
    public function getEmailResponseSummary($emailId, $data)
    {
        return $this->send(RequestInterface::POST, sprintf('email/%s/responsesummary', $emailId), array(), $data);
    }

    /**
     * Instructs the system to send a test email.
     *
     * @param string $emailId
     * @param array $data
     * @return Response
     */
    public function sendEmailTest($emailId, $data)
    {
        return $this->send(RequestInterface::POST, sprintf('email/%s/sendtestmail', $emailId), array(), $data);
    }

    /**
     * Returns the URL to the online version of an email, provided it has been sent to the specified contact.
     *
     * @param string $emailId
     * @param array $data
     * @return Response
     */
    public function getEmailUrl($emailId, $data)
    {
        return $this->send(RequestInterface::POST, sprintf('email/%s/url', $emailId), array(), $data);
    }

    /**
     * Returns the delivery status of an email.
     *
     * @param array $data
     * @return Response
     */
    public function getEmailDeliveryStatus($data)
    {
        return $this->send(RequestInterface::POST, 'email/getdeliverystatus', array(), $data);
    }

    /**
     * Lists all the launches of an email with ID, launch date and 'done' status.
     *
     * @param array $data
     * @return Response
     */
    public function getEmailLaunches($data)
    {
        return $this->send(RequestInterface::GET, 'email/getlaunchesofemail', array(), $data);
    }

    /**
     * Exports the selected fields of all contacts which responded to emails in the specified time range.
     *
     * @param array $data
     * @return Response
     */
    public function getEmailResponses($data)
    {
        return $this->send(RequestInterface::POST, 'email/getresponses', array(), $data);
    }

    /**
     * Returns a list of email categories which can be used in email creation.
     *
     * @param array $data
     * @return Response
     */
    public function getEmailCategories($data)
    {
        return $this->send(RequestInterface::GET, 'emailcategory', array(), $data);
    }

    /**
     * Returns a list of external events which can be used in program s .
     *
     * @param array $data
     * @return Response
     */
    public function getEvents($data)
    {
        return $this->send(RequestInterface::GET, 'event', array(), $data);
    }

    /**
     * Triggers the given event for the specified contact.
     *
     * @param string $eventId
     * @param array $data
     * @return Response
     */
    public function triggerEvent($eventId, $data)
    {
        return $this->send(RequestInterface::POST, sprintf('event/%s/trigger', $eventId), array(), $data);
    }

    /**
     * Fetches the status data of an export.
     *
     * @param array $data
     * @return Response
     */
    public function getExportStatus($data)
    {
        return $this->send(RequestInterface::GET, 'export', array(), $data);
    }

    /**
     * Returns a list of fields (including custom fields and vouchers) which can be used to personalize content.
     *
     * @return Response
     */
    public function getFields()
    {
        return $this->send(RequestInterface::GET, 'field');
    }

    /**
     * Returns the choice options of a field.
     *
     * @param string $fieldId Field ID or custom field name (available in fields mapping)
     * @return Response
     */
    public function getFieldChoices($fieldId)
    {
        return $this->send(RequestInterface::GET, sprintf('field/%s/choice', $this->getFieldId($fieldId)));
    }

    /**
     * Returns a customer's files.
     *
     * @param array $data
     * @return Response
     */
    public function getFiles($data)
    {
        return $this->send(RequestInterface::GET, 'file', array(), $data);
    }

    /**
     * Uploads a file to a media database.
     *
     * @param array $data
     * @return Response
     */
    public function uploadFile($data)
    {
        return $this->send(RequestInterface::POST, 'file', array(), $data);
    }

    /**
     * Returns a list of segments which can be used as recipient source for the email.
     *
     * @param array $data
     * @return Response
     */
    public function getSegments($data)
    {
        return $this->send(RequestInterface::GET, 'filter', array(), $data);
    }

    /**
     * Returns a customer's folders.
     *
     * @param array $data
     * @return Response
     */
    public function getFolders($data)
    {
        return $this->send(RequestInterface::GET, 'folder', array(), $data);
    }

    /**
     * Returns a list of the customer's forms.
     *
     * @param array $data
     * @return Response
     */
    public function getForms($data)
    {
        return $this->send(RequestInterface::GET, 'form', array(), $data);
    }

    /**
     * Returns a list of languages which you can use in email creation.
     *
     * @return Response
     */
    public function getLanguages()
    {
        return $this->send(RequestInterface::GET, 'language');
    }

    /**
     * Returns a list of sources which can be used for creating contacts.
     *
     * @return Response
     */
    public function getSources()
    {
        return $this->send(RequestInterface::GET, 'source');
    }

    /**
     * Deletes an existing source.
     *
     * @param string $sourceId
     * @return Response
     */
    public function deleteSource($sourceId)
    {
        return $this->send(RequestInterface::DELETE, sprintf('source/%s/delete', $sourceId));
    }

    /**
     * Creates a new source for the customer with the specified name.
     *
     * @param $data
     * @return Response
     */
    public function createSource($data)
    {
        return $this->send(RequestInterface::POST, 'source/create', array(), $data);
    }

    /**
     * @param string $method
     * @param string|null $uri
     * @param array $headers
     * @param string|resource|array $body
     * @param array $options
     * @return Response
     * @throws ServerException
     */
    public function send($method = 'GET', $uri = null, $headers = array(), $body = null, $options = array())
    {
        $request = $this->createRequest($method, $uri, $headers, $body ? json_encode($body) : null, $options);

        try {
            $response = $request->send();

            return $this->getResponse($response->json());
        } catch (ClientErrorResponseException $e) {
            $response = $e->getResponse();
            $result = $this->getResponse($response->json());

            throw new ServerException($result->getReplyText(), $result->getReplyCode());
        }

    }

    /**
     * @param string $method
     * @param string $uri
     * @param array $headers
     * @param string $body
     * @param array $options
     * @return \Guzzle\Http\Message\RequestInterface
     */
    public function createRequest($method = 'GET', $uri = null, $headers = array(), $body = null, $options = array())
    {
        $headers = array_merge(
            array(
                'Content-Type' => 'application/json',
                'X-WSSE' => $this->getAuthenticationSignature(),
            ),
            $headers
        );

        $request = $this->client->createRequest($method, $uri, $headers, $body, $options);

        return $request;
    }

    /**
     * @param array $response
     * @return Response
     */
    public function getResponse(array $response)
    {
        return new Response($response);
    }

    /**
     * Generate X-WSSE signature used to authenticate
     *
     * @return string
     */
    protected function getAuthenticationSignature()
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

        return $signature;
    }

    /**
     * Convert field names to field ids
     *
     * @param array $data
     * @return array
     */
    protected function mapFieldsToIds(array $data)
    {
        $mappedData = array();

        foreach ($data as $name => $value) {
            if (is_numeric($name)) {
                $mappedData[(int)$name] = $value;
            } else {
                $mappedData[$this->getFieldId($name)] = $value;
            }
        }

        return $mappedData;
    }

    /**
     * Convert field ids to field names
     *
     * @param array $data
     * @return array
     */
    protected function mapIdsToFields(array $data)
    {
        $mappedData = array();

        foreach ($data as $id => $value) {
            if (is_numeric($id)) {
                $mappedData[$this->getFieldName($id)] = $value;
            } else {
                $mappedData[$id] = $value;
            }
        }

        return $mappedData;
    }

    /**
     * @param string $filename
     * @return array
     */
    protected function parseIniFile($filename)
    {
        $data = parse_ini_file(__DIR__ . '/ini/' . $filename, true);

        return $this->castIniFileValues($data);
    }

    /**
     * @param mixed $data
     * @return mixed
     */
    protected function castIniFileValues($data)
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->castIniFileValues($value);
            } elseif (is_numeric($value)) {
                $data[$key] = (int)$value;
            }
        }

        return $data;
    }
}