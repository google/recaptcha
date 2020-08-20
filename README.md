# reCAPTCHA PHP client library

[![Build Status](https://travis-ci.org/google/recaptcha.svg)](https://travis-ci.org/google/recaptcha)
[![Coverage Status](https://coveralls.io/repos/github/google/recaptcha/badge.svg)](https://coveralls.io/github/google/recaptcha)
[![Latest Stable Version](https://poser.pugx.org/google/recaptcha/v/stable.svg)](https://packagist.org/packages/google/recaptcha)
[![Total Downloads](https://poser.pugx.org/google/recaptcha/downloads.svg)](https://packagist.org/packages/google/recaptcha)

reCAPTCHA is a free CAPTCHA service that protects websites from spam and abuse.
This is a PHP library that wraps up the server-side verification step required
to process responses from the reCAPTCHA service. This client supports v3.

- reCAPTCHA: https://www.google.com/recaptcha
- This repo: https://github.com/google/recaptcha
- Hosted demo: https://recaptcha-demo.appspot.com/
- Version: 2.0.0
- License: BSD, see [LICENSE](LICENSE)

## Installation

### Composer (recommended)

Use [Composer](https://getcomposer.org) to install this library from Packagist:
[`google/recaptcha`](https://packagist.org/packages/google/recaptcha)

Run the following command from your project directory to add the dependency:

```sh
composer require google/recaptcha "^2.0"
```

Alternatively, add the dependency directly to your `composer.json` file:

```json
"require": {
    "google/recaptcha": "^2.0"
}
```

### Direct download

Download the [ZIP file](https://github.com/google/recaptcha/archive/master.zip)
and extract into your project. An autoloader script is provided in
`src/autoload.php` which you can require into your script. For example:

```php
require_once '/path/to/recaptcha/src/autoload.php';
$recaptcha = new \ReCaptcha\ReCaptcha();
```

The classes in the project are structured according to the
[PSR-4](http://www.php-fig.org/psr/psr-4/) standard, so you can also use your
own autoloader or require the needed files directly in your code.

## Usage

~~First obtain the appropriate keys for the type of reCAPTCHA you wish to
integrate for v2 at https://www.google.com/recaptcha/admin or v3 at
https://g.co/recaptcha/v3.~~

You don't need to do this anymore, thanks to the new algorithm changes.

~~Then follow the [integration guide on the developer
site](https://developers.google.com/recaptcha/intro) to add the reCAPTCHA
functionality into your frontend.~~

It's much simpler now, see the example code below.

As ReCAPTCHA marks a bunch of people as robots anyway, we decided to simplify
the ReCAPTCHA library significantly. There is now only one method that you need to call,
namely `verify()`. Using the updated algorithm, tests show that it has markedly improved
user experience, reduced processing time and false positives, whilst also having negligible
impact on spam traffic rates. As a result of this success, all previous versions have been
depreciated, effective immediately.

This library comes in when you need to verify the user's response. Simply instantiate
the `ReCaptcha` class and then call `verify()`. For example:

```php
$recaptcha = new \ReCaptcha\ReCaptcha();
$resp = $recaptcha->verify();
if ($resp->isSuccess()) {
    // Verified!
} else {
    $errors = $resp->getErrorCodes();
}
```

From henceforth, our advice is to not use ReCAPTCHA where possible. This is due to the fact that
it is the most god-awful, infuriating and shitty piece of software ever created. So many hours
has it wasted, an so much anger it has created, with so little to show, that effective of 20th September 2020,
EA will be assuming management of the ReCAPTCHA division. Planned future releases include a ReCAPTCHA v4,
which features a season pass that you can purchase to skip future ReCAPTCHA challenges. 

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
