# reCAPTCHA PHP client library


[![Latest stable version](https://poser.pugx.org/google/recaptcha/v/stable.svg)](https://packagist.org/packages/google/recaptcha)
[![PHP version required](https://poser.pugx.org/google/recaptcha/require/php)](https://packagist.org/packages/google/recaptcha)
[![Coverage Status](https://coveralls.io/repos/github/google/recaptcha/badge.svg?branch=main)](https://coveralls.io/github/google/recaptcha?branch=main)
[![Total downloads](https://poser.pugx.org/google/recaptcha/downloads.svg)](https://packagist.org/packages/google/recaptcha)
[![License](https://poser.pugx.org/google/recaptcha/license.svg)](https://packagist.org/packages/google/recaptcha)

reCAPTCHA is a free CAPTCHA service that protects websites from spam and abuse.
This is a PHP library that wraps up the server-side verification step required
to process responses from the reCAPTCHA service. This client supports both v2
and v3.

- reCAPTCHA: https://cloud.google.com/security/products/recaptcha
- This repo: https://github.com/google/recaptcha
- Hosted demo: https://recaptcha-demo.appspot.com/
- Version: 1.5.1
- License: BSD, see [LICENSE](LICENSE)

> [!IMPORTANT]
> **reCAPTCHA Enterprise** is supported via the [Google Cloud Recaptcha Enterprise for
PHP client](https://docs.cloud.google.com/php/docs/reference/cloud-recaptcha-enterprise/).

## Installation

### Composer (recommended)

Use [Composer](https://getcomposer.org) to install this library from Packagist:
[`google/recaptcha`](https://packagist.org/packages/google/recaptcha)

Run the following command from your project directory to add the dependency:

```sh
composer require google/recaptcha "^1.5"
```

Alternatively, add the dependency directly to your `composer.json` file:

```json
"require": {
    "google/recaptcha": "^1.5"
}
```

### Support for earlier versions of PHP

From the 1.3 release support moved to PHP 8 and up. For earlier versions, you
will need to stay with the 1.2 releases.

### Direct download

Download the [ZIP file](https://github.com/google/recaptcha/archive/main.zip)
and extract into your project. An autoloader script is provided in
`src/autoload.php` which you can require into your script. For example:

```php
require_once '/path/to/recaptcha/src/autoload.php';
$recaptcha = new \ReCaptcha\ReCaptcha($secret);
```

The classes in the project are structured according to the
[PSR-4](https://www.php-fig.org/psr/psr-4/) standard, so you can also use your
own autoloader or require the needed files directly in your code.

## Usage

First obtain the appropriate keys for the type of reCAPTCHA you wish to
integrate at https://www.google.com/recaptcha/admin.

Then follow the [integration guide on the developer
site](https://developers.google.com/recaptcha/intro) to add the reCAPTCHA
functionality into your frontend.

This library comes in when you need to verify the user's response. On the PHP
side you need the response from the reCAPTCHA service and secret key from your
credentials. Instantiate the `ReCaptcha` class with your secret key, specify any
additional validation rules, and then call `verify()` with the reCAPTCHA
response (usually in `$_POST[\ReCaptcha\ReCaptcha::RESPONSE_KEY]` or the
response from `grecaptcha.execute()` in JS which is in `$gRecaptchaResponse` in
the example) and user's IP address. For example:

```php
<?php
$recaptcha = new \ReCaptcha\ReCaptcha($secret);
$resp = $recaptcha->setExpectedHostname('recaptcha-demo.appspot.com')
                  ->verify($gRecaptchaResponse, $remoteIp);
if ($resp->isSuccess()) {
    // Verified!
} else {
    $errors = $resp->getErrorCodes();
}
```

The following methods are available:

- `setExpectedHostname($hostname)`: ensures the hostname matches. You must do
  this if you have disabled "Domain/Package Name Validation" for your
  credentials. **Note:** if you need to validate against multiple hostnames,
  do not use this method. Instead, check the `$resp->getHostname()` against
  your list of allowed hostnames after calling `verify()`.
- `setExpectedApkPackageName($apkPackageName)`: if you're verifying a response
  from an Android app. Again, you must do this if you have disabled
  "Domain/Package Name Validation" for your credentials.
- `setExpectedAction($action)`: ensures the action matches for the v3 API.
- `setScoreThreshold($threshold)`: set a score threshold for responses from the
  v3 API
- `setChallengeTimeout($timeoutSeconds)`: set a timeout between the user passing
  the reCAPTCHA and your server processing it.

Each of the `set`\*`()` methods return the `ReCaptcha` instance so you can chain
them together. For example:

```php
<?php
$recaptcha = new \ReCaptcha\ReCaptcha($secret);
$resp = $recaptcha->setExpectedHostname('recaptcha-demo.appspot.com')
                  ->setExpectedAction('homepage')
                  ->setScoreThreshold(0.5)
                  ->verify($gRecaptchaResponse, $remoteIp);

if ($resp->isSuccess()) {
    // Verified!
} else {
    $errors = $resp->getErrorCodes();
}
```

You can find the constants for the libraries error codes in the `ReCaptcha`
class constants, e.g. `ReCaptcha::E_HOSTNAME_MISMATCH`

### Alternate request methods

> [!NOTE]
> As of version 1.4.2, the default behavior has changed.

By default, the library will attempt to use [cURL](https://secure.php.net/curl) to make the
POST request to the reCAPTCHA service. This is handled by the
[`RequestMethod\CurlPost`](./src/ReCaptcha/RequestMethod/CurlPost.php) class.
If cURL is not available, it will fall back to using
[`stream_context_create()`](https://secure.php.net/stream_context_create) and
[`file_get_contents()`](https://secure.php.net/file_get_contents) via the
[`RequestMethod\Post`](./src/ReCaptcha/RequestMethod/Post.php) class.

To keep the previous behavior of always using `file_get_contents()` regardless of cURL's availability, you can explicitly configure it:

```php
<?php
$recaptcha = new \ReCaptcha\ReCaptcha($secret, new \ReCaptcha\RequestMethod\Post());
```

You may need to use other methods for making requests in your environment. The
[`ReCaptcha`](./src/ReCaptcha/ReCaptcha.php) class allows an optional
[`RequestMethod`](./src/ReCaptcha/RequestMethod.php) instance to configure this.
For example, if you want to force the use of [cURL](https://secure.php.net/curl) you
can do this:

```php
<?php
$recaptcha = new \ReCaptcha\ReCaptcha($secret, new \ReCaptcha\RequestMethod\CurlPost());
```

Alternatively, you can also use a [socket](https://secure.php.net/fsockopen):

```php
<?php
$recaptcha = new \ReCaptcha\ReCaptcha($secret, new \ReCaptcha\RequestMethod\SocketPost());
```

For more details on usage and structure, see [ARCHITECTURE](ARCHITECTURE.md).

The 1.x line preserves compatibility for the public request and response APIs.
See [Public API compatibility](ARCHITECTURE.md#public-api-compatibility) for
details on which API changes require a major release.


### Examples

You can see examples of each reCAPTCHA type in [examples/](examples/). You can
run the examples locally by using the Composer script:

```sh
composer run-script serve-examples
```

This makes use of the in-built PHP dev server to host the examples at
http://localhost:8080/

These are also hosted on Google AppEngine Flexible environment at
https://recaptcha-demo.appspot.com/. This is configured by
[`app.yaml`](./app.yaml) which you can also use to [deploy to your own AppEngine
project](https://cloud.google.com/appengine/docs/flexible/php/download).

## Contributing

No one ever has enough engineers, so we're very happy to accept contributions
via Pull Requests. For details, see [CONTRIBUTING](CONTRIBUTING.md)

To set up your local checkout, install the dependencies:

```bash
composer install
```

If you add new dependencies to the project, make sure you commit the lock file:

```bash
composer update
git add composer.json composer.lock
```

Before committing code, make sure it meets the quality and formatting standards:

```bash
composer run phpstan
composer run lint-fix
```

Run the tests before submitting. Make sure you add or update tests to cover any changes you make:

```bash
composer run test
```
