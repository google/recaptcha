# Upgrading

## 1.x.x to 2.x.x

The library has received a full reworking. You will have to change instancing procedures and all methods, hence the change in the major version. 

### Removed 

#### `RequestParameters`

The class is not needed now since the construction of the request happens dynamically once the verification is fired.

#### Response properties

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

`ReCaptcha` class now requires the `$secret` token string and the HTTP Client to use against reCAPTCHA servers:

```php
<?php

use Google\ReCaptcha\ReCaptcha;
use App\HttpClient;

$recaptcha = new ReCaptcha($secret, new HttpClient);
``` 

#### Namespace

Prior v1 versions used the `\ReCaptcha` namespace. To better reflect the code hierarchy and avoid conflicts, the v2 uses the `\Google\ReCaptcha` namespace. 

#### `RequestMethod` to `ClientInterface`

Any HTTP Client now must follow the `Http/ClientInterface` interface. It receives the site secret token, URL to verify, and a way to set the underlying HTTP Client if necessary after instantiation.

#### Response status methods

Methods to check the Response status have changed for.

| Original | New |
|---|---|
| `isSuccess()` | `success()`  |
| `! isSuccess()` | `failed()` |
| `getErrorCodes()` | `errorCodes()` \| `errors()` |
| `getErrorCodes()[$code]` | `hasErrors($code)` |

#### Verification Fluent Methods

Constraint methods for after the challenge has been made have changed to shorter versions and stricter parameters.

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

The developer can add constraints to further check the reCAPTCHA response after it has been received. These constraints array is injected into the Response, and later is checked against.

The constraints array can be retrieved using `$response->constraints()`.
