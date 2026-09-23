# AGENTS.md — Contributor & Maintainer Guide for AI Agents

This file provides authoritative context, architectural invariants, branching policies, and verification requirements for AI coding agents and maintainers working on [`google/recaptcha`](https://github.com/google/recaptcha).

---

## 1. Project Identity & Core Constraints

- **Purpose**: Official PHP client library for verifying Google reCAPTCHA (`v2` Checkbox, `v2` Invisible, and `v3`) tokens against the `siteverify` endpoint (`https://www.google.com/recaptcha/api/siteverify`).
- **Scope Boundary**: This library targets the standard reCAPTCHA `siteverify` API. Projects using Google Cloud reCAPTCHA Enterprise should use [`google/cloud-recaptcha-enterprise`](https://github.com/googleapis/google-cloud-php-recaptcha-enterprise).
- **Zero Runtime Dependencies**:
  - `composer.json` `require` MUST only specify `"php": ">=8.4"` (and optional PHP extensions in `suggest` if ever needed). Never add third-party runtime packages to `require`.
  - The library MUST work both via Composer's PSR-4 autoloader (`vendor/autoload.php`) and via the standalone autoloader (`src/autoload.php`) for non-Composer installations.
- **Distribution Cleanliness**:
  - All development, CI, test, documentation, and agent configuration files MUST be listed with `export-ignore` in [`.gitattributes`](.gitattributes) so Composer `--prefer-dist` archives contain only runtime library files (`src/`, `composer.json`, `LICENSE`, `README.md`).

---

## 2. Branching Model & Semantic Versioning (`SemVer`)

### Active Branch
| Branch | Series | PHP Constraint | Type System & Mutability Contract |
| :--- | :--- | :--- | :--- |
| **`main`** | **`2.x`** (`2.1.x-dev`) | `>=8.4` | `declare(strict_types=1)` in all files, strict scalar/return type hints, `readonly` properties on `RequestParameters` and `Response`. |

### Backward Compatibility Enforcement (`roave/backward-compatibility-check`)
- Every push and pull request on `main` runs `roave/backward-compatibility-check` against the latest tagged release in `.github/workflows/php.yml`.
- **Allowed changes in minor/patch releases (`2.1.x`, `2.x.0`)**:
  - Updating the value of `ReCaptcha\ReCaptcha::VERSION` (explicitly allowlisted in [`.roave-backward-compatibility-check.xml`](.roave-backward-compatibility-check.xml)).
  - Internal implementation improvements that do not alter class hierarchies, public/protected method signatures, parameter types, return types, property types/visibility, or constant values.
  - Adding new optional parameters with default values or new public methods/constants (minor release `2.x.0`).
- **Breaking changes (require `3.0.0`)**:
  - Narrowing parameter types, widening return types, changing `readonly` modifiers, removing or renaming public/protected methods/properties/constants, or adding required methods to `ReCaptcha\RequestMethod`.

### Release Checklist
When preparing a new release (`X.Y.Z`):
1. Update `ReCaptcha::VERSION` (`public const VERSION = 'php_X.Y.Z';`) in [`src/ReCaptcha/ReCaptcha.php`](src/ReCaptcha/ReCaptcha.php).
2. If starting a new minor/major series, update `extra.branch-alias.dev-main` in [`composer.json`](composer.json).
3. Run all quality gates (`composer validate --strict`, `composer audit`, `composer run lint`, `composer run phpstan`, `composer run test`).
4. Tag `X.Y.Z` on the target branch and publish structured GitHub Release notes (`Overview`, `Breaking Changes` if major, `Bug Fixes` / `What's Changed`, `Compatibility & Upgrade Guide`, and `Full Changelog` compare link).

---

## 3. Architecture & Security Invariants

```
src/
├── autoload.php                       # Standalone PSR-4-equivalent autoloader
└── ReCaptcha/
    ├── ReCaptcha.php                  # Main client & fluent verification builder
    ├── RequestMethod.php              # Transport interface: submit(RequestParameters): string
    ├── RequestParameters.php          # Immutable (readonly) request payload value object
    ├── Response.php                   # Immutable (readonly) parsed siteverify response DTO
    └── RequestMethod/
        ├── CurlPost.php               # Default transport when ext-curl is available
        ├── Post.php                   # Fallback transport using file_get_contents() + stream context
        └── SocketPost.php             # Fallback transport using fsockopen() TLS sockets
```

### Key Invariants
1. **No Exceptions on Network or Validation Failures**:
   - `ReCaptcha::__construct()` throws `\RuntimeException` if `$secret` is empty (`''`), and PHP throws `\TypeError` if invalid types are passed under `strict_types=1`.
   - `ReCaptcha::verify()` and `RequestMethod::submit()` **never throw** on network errors, malformed JSON, or failed verification checks. Instead, transports return synthetic JSON error payloads (`E_CONNECTION_FAILED`, `E_BAD_RESPONSE`), `Response::fromJson()` returns `E_INVALID_JSON` or `E_UNKNOWN_ERROR`, and `ReCaptcha::verify()` returns a `Response` with `isSuccess() === false` and populated `getErrorCodes()`.
2. **Transport Security & Resource Management**:
   - **TLS Verification**: Always keep peer verification enabled (`CURLOPT_SSL_VERIFYPEER => true` in `CurlPost`; `verify_peer => true` and `verify_peer_name => true` in `Post`; `ssl://` in `SocketPost`).
   - **Timeouts**: All 3 transports enforce a 60-second timeout (`CURLOPT_TIMEOUT => 60`, `CURLOPT_CONNECTTIMEOUT => 60`, stream `timeout => 60`, `fsockopen` + `stream_set_timeout` 60s).
   - **Resource Cleanup**:
     - Do **not** call `curl_close()` in `CurlPost` (`curl_close()` is a no-op since PHP 8.0 and deprecated in PHP 8.5; `CurlHandle` closes automatically when out of scope).
     - Always call `fclose($handle)` on every exit path in `SocketPost` once `fsockopen()` returns a valid handle (including when `stream_set_timeout()` fails).

---

## 4. Testing Architecture & Conventions

- **100% Code Coverage**:
  - The test suite in `tests/ReCaptcha/` maintains **100% line, method, and class coverage** across `src/ReCaptcha/`. Any new code or branch in `src/` MUST have corresponding unit tests maintaining 100% coverage.
- **Strict PHPUnit 13 Metadata**:
  - [`phpunit.xml.dist`](phpunit.xml.dist) enables `beStrictAboutCoverageMetadata="true"`, `beStrictAboutOutputDuringTests="true"`, and `failOn*` (`Warning`, `Risky`, `Deprecation`, `Notice`, `EmptyTestSuite`).
  - Every test class MUST declare explicit `#[CoversClass(...)]` and `#[UsesClass(...)]` PHPUnit attributes (never `@coversNothing`).
- **Namespace-Scoped Function Mocking**:
  - Because `CurlPost`, `Post`, `SocketPost`, and `ReCaptcha` call unqualified PHP built-in functions (`function_exists`, `curl_init`, `curl_setopt_array`, `curl_exec`, `file_get_contents`, `fsockopen`, `fwrite`, `stream_get_contents`, `stream_set_timeout`, `fclose`) from within the `ReCaptcha` or `ReCaptcha\RequestMethod` namespaces, tests intercept these calls by defining namespace-scoped functions backed by static state classes (`GlobalState`, `CurlPostGlobalState`, `PostTest::$assert`, `SocketPostGlobalState`).
  - Always reset static mock state in `setUp()` or `tearDown()`.
  - In `src/`, do **not** prefix those mocked built-in functions with a leading backslash (`\curl_exec`, `\file_get_contents`, etc.), as doing so bypasses namespace lookup and breaks the unit test mocks.

---

## 5. Mandatory Quality Gates

Before creating a commit or opening a pull request, run all five checks locally and ensure zero errors or warnings:

```bash
# 1. Validate composer.json and composer.lock
composer validate --strict

# 2. Check for known security vulnerabilities in dependencies
composer audit

# 3. Check code formatting and strict_types enforcement (PHP-CS-Fixer)
composer run lint
# (Use `composer run lint-fix` to automatically format files)

# 4. Run static analysis at max level + strict rules + PHPUnit rules (PHPStan)
composer run phpstan

# 5. Run unit tests with Xdebug coverage and strict PHPUnit 13 checks
composer run test
```

To manually test the interactive example pages locally:
```bash
composer run serve-examples
# Starts PHP built-in server at http://localhost:8080 serving examples/
```
