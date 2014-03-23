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
     * @param string|null $baseUrl
     */
    public function __construct($username, $secret, $baseUrl = null)
    {
        $this->username = $username;
        $this->secret = $secret;

        if (null !== $baseUrl) {
            $this->baseUrl = $baseUrl;
        }

        $this->client = new GuzzleClient($this->baseUrl);
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
     *
     * @param array $data
     * @return Response
     */
    public function createContact($data)
    {
        return $this->send(RequestInterface::POST, 'contact', array(), $data);
    }

    /**
     * Updates one or more contacts/recipients, identified by an external ID.
     *
     * @param array $data
     * @return Response
     */
    public function updateContact($data)
    {
        return $this->send(RequestInterface::PUT, 'contact', array(), $data);
    }

    /**
     * Returns the internal ID of a contact specified by its external ID.
     *
     * @param string $fieldId
     * @param string $fieldValue
     * @return Response
     */
    public function getContact($fieldId, $fieldValue)
    {
        return $this->send(RequestInterface::GET, sprintf('contact/%s=%s', $fieldId, $fieldValue));
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
     * @param array $data
     * @return Response
     */
    public function getContactData($data)
    {
        return $this->send(RequestInterface::GET, 'contact/getdata', array(), $data);
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
     *
     * @param string $language
     * @param string $name
     * @param string $fromEmail
     * @param string $fromName
     * @param string $subject
     * @param string $emailCategory
     * @param string $htmlSource
     * @param string $textSource
     * @param int|null $segment
     * @param int|null $contactList
     * @param int|null $unsubscribe
     * @param int|null $browse
     * @throws Exception\ClientException
     * @return Response
     */
    public function createEmail($language, $name, $fromEmail, $fromName, $subject, $emailCategory, $htmlSource, $textSource, $segment = null, $contactList = null, $unsubscribe = null, $browse = null)
    {
        $data = array(
            'language' => $language,
            'name' => $name,
            'fromemail' => $fromEmail,
            'fromname' => $fromName,
            'subject' => $subject,
            'email_category' => $emailCategory,
            'html_source' => $htmlSource,
            'text_source' => $textSource,
        );
        if (empty($segment) && empty($contactList)) {
            throw new ClientException('Missing segment or contactList');
        }
        $data['segment'] = $segment ?: 0;
        $data['contactlist'] = $contactList ?: 0;

        if (null !== $unsubscribe) {
            $data['unsubscribe'] = $unsubscribe;
        }
        if (null !== $browse) {
            $data['browse'] = $browse;
        }

        return $this->send(RequestInterface::POST, 'email', array(), (object) $data);
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
     * @param array $data
     * @return Response
     */
    public function getFields($data)
    {
        return $this->send(RequestInterface::GET, 'field', array(), $data);
    }

    /**
     * Returns the choice options of a field.
     *
     * @param string $fieldId
     * @param array $data
     * @return Response
     */
    public function getFieldChoices($fieldId, $data)
    {
        return $this->send(RequestInterface::GET, sprintf('field/%s/choice', $fieldId), array(), $data);
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
     * @param string|null $body
     * @param array $options
     * @return Response
     * @throws ServerException
     */
    public function send($method = 'GET', $uri = null, $headers = array(), $body = null, $options = array())
    {
        $request = $this->createRequest($method, $uri, $headers, $body, $options);

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
}