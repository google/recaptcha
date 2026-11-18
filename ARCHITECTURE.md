# Architecture

The general pattern of usage is to instantiate the `ReCaptcha` class with your
secret key, specify any additional validation rules, and then call `verify()`
with the reCAPTCHA response and user's IP address. For example:

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

The `ReCaptcha` class automatically chooses a method to communicate with the
reCAPTCHA service based on your server's capabilities. See the
[Alternate request methods](README.md#alternate-request-methods) section in the
README for more details.

## DTOs & Immutable Builders

- [`RequestParameters`](./src/ReCaptcha/RequestParameters.php) and
  [`Response`](./src/ReCaptcha/Response.php) are immutable `readonly` value
  objects. `Response` implements `\JsonSerializable` and provides
  `hasErrorCode()` for ergonomic error checks without exceptions on verification
  or network errors.
- [`ReCaptcha`](./src/ReCaptcha/ReCaptcha.php) supports both legacy mutable
  `set*()` methods and immutable `with*()` builders that clone the client
  instance so a shared base verifier can safely derive route-specific rules.
- When additional validation checks fail locally, `ReCaptcha::verify()` merges
  the local error codes (`E_HOSTNAME_MISMATCH`, `E_APK_PACKAGE_NAME_MISMATCH`,
  `E_ACTION_MISMATCH`, `E_SCORE_THRESHOLD_NOT_MET`, `E_CHALLENGE_TIMEOUT`) after
  any upstream error codes returned by the `siteverify` endpoint.

## Adding new request methods

Create a class that implements the
[`RequestMethod`](./src/ReCaptcha/RequestMethod.php) interface. The convention
is to name this class `RequestMethod\`_MethodType_`Post`. Take a look at
[`RequestMethod\CurlPost`](./src/ReCaptcha/RequestMethod/CurlPost.php)
with the matching
[`RequestMethod/CurlPostTest`](./tests/ReCaptcha/RequestMethod/CurlPostTest.php)
to see this pattern in action.

### PSR-18 HTTP Client Recipe

To keep runtime dependencies at zero, no PSR-18 package is required by this
library. Applications already using a PSR-18 `ClientInterface` (such as Symfony
HttpClient or Guzzle) can wrap it in a small `RequestMethod` implementation:

```php
<?php

declare(strict_types=1);

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use ReCaptcha\ReCaptcha;
use ReCaptcha\RequestMethod;
use ReCaptcha\RequestParameters;

final readonly class Psr18Post implements RequestMethod
{
    public function __construct(
        private ClientInterface $client,
        private RequestFactoryInterface $requestFactory,
        private StreamFactoryInterface $streamFactory,
        private string $siteVerifyUrl = ReCaptcha::SITE_VERIFY_URL,
    ) {}

    public function submit(RequestParameters $params): string
    {
        $request = $this->requestFactory
            ->createRequest('POST', $this->siteVerifyUrl)
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withBody($this->streamFactory->createStream($params->toQueryString()));

        try {
            $response = $this->client->sendRequest($request);
        } catch (ClientExceptionInterface) {
            return '{"success": false, "error-codes": ["'.ReCaptcha::E_CONNECTION_FAILED.'"]}';
        }

        if (200 !== $response->getStatusCode()) {
            return '{"success": false, "error-codes": ["'.ReCaptcha::E_BAD_RESPONSE.'"]}';
        }

        return (string) $response->getBody();
    }
}
```

### Error conventions

The client returns the response as provided by the reCAPTCHA services augmented
with additional error codes based on the client's checks. When adding a new
[`RequestMethod`](./src/ReCaptcha/RequestMethod.php) ensure that it returns the
`ReCaptcha::E_CONNECTION_FAILED` and `ReCaptcha::E_BAD_RESPONSE` where
appropriate.
