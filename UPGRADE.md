# Upgrading

Since version 1.2.3 a lot of changes has been made to keep the library modular on the edge of the possibilities of latest PHP versions.

## Version 2.0

### Removed

#### Curl and Socket HTTP Clients

All HTTP Clients have been removed and only the `StreamClient` is available. Developers can use their own HTTP Client like cURL or Socket, or full libraries like Guzzle or Symfony HTTP Client, or even HTTP Plug.

### Changed 

#### HTTP Client interface

Any HTTP Client now must follow the `Http/Client` interface. It receives the site token, URL to verify, and a way to set the underlying HTTP Client if necessary.

### Response

The Response from reCAPTCHA servers is now immutable.

#### Response methods

Methods to check the Response status have changed for.

| Original | New |
|---|---|
| isSuccess() | valid()  |
| ! isSuccess() | invalid() |
| getErrorCodes() | errors() |

#### Verification Fluent Methods

Constraint methods for after the challenge has been made have changed to shorter versions and stricter parameters.

| Original | New |
|---|---|
| setExpectedHostname() | hostname()  |
| setExpectedApkPackageName() | apk()  |
| setExpectedAction() | action()  |
| setScoreThreshold() | threshold()  |
| setChallengeTimeout() | timeout()  |

### Added

#### Instantiation

No longer is needed to instantiate manually the `ReCaptcha` class. The `make()` static method is preferred, while leaving manual instantiation for fine control on the HTTP Client to use.

#### Constraints

The developer can add constraints to further check the reCAPTCHA response after it has been received. These constraints array is injected into the Response, and later is checked against.

The constraints array can be retrieved using `$response->constraints()`.
