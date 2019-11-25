# reCAPTCHA PHP Client Library

![reCATPCHA challenge](https://developers.google.com/recaptcha/images/newCaptchaAnchor.gif)

[![Build Status](https://travis-ci.org/google/recaptcha.svg)](https://travis-ci.org/google/recaptcha)
[![Coverage Status](https://coveralls.io/repos/github/google/recaptcha/badge.svg)](https://coveralls.io/github/google/recaptcha)
[![Latest Stable Version](https://poser.pugx.org/google/recaptcha/v/stable.svg)](https://packagist.org/packages/google/recaptcha)
[![Total Downloads](https://poser.pugx.org/google/recaptcha/downloads.svg)](https://packagist.org/packages/google/recaptcha)

reCAPTCHA is a free CAPTCHA service that protects websites from spam and abuse.

This is a PHP library that wraps up the server-side verification step required to process responses from the reCAPTCHA service. This client supports both v2 (Android, checkbox, invisible) and v3 (score).

* reCAPTCHA: https://www.google.com/recaptcha
* This repo: https://github.com/google/recaptcha
* Hosted demo: https://recaptcha-demo.appspot.com/
* Version: 2.0.0
* License: BSD, see [LICENSE](LICENSE.md)

## Installation

Use [Composer](https://getcomposer.org/) to install this package [from Packagist](https://packagist.org/packages/google/recaptcha) into your project:

```bash
composer require google/recaptcha
```

If your project doesn't have any HTTP Client or a Message Factory, which you probably don't, it's suggested to also install the Symfony HTTP Client along with Nyholm PSR-17 factories.

```bash
composer require symfony/http-client nyholm/psr7
```

## Usage

This package works out-of-the-box: after you integrate reCAPTCHA in your web frontend or Android app, include your reCAPTCHA keys in this package and you're ready to start verifying the challenges from your server.

```php
<?php

use Google\ReCaptcha\ReCaptcha;

$response = ReCaptcha::validate($secret, $recaptchaToken, $userIp);

if ($response) {
    echo 'You are a human!';
} else {
    echo 'You are a robot!';
}
```

The `validate()` method conveniently creates a new `ReCaptcha` instance using just your secret key, wires an HTTP Client automatically, and returns the verification response.

> To use `validate()` or `make()`, ensure you have [installed the suggested HTTP Client and Message Factories](#installation).

### Constraints

Sometimes it may be not enough to just verify if the reCAPTCHA challenge was completed. You can use these fluent methods to verify further the response from reCAPTCHA servers and check if the challenge is faithfully valid:

* `hostname()`: Ensures the hostname from the challenge matches. Use this method if you disabled _Domain/Package Name Validation_ for your credentials.
* `apkPackageName()`: Ensures the APK Package Name from the challenge matches. Use this method if you disabled _Domain/Package Name Validation_ for your credentials.
* `challengeTs()`: Ensures the seconds elapsed between the reCAPTCHA challenge completion and your server retrieving the result from the reCAPTCHA servers is **below** the one set here. 
 
For reCAPTCHA v3, there are two methods to further constrain the verification.

* `action()`: Ensures the action from the challenge matches.
* `threshold()`: Ensures the score is **above** the specified threshold.

```php
<?php

$response = ReCaptcha::make($secret)
                     ->hostname('recaptcha-demo.appspot.com')
                     ->action('homepage')
                     ->threshold(0.5)
                     ->challengeTs(10)
                     ->verify($recaptchaToken, $userIp);
```

You can use the `saneAction()` to automatically replace invalid characters from the `action` parameter, which is useful when you want to automatically put your URL path in your application as the action name, instead of doing manually for each page, which can be cumbersome on large applications.

```php
<?php

echo Request::path(); // "/example/action-for-this-page?foo=bar&done=yes"

$response = ReCaptcha::make($secret)
                     ->saneAction(Request::urlPath())
                     ->verify($recaptchaToken, $userIp);

echo $response->constraint('action'); // "/example/action_for_this_page"
```

## Verification

By default, reCAPTCHA verification returns response from reCAPTCHA servers. You can use the `valid()` method to check if the challenge is valid and has no errors, while the `invalid()` method will check if it has failed or contains errors from the server or constraints set by you.

```php
<?php

$response =  ReCaptcha::make($secret)
                      ->verify($recaptchaToken, $userIp);

if ($response->invalid()) {
    return 'The challenge failed';
}

return 'Success!';
```

### On Failure Exception

You can use the `verifyOrThrow()` method to conveniently throw a `FailedReCaptchaException` when the challenge is invalid, allowing your application to identify the error and proceed accordingly, like logging the event or pass the exception to an exception handler.

```php
<?php

use App\Logger;
use App\Redirect;
use Google\ReCaptcha\ReCaptcha;
use Google\ReCaptcha\FailedReCaptchaException;

try {
    $response = ReCaptcha::make($secret)
                         ->verifyOrThrow($recaptchaToken, $ip);
} catch (FailedReCaptchaException $exception) {
    Logger::info("The $ip has failed the reCAPTCHA challenge");
    
    return Redirect::previous()->withErrors([
        'Please try completing the reCAPTCHA challenge again.'
    ]);
}
```

## Errors

When a verification fails, you will have available an array of errors consisting in the parts that failed the challenge verification from the server and constraints set previously.

To access the array you can use `errors()`.

```php
<?php

use Google\ReCaptcha\ReCaptcha;

$response =  ReCaptcha::make($secret)->verify($recaptchaToken, $userIp);

if ($response->invalid()) {
    return var_dump($response->errors());
}
```

Each error present in the array is a string declared as constant in the `ReCaptchaError` class, like `ReCaptchaError::E_CONNECTION_FAILED`.

You can use `hasError()` to identify if a particular error was thrown in the response for further processing.

```php
<?php

use Google\ReCaptcha\ReCaptchaErrors;
use Google\ReCaptcha\ReCaptcha;

$response =  ReCaptcha::make($secret)
                      ->verify($recaptchaToken, $userIp);

if ($response->hasErrors(ReCaptchaErrors::E_UNKNOWN_ERROR)) {
    return 'The reCAPTCHA servers are unavailable for now.';
}
```

### Constraints array

When a response is received, you will have access to the initial constraints set previously by using the `constraints()` method.

```php
<?php

$response = ReCaptcha::make($secret)
                     ->threshold(0.7)
                     ->verify($recaptchaToken, $userIp);

if ($response->failed()) {
    var_dump($response->constraints());
}
```

You can also retrieve a single constraint using the `constraint()` method along with the constraint name. If the returned value is `null`, it's because the constraint was not previously set.

```php
<?php

$response = ReCaptcha::make($secret)
                     ->threshold(0.7)
                     ->verify($recaptchaToken, $userIp);

echo $response->constraint('threshold'); // "0.7"
echo $response->constraint('challenge_ts'); // null
```

## HTTP Clients

When using [Symfony HTTP Client](https://symfony.com/doc/current/components/http_client.html), this package uses [cURL](https://curl.haxx.se/) to make a POST request to the reCAPTCHA service using [HTTP/2](https://developers.google.com/web/fundamentals/performance/http2), fallback to native PHP stream.

To use another HTTP Client, instance the class using any PSR-18 HTTP Client along the PSR-17 Message Factories required.

You must set the secret separately.

```php
<?php

use Google\ReCaptcha\ReCaptcha;
use App\SocketHttpClient;
use App\RequestFactory;
use App\StreamFactory;

$recaptcha = new ReCaptcha(new SocketHttpClient,
                           new RequestFactory,
                           new StreamFactory);

$recaptcha->setSecret($secret);
```

## Using reCAPTCHA V2 and V3

There may be some edge cases where you need to use reCAPTCHA v2 and v3 at the same time - consider using [Invisible reCAPTCHA](https://developers.google.com/recaptcha/docs/invisible) invoked automatically.  You can safely create a `ReCaptcha` instance using each secret key. Nothing more is needed since the secret dictates which version to use.

```php
<?php

use Google\ReCaptcha\ReCaptcha;

$version2 = ReCaptcha::make($secretV2);

$version3 = ReCaptcha::make($secretV3);
``` 

If your application implements [PSR-11 Container interface](https://www.php-fig.org/psr/psr-11/), it can be useful to save these instances separately with different names.

## Examples

You can run the [examples](examples/) locally by just running a convenient Composer script to run a local PHP server in `http://localhost:8080`:

```bash
composer run-script serve-examples
```

Alternatively, you can check the hosted Google AppEngine Flexible environment at [`https://recaptcha-demo.appspot.com`](https://recaptcha-demo.appspot.com). This is configured by [`app.yaml`](app.yaml) which you can also use to [deploy to your own AppEngine project](https://cloud.google.com/appengine/docs/flexible/php/download).
 
## Contributing

No one ever has enough engineers, so we're very happy to accept contributions via Pull Requests. For details, see [CONTRIBUTING](CONTRIBUTING.md).