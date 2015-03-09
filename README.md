# reCAPTCHA PHP client library

[![Build Status](https://travis-ci.org/google/recaptcha.svg)](https://travis-ci.org/google/recaptcha)

* Project page: http://www.google.com/recaptcha/
* Repository: https://github.com/google/recaptcha
* Version: 1.1.0
* License: BSD, see [LICENSE](LICENSE)

## Description

reCAPTCHA is a free CAPTCHA service that protect websites from spam and abuse.
This is Google authored code that provides plugins for third-party integration
with reCAPTCHA.

## Installation

Use [Composer](https://getcomposer.org/) to install the library. Either use
`composer require google/recaptcha "~1.1"` or add the following to your
`composer.json`:
```json
    "require": {
        "google/recaptcha": "~1.1"
    }
```

## Usage

First, register keys for your site at https://www.google.com/recaptcha/admin

When your app receives a form submission containing the `g-recaptcha-response`
field, you can verify it using:
```php
<?php
$recaptcha = new \ReCaptcha\ReCaptcha($secret);
$resp = $recaptcha->verify($gRecaptchaResponse, $remoteIp);
if ($resp->isSuccess()) {
    // verified!
} else {
    $errors = $resp->getErrorCodes();
}
```

You can see an end-to-end working example in [examples/example-recaptcha.php](examples/example-recaptcha.php)

## Contributing

We accept contributions via GitHub Pull Requests, but all contributors need to
be covered by the standard Google Contributor License Agreement. You can find
instructions for this in [CONTRIBUTING](CONTRIBUTING.md)
