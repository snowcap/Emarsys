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