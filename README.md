Emarsys, PHP HTTP client for Emarsys webservice
================================================

Emarsys is a PHP HTTP client based on Emarsys web service documentation.

This is a WIP and should not be used in production without further tests

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

To use the client, you just need to load the client with your credentials

```php
define('EMARSYS_API_USERNAME', 'your_username');
define('EMARSYS_API_SECRET', 'your_secret');
// ...
$client = new Client(EMARSYS_API_USERNAME, EMARSYS_API_SECRET);
```

At that point, you have access to all the methods implemented by the Emarsys API

For example :

```php
// Retrieve a contact from his email address
$response = $client->getContact(array(3 => 'example@example.com'));

// Create a contact with just his email information
$response = $client->createContact(array(3 => 'example@example.com'));

// Create a more complex contact
$response = $client->createContact(array(1 => 'John', 2 => 'Doe', 3 => 'example@example.com'));
```

As explained in the Emarsys documentation, each field has an ID

You can do a `$response = $client->getFields();` to get the complete list with their ids and names.

### Custom mapping

Dealing with IDs is not always the easiest way to work.

So, extra methods have been implemented to handle custom mapping.

First of all, a default non-exhaustive mapping has been set for the Emarsys pre-defined fields and choices.
You can find them in `src/Snowcap/Emarsys/ini/fields.ini` and `src/Snowcap/Emarsys/ini/choices.ini`.

But you can add your own by calling 

```php
$client->addFieldsMapping(array('petName' => 7849, 'twitter' => 7850));`
```

In that way, the default mapping and your own are merged and become available instantly as replacement of these boring IDs.

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

Last but not least, you can completely overrid the default mappings by passing an array as a third argument of the constructor.

```php
$client = new Client(EMARSYS_API_USERNAME, EMARSYS_API_SECRET, array('MyAwsomeFirstname' => 1, 'MyAwsomeLastname' => 2));
```

You just have to refer to the documentation or the `getFields()` method to identify the right IDs.
