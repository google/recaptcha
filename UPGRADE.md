# Upgrading

## 1.x.x to 2.x.x

The library has received a full rework. You will have to change instancing procedures and all methods, hence the change in the major version. 

This is in exchange of flexibility. This package can work well out-of-the-box, but it also allows the developer to handle the reCAPTCHA request and response as he sees fit in an PSR-standard manner.

### Removed 

#### Some Errors constants

The following constants for errors has been removed:

* `E_CONNECTION_FAILED` 
* `E_BAD_RESPONSE` 
* `E_MISSING_INPUT_RESPONSE`

The first two are superseded by PSR-18 exceptions, while the third is not used since the input response is required at all times.   

#### Autoload

The `autoload.php` has been removed in favor of using Composer autoloader. 

#### cURL, Stream and Socket HTTP Clients

cURL, Stream and Socket HTTP Clients have been removed in favour of PSR-18 compliant clients and PSR-17 message factories. The developer is free to use any PSR-18 client to contact the reCAPTCHA servers.

The package suggests Symfony HTTP Client, which uses cURL (thanks to its wide adoption) and fallbacks to native PHP stream.

#### `RequestParameters`

The class is not needed now since the construction of the request happens dynamically once the verification is fired.

#### Response methods

The Response from reCAPTCHA servers has become simpler to use thanks to dynamic `__get` calls, so there is no more need to call the methods to check the constraints:

| Original | New |
|---|---|
| `getHostname()` | `$response->hostname`  |
| `getChallengeTs()` | `$response->challenge_ts` |
| `getApkPackageName()` | `$response->apk_package_name` |
| `getScore()` | `$response->score` |
| `getAction()` | `$response->action` |

### Changed 

#### `ReCaptcha` instantiation

The main `ReCaptcha` class can be instanced quickly, which will use `nyholm/psr7` factories and Symfony HTTP Client by default, as long these are installed.

```php
<?php

use Google\ReCaptcha\ReCaptcha;

$recaptcha = ReCaptcha::make($secret);
``` 

Alternatively, the manual instancing requires a PSR-18 HTTP Client and PSR-17 Request and Stream Factories the developer can choose.

#### Namespace

Prior v1 versions used the `\ReCaptcha` namespace. To better reflect the code hierarchy and avoid conflicts, the v2 uses the `\Google\ReCaptcha` namespace. 

#### Response status methods

Methods to check the Response status have changed for the returned `ReCaptchaResponse`:

| Original | New |
|---|---|
| `isSuccess()` | `valid()`  |
| `! isSuccess()` | `invalid()` |
| `getErrorCodes()` | `$response->error_codes` |
| `getErrorCodes()[$code]` | `hasErrors($code)` |

To check **only** if the challenge was completed successfully, aside from any constraint error, you will need to retrieve the property directly using `$response->success`.

#### Constraint Errors

While the response will have the reCAPTCHA errors contained in an array in the `$response->error_codes` property, constraint errors (like hostname and APK Package Name post-verifications) are stored separately and retrieved using `$response->errors()`.

#### Verification Fluent Methods

Constraint methods for after the challenge has been made have changed to shorter versions and stricter parameters to better reflect the original JSON response from reCAPTCHA servers:

| Original | New |
|---|---|
| `setExpectedHostname()` | `hostname()` |
| `setExpectedApkPackageName()` | `apkPackageName()` |
| `setExpectedAction()` | `action()` |
| `setScoreThreshold()` | `threshold()` |
| `setChallengeTimeout()` | `challengeTs()` |

### Added

#### Easy instantiation

No longer is needed to instantiate manually the `ReCaptcha` class. The `make()` static method is preferred, while leaving manual instantiation for fine control on the HTTP Client to use or Service Containers.

#### Constraints

The developer can add constraints to further check the reCAPTCHA response after it has been received. These constraints array is injected into the Response, and later are checked against once received.

```php

use Google\ReCaptcha\ReCaptcha;

$response = ReCaptcha::make($secret)
    ->action('login')
    ->challengeTs(10)
    ->threshold(0.7)
    ->verify($token, $ip);
```

The errors are appended to the response array, so any error given by a constraint will be available at `$response->error_codes`.

The constraints can be retrieved from the `ReCaptchaResponse` using `$response->constraints()`.

