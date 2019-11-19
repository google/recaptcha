# reCAPTCHA PHP Client Library

![reCATPCHA challenge](https://developers.google.com/recaptcha/images/newCaptchaAnchor.gif)

[![Build Status](https://travis-ci.org/google/recaptcha.svg)](https://travis-ci.org/google/recaptcha)
[![Coverage Status](https://coveralls.io/repos/github/google/recaptcha/badge.svg)](https://coveralls.io/github/google/recaptcha)
[![Latest Stable Version](https://poser.pugx.org/google/recaptcha/v/stable.svg)](https://packagist.org/packages/google/recaptcha)
[![Total Downloads](https://poser.pugx.org/google/recaptcha/downloads.svg)](https://packagist.org/packages/google/recaptcha)

reCAPTCHA is a free CAPTCHA service that protects websites from spam and abuse. 

This is a PHP library that wraps up the server-side verification step required to process responses from the reCAPTCHA service. This client supports both v2 and v3.

* reCAPTCHA: https://www.google.com/recaptcha
* This repo: https://github.com/google/recaptcha
* Hosted demo: https://recaptcha-demo.appspot.com/
* Version: 2.0.0
* License: BSD, see [LICENSE](LICENSE.md)

## Installation

Preferably, use [Composer](https://getcomposer.org/) to install this package [from Packagist](https://packagist.org/packages/google/recaptcha) into your project:

```bash
composer require google/recaptcha "^2.0"
```

If you don't have access to Composer, or require manual installation, [download the ZIP file](https://github.com/google/recaptcha/archive/master.zip) into your project and require the PSR-4 autoloader included: 

```php
require_once '/path/to/recaptcha/src/autoload.php';
```

The classes in the project are structured according to the [PSR-4 standard](http://www.php-fig.org/psr/psr-4/), so you can also use your own autoloader or require the needed files directly in your code.

## Usage

This package works out-of-the-box: after you integrate reCAPTCHA in your frontend, include your reCAPTCHA keys in this package and you're ready to start verifying the challenges from your server.

```php
<?php

use Google\ReCaptcha\ReCaptcha;

$response = ReCaptcha::make($secret)
                     ->verify($recaptchaToken, $userIp);

if ($response->valid()) {
    echo 'You are a human!';
} else {
    echo 'You are a robot!';
}
```

The `make()` static method conveniently creates a new `ReCaptcha` instance using just your secret key.

### Constraints

Sometimes it may be not enough to just verify if the reCAPTCHA challenge was completed. You can use these fluent methods to add constraints to the verification procedure and check if the challenge was faithfully completed:

* `hostname()`: Ensures the hostname from the challenge matches. Use this method if you disabled _Domain/Package Name Validation_ for your credentials.
* `apk()`: Ensures the APK Package Name from the challenge matches. Use this method if you disabled _Domain/Package Name Validation_ for your credentials.
* `window()`: Ensures the seconds elapsed between the reCAPTCHA challenge completion and your server retrieving the result from the reCAPTCHA servers is **below** the one set here. 
 
For reCAPTCHA v3, there are two methods to further constrain the verification.

* `action()`: Ensures the action from the challenge matches.
* `threshold()`: Ensures the score is **above** the specified threshold.

```php
<?php

ReCaptcha::make($secret)
         ->hostname('recaptcha-demo.appspot.com')
         ->action('homepage')
         ->threshold(0.5)
         ->window(10)
         ->verify($recaptchaToken, $userIp);
```

## Verification

By default, reCAPTCHA verification returns a immutable response from reCAPTCHA servers. You can use the `valid()` method to check if the challenge is valid or not, while the `invalid()` method will check if it has failed.

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

You can use the `verifyOrThrow()` method to conveniently throw a `ReCaptchaException` when this is invalid, allowing your application to identify the error and proceed accordingly, like redirecting the user back with a visual alert to retry.

```php
<?php

use App\Logger;
use App\Redirect;
use Google\ReCaptcha\ReCaptcha;
use Google\ReCaptcha\ReCaptchaException;

try {
    $response = ReCaptcha::make($secret)
                         ->verifyOrThrow($recaptchaToken, $ip);
} catch (ReCaptchaException $exception) {
    Logger::info("The $ip has failed the reCAPTCHA challenge");
    
    return Redirect::previous()->withErrors([
        'Please try completing the reCAPTCHA challenge again.'
    ]);
}
```

## Error handling

When a verification fails, you will have available an array of errors consisting in the parts that failed the challenge verification or constraints.

To access the array you can use `errors()`.

```php
<?php

$response =  ReCaptcha::make($secret)->verify($recaptchaToken, $userIp);

if ($response->invalid()) {
    return var_dump($response->errors());
}
```

Each error present in the array is a string declared as constant in the `ReCaptchaError` class, like `ReCaptchaError::E_CONNECTION_FAILED`.

### Constraints array

When a response is received, you will have access to the initial constraints set previously by using the `constraints()` method.

```php
<?php

$response = ReCaptcha::make($secret)
                     ->threshold(0.7)
                     ->verify($recaptchaToken, $userIp);

if ($response->invalid()) {
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
echo $response->constraint('window'); // null
```

## HTTP Clients

By default, this package uses [cURL](https://curl.haxx.se/) to make a POST request to the reCAPTCHA service. This is handled by the `Clients\CurlClient` class, and allows this package to use [HTTP/2](https://developers.google.com/web/fundamentals/performance/http2) when possible.

If cURL is not installed, or PHP doesn't have the [`curl` extension enabled](https://www.php.net/manual/en/curl.requirements.php), you may try with the stream client and socket client. The `Clients/StreamClient` uses `file_get_contents()`, but, if this is locked down to disallow its use with URLs, you can use the `Clients/SocketClient` which will use a socket connection.

To use another HTTP Client, pass the class name as a second parameter to the `make()` static method. ReCaptcha will automatically instance the class.

```php
<?php

use Google\ReCaptcha\ReCaptcha;
use Google\ReCaptcha\Clients\StreamClient;
use Google\ReCaptcha\Clients\SocketClient;

// Use the File Stream Client
$response = ReCaptcha::make($secret, StreamClient::class)
                     ->verify($recaptchaToken, $userIp);

// Use the Socket Client
$response = ReCaptcha::make($secret, SocketClient::class)
                     ->verify($recaptchaToken, $userIp);
``` 

### Custom HTTP Client

You can also use a custom HTTP Client. It must implement the `Clients/ClientInterface`, like in this example which uses [Symfony HTTP Client](https://symfony.com/doc/current/components/http_client.html#basic-usage) and a helper trait to handle some other tasks.

```php
<?php

namespace App\ReCaptcha;

use Google\ReCaptcha\Clients\ClientInterface;
use Google\ReCaptcha\Clients\ClientMethods;
use Symfony\Component\HttpClient\HttpClient;

class SymfonyAdapter implements ClientInterface
{
    use ClientMethods;
    
    /**
     * Boot this class if needed.
     *
     * @return void
     */
    protected function boot()
    {
        $this->client = HttpClient::create();
    }
    
    /**
     * Receives a request and returns a response from reCAPTCHA servers
     *
     * @param string $token
     * @param string|null $ip
     * @return array
     */
    public function send(string $token, string $ip = null) : array
    {
        $response = $this->client->request('POST', $this->url, [
            'headers' => [
                'content-type' => 'application/x-www-form-urlencoded',
                'accept' => 'application/json'
            ],
            'body' => [
                'secret' => $this->secret,
                'response' => $token,
                'remoteip' => $ip,
                'version' => ReCaptcha::VERSION,
            ],
        ]);
        
        return $response->getContent()->toArray();
    }
}
```

Once your class is ready, pass the class instance as the second argument to the `make()` static method. 

```php
<?php

use Google\ReCaptcha\ReCaptcha;
use App\ReCaptcha\SymfonyAdapter;

$response = ReCaptcha::make($secret, SymfonyAdapter::class)
                ->verify($recaptchaToken, $userIp);
``` 

## Using reCAPTCHA V2 and V3

There may be some edge cases where you need to use reCAPTCHA v2 and v3 at the same time. You can safely create a `ReCaptcha` instance using each secret key. Nothing more is needed since the secret dictates which version to use.

```php
<?php

use Google\ReCaptcha\ReCaptcha;

$version2 = ReCaptcha::make($secretV2);

$version3 = ReCaptcha::make($secretV3);
``` 

If your application implements [PSR-11 Container interface](https://www.php-fig.org/psr/psr-11/), it can be useful to save these instances separately.

## Examples

You can run the [examples](examples/) locally by just running a convenient Composer script to run a local PHP server in `http://localhost:8080`:

```bash
composer run-script serve-examples
```

Alternatively, you can check the hosted Google AppEngine Flexible environment at [`https://recaptcha-demo.appspot.com`](https://recaptcha-demo.appspot.com). This is configured by [`app.yaml`](app.yaml) which you can also use to [deploy to your own AppEngine project](https://cloud.google.com/appengine/docs/flexible/php/download).

## Contributing

No one ever has enough engineers, so we're very happy to accept contributions via Pull Requests. For details, see [CONTRIBUTING](CONTRIBUTING.md).