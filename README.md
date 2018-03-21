Emarsys, PHP HTTP client for Emarsys webservice
================================================

Emarsys is a PHP HTTP client based on the official Emarsys web service documentation and based on 

At the time of writing, __only methods related to contacts are production ready__.

__All the other methods__ have been implemented following the documentation but __not yet tested__.

### Installing via Composer

The recommended way to install Emarsys is through [Composer](http://getcomposer.org).

```bash
# Install Composer
curl -sS https://getcomposer.org/installer | php

# Add Emarsys as a dependency
php composer.phar require snowcap/emarsys:*
```

After installing, you need to require Composer's autoloader:

```php
require 'vendor/autoload.php';
```

### Basics

To use the client, you need to instantiate a new one with your credentials. Emarsys is shipped with Guzzle HTTP client.

```php
$username = 'your_username';
$secret = 'your_secret';

$client = new \Emarsys\Client($username, $secret);
```

At this point, you have access to all the methods implemented by the Emarsys API

For example :

```php
// Retrieve a contact from his email address
$response = $client->getContact(array(3 => 'example@example.com'));

// Create a contact with just his email information
$response = $client->createContact(array(3 => 'example@example.com'));

// Create a more complex contact
$response = $client->createContact(array(
    'email'      => 'johndoe@gmail.com',
    'gender'     => $client->getChoiceId('gender', 'male'),
    'salutation' => $client->getChoiceId('salutation', 'mr'),
    'firstName'  => 'John',
    'lastName'   => 'Doe',
    'birthDate'  => '2014-03-27',
    'address'    => 'Forgotten street 85B',
    'zip'        => '1000',
    'city'       => 'Brussels',
    'country'    => 17,
    'language'   => 3,
));
```
### GuzzleClient

Emarsys is shipped with the Guzzle HTTP client. You can pass your configured guzzleclient as the third parameter while creating 
the emarsys client. This is the most time only needed for tests or set specific configurations. Don't pass a baseUrl into your 
own client, this one should pass as fourth parameter.

### BaseUrl

The Url of the client is set to ```https://api.emarsys.net/api/v2/```. 
While creating the client you can pass a other url as the fourth parameter.  

### Custom field mapping

As explained in the Emarsys documentation, each field is referenced by an ID.

You can do a `$response = $client->getFields();` to get the complete list with their ids and names.

But dealing with IDs is not always the easiest way to work.

So, extra methods have been implemented to handle custom mapping.

First of all, a default (non-exhaustive) mapping has been set for the Emarsys pre-defined fields.
You can find it in `src/Emarsys/ini/fields.ini`

But you can add your own by calling :

```php
$client->addFieldsMapping(array('petName' => 7849, 'twitter' => 7850));`
```

In that way, the default mapping and your own are merged and become available instantly as a replacement of these boring IDs.

It means that you can use both IDs and custom names to reference fields, so the two samples below do the same :

```php
$response = $client->createContact(array(1 => 'John', 2 => 'Doe', 3 => 'example@example.com'));
$response = $client->createContact(array('firstName' => 'John', 'lastName' => 'Doe', 'email' => 'example@example.com'));
```

You also have access to additional methods to retrieve a particular ID by name and vice versa.

```php
$fieldId = $client->getFieldId('firstName');
// will return 1;
$fieldName= $client->getFieldName(1);
// will return 'firstName';
```

Last but not least, you can completely override the default mappings by passing an array as the fifth argument of the constructor.

```php
$client = new Client($username, $secret, null, array('firstName' => 1, 'lastName' => 2));
```

You just have to refer to the official Emarsys documentation or the `getFields()` method to identify the right IDs.

### Custom field choice mapping

When we use choice fields, each choice has its own ID, like a field.

You can do a `$response = $client->getFieldChoices(5);` to get the complete list of choices with their ids and names for a specific field (the gender for instance [5]).

But dealing with IDs is still not the easiest way to work.

So, extra methods have been implemented to handle custom mapping.

First of all, a default (non-exhaustive) mapping has been set for the Emarsys pre-defined field choices.
You can find it in `src/Emarsys/ini/choices.ini`

But you can add your own by calling :

```php
$client->addChoicesMapping(array('gender' => array('male' => 1, 'female' => 2)));
```

It means that you can use both IDs and custom names to reference field choices, so the two samples below do the same :

```php
$response = $client->getFieldChoices(5);
$response = $client->getFieldChoices('gender');
```

You also have access to additional methods to retrieve a particular ID by name and vice versa.

```php
$choiceId = $client->getChoiceId('gender', 'male');
// will return 1;
$choiceName= $client->getChoiceName('gender', 1);
// will return 'male';
```

You can of course override it completely the default mappings by passing an array as the sixth argument of the constructor.

```php
$client = new Client($username, $secret, null, null, [], array('gender' => array('male' => 1, 'female' => 2)));
```

You just have to refer to the official Emarsys documentation or the `getFieldChoices()` method to identify the right IDs.

### The response

Almost every methods implementing the API return a new Response object.

This response is a simple class with three properties :

* a _replyCode_
* a _replyText_
* and the _data_

This matches the json response sent by the Emarsys API.

The reply code and reply text are the official reply returned by the Emarsys API.
The data become an associative array representing the actual data (read the official Emarsys documentation, check the inline documentation in the code or var_dump the response)

### Exceptions

The client throws 2 types of exceptions

* a _ClientException_ : which is related to wrong usage of this client
* a _ServerException_ : which is related to wrong usage of the API itself
 
The _ServerException_ is carrying the original reply text and reply code sent by the API.

Some of the reply codes have already been handled as constants, but not all.

This could be very useful, for example : we could check the exception code to see if the contact was not found, then we could create it.
