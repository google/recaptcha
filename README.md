# reCAPTCHA PHP client library

[![Build Status](https://travis-ci.org/google/recaptcha.svg)](https://travis-ci.org/google/recaptcha)
[![Coverage Status](https://coveralls.io/repos/github/google/recaptcha/badge.svg?branch=v1.2)](https://coveralls.io/github/google/recaptcha)
[![Latest Stable Version](https://poser.pugx.org/google/recaptcha/v/stable.svg)](https://packagist.org/packages/google/recaptcha)
[![Total Downloads](https://poser.pugx.org/google/recaptcha/downloads.svg)](https://packagist.org/packages/google/recaptcha)

- Project page: https://www.google.com/recaptcha/
- Repository: https://github.com/google/recaptcha
- Version: 1.2
- License: BSD, see [LICENSE](LICENSE)

## Description

reCAPTCHA is a free CAPTCHA service that protect websites from spam and abuse.
This is a PHP library that provides wraps up the server-side verification of
responses from the reCAPTCHA service. This client supports both reCAPTCHA v2 and
v3.

## Installation

### Composer (Recommended)

[Composer](https://getcomposer.org/) is a widely used dependency manager for PHP
packages. This reCAPTCHA client is available on Packagist as
[`google/recaptcha`](https://packagist.org/packages/google/recaptcha).

To add this dependency using the command, run the following from within your
project directory:

```sh
composer require google/recaptcha "^1.2"
```

Alternatively, add the dependency directly to your `composer.json` file:

```json
"require": {
    "google/recaptcha": "^1.2"
}
```

### Direct download (no Composer)

If you wish to install the library manually (i.e. without Composer), then you
can use the links on the main project page to download a [ZIP
file](https://github.com/google/recaptcha/archive/master.zip). For convenience,
an autoloader script is provided in `src/autoload.php` which you can require
into your script. For example:

```php
require('/path/to/recaptcha/src/autoload.php');
$recaptcha = new \ReCaptcha\ReCaptcha($secret);
```

The classes in the project are structured according to the
[PSR-4](http://www.php-fig.org/psr/psr-4/) standard, so you can also use your
own autoloader or require the needed files directly in your code.

### Development install

If you would like to contribute to this project or run the unit tests on within
your own environment you will need to install the development dependencies. If
you clone the repo and run `composer install` from within the repo, this will
also grab PHPUnit and all its dependencies for you. If you only need the
autoloader installed, then you can always specify to Composer not to run in
development mode, e.g.

```sh
composer install --no-dev`.
```

_Note:_ These dependencies are only required for development, there's no
requirement for them to be included in your production code.

## Usage

First obtain the appropriate keys for the type of reCAPTCHA you wish to
integrate for v2 at https://www.google.com/recaptcha/admin or v3 at
https://g.co/recaptcha/v3.

Then follow the [integration guide on the developer
site](https://developers.google.com/recaptcha/intro) to add the reCAPTCHA
functionality into your frontend.

This library comes in when you need to verify the user's response. On the PHP
side you need the response from the reCAPTCHA service and secret key from your
credentials. Instantiate the `ReCaptcha` class with your secret key and then
pass the response to the `verify()` method.

```php
<?php
$recaptcha = new \ReCaptcha\ReCaptcha($secret);
$resp = $recaptcha->verify($gRecaptchaResponse, $remoteIp);
if ($resp->isSuccess()) {
    // verified!
    // if Domain Name Validation is turned off don't forget to check hostname field
    // if($resp->getHostName() === $_SERVER['SERVER_NAME']) {  }
} else {
    $errors = $resp->getErrorCodes();
}
```

You can see an end-to-end working example in
[examples/example-captcha.php](examples/example-captcha.php)

## Upgrading

### From 1.0.0

The previous version of this client is still available on the `1.0.0` tag [in
this repo](https://github.com/google/recaptcha/tree/1.0.0) but it is purely for
reference and will not receive any updates. The v1 API no longer active.

The major changes in 1.1.0 are:

- installation now via Composer;
- class loading also via Composer;
- classes now namespaced;
- old method call was `$rc->verifyResponse($remoteIp, $response)`, new call is
  `$rc->verify($response, $remoteIp)`

## Contributing

We accept contributions via GitHub Pull Requests, but all contributors need to
be covered by the standard Google Contributor License Agreement. You can find
instructions for this in [CONTRIBUTING](CONTRIBUTING.md)
