<?php

/**
 * This is a PHP library that handles calling reCAPTCHA.
 *
 * BSD 3-Clause License
 *
 * @copyright (c) 2019, Google Inc.
 *
 * @see https://www.google.com/recaptcha
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 * 1. Redistributions of source code must retain the above copyright notice, this
 *    list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 *    this list of conditions and the following disclaimer in the documentation
 *    and/or other materials provided with the distribution.
 *
 * 3. Neither the name of the copyright holder nor the names of its
 *    contributors may be used to endorse or promote products derived from
 *    this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
 * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

namespace ReCaptcha;

/**
 * reCAPTCHA client.
 */
class ReCaptcha
{
    /**
     * Version of this client library.
     *
     * @var string
     */
    public const VERSION = 'php_1.4.2';

    /**
     * URL for reCAPTCHA siteverify API.
     *
     * @var string
     */
    public const SITE_VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    /**
     * User response token parameter name.
     *
     * @var string
     */
    public const RESPONSE_KEY = 'g-recaptcha-response';

    /**
     * Invalid JSON received.
     *
     * @var string
     */
    public const E_INVALID_JSON = 'invalid-json';

    /**
     * Could not connect to service.
     *
     * @var string
     */
    public const E_CONNECTION_FAILED = 'connection-failed';

    /**
     * Did not receive a 200 from the service.
     *
     * @var string
     */
    public const E_BAD_RESPONSE = 'bad-response';

    /**
     * Not a success, but no error codes received!
     *
     * @var string
     */
    public const E_UNKNOWN_ERROR = 'unknown-error';

    /**
     * ReCAPTCHA response not provided.
     *
     * @var string
     */
    public const E_MISSING_INPUT_RESPONSE = 'missing-input-response';

    /**
     * Expected hostname did not match.
     *
     * @var string
     */
    public const E_HOSTNAME_MISMATCH = 'hostname-mismatch';

    /**
     * Expected APK package name did not match.
     *
     * @var string
     */
    public const E_APK_PACKAGE_NAME_MISMATCH = 'apk_package_name-mismatch';

    /**
     * Expected action did not match.
     *
     * @var string
     */
    public const E_ACTION_MISMATCH = 'action-mismatch';

    /**
     * Score threshold not met.
     *
     * @var string
     */
    public const E_SCORE_THRESHOLD_NOT_MET = 'score-threshold-not-met';

    /**
     * Challenge timeout.
     *
     * @var string
     */
    public const E_CHALLENGE_TIMEOUT = 'challenge-timeout';

    /**
     * Shared secret for the site.
     *
     * @var string
     */
    private $secret;

    /**
     * Method used to communicate with service. Defaults to POST request.
     *
     * @var RequestMethod
     */
    private $requestMethod;

    private $hostname;
    private $apkPackageName;
    private $action;
    private $threshold;
    private $timeoutSeconds;

    /**
     * Create a configured instance to use the reCAPTCHA service.
     *
     * @param string        $secret        the shared key between your site and reCAPTCHA
     * @param RequestMethod $requestMethod method used to send the request. Defaults to POST.
     *
     * @throws \RuntimeException if $secret is invalid
     */
    public function __construct($secret, ?RequestMethod $requestMethod = null)
    {
        if (empty($secret)) {
            throw new \RuntimeException('No secret provided');
        }

        if (!is_string($secret)) {
            throw new \RuntimeException('The provided secret must be a string');
        }

        $this->secret = $secret;

        if (!is_null($requestMethod)) {
            $this->requestMethod = $requestMethod;
        } elseif (function_exists('curl_version')) {
            $this->requestMethod = new RequestMethod\CurlPost();
        } else {
            $this->requestMethod = new RequestMethod\Post();
        }
    }

    /**
     * Calls the reCAPTCHA siteverify API to verify whether the user passes
     * CAPTCHA test and additionally runs any specified additional checks.
     *
     * @param string $response the user response token provided by reCAPTCHA, verifying the user on your site
     * @param string $remoteIp the end user's IP address
     *
     * @return Response response from the service
     */
    public function verify($response, $remoteIp = null)
    {
        // Discard empty solution submissions
        if (empty($response)) {
            return new Response(false, [self::E_MISSING_INPUT_RESPONSE]);
        }

        $params = new RequestParameters($this->secret, $response, $remoteIp, self::VERSION);
        $rawResponse = $this->requestMethod->submit($params);
        $initialResponse = Response::fromJson($rawResponse);
        $validationErrors = [];

        if (isset($this->hostname) && 0 !== strcasecmp($this->hostname, $initialResponse->getHostname())) {
            $validationErrors[] = self::E_HOSTNAME_MISMATCH;
        }

        if (isset($this->apkPackageName) && 0 !== strcasecmp($this->apkPackageName, $initialResponse->getApkPackageName())) {
            $validationErrors[] = self::E_APK_PACKAGE_NAME_MISMATCH;
        }

        if (isset($this->action) && 0 !== strcasecmp($this->action, $initialResponse->getAction())) {
            $validationErrors[] = self::E_ACTION_MISMATCH;
        }

        if (isset($this->threshold) && $this->threshold > $initialResponse->getScore()) {
            $validationErrors[] = self::E_SCORE_THRESHOLD_NOT_MET;
        }

        if (isset($this->timeoutSeconds)) {
            $challengeTs = strtotime($initialResponse->getChallengeTs());

            if ($challengeTs > 0 && time() - $challengeTs > $this->timeoutSeconds) {
                $validationErrors[] = self::E_CHALLENGE_TIMEOUT;
            }
        }

        if (empty($validationErrors)) {
            return $initialResponse;
        }

        return new Response(
            false,
            array_merge($initialResponse->getErrorCodes(), $validationErrors),
            $initialResponse->getHostname(),
            $initialResponse->getChallengeTs(),
            $initialResponse->getApkPackageName(),
            $initialResponse->getScore(),
            $initialResponse->getAction()
        );
    }

    /**
     * Provide a hostname to match against in verify()
     * This should be without a protocol or trailing slash, e.g. www.google.com.
     *
     * @param string $hostname Expected hostname
     *
     * @return ReCaptcha Current instance for fluent interface
     */
    public function setExpectedHostname($hostname)
    {
        $this->hostname = $hostname;

        return $this;
    }

    /**
     * Provide an APK package name to match against in verify().
     *
     * @param string $apkPackageName Expected APK package name
     *
     * @return ReCaptcha Current instance for fluent interface
     */
    public function setExpectedApkPackageName($apkPackageName)
    {
        $this->apkPackageName = $apkPackageName;

        return $this;
    }

    /**
     * Provide an action to match against in verify()
     * This should be set per page.
     *
     * @param string $action Expected action
     *
     * @return ReCaptcha Current instance for fluent interface
     */
    public function setExpectedAction($action)
    {
        $this->action = $action;

        return $this;
    }

    /**
     * Provide a threshold to meet or exceed in verify()
     * Threshold should be a float between 0 and 1 which will be tested as response >= threshold.
     *
     * @param float $threshold Expected threshold
     *
     * @return ReCaptcha Current instance for fluent interface
     */
    public function setScoreThreshold($threshold)
    {
        $this->threshold = floatval($threshold);

        return $this;
    }

    /**
     * Provide a timeout in seconds to test against the challenge timestamp in verify().
     *
     * @param int $timeoutSeconds Maximum time (seconds) elapsed since the challenge timestamp
     *
     * @return ReCaptcha Current instance for fluent interface
     */
    public function setChallengeTimeout($timeoutSeconds)
    {
        $this->timeoutSeconds = $timeoutSeconds;

        return $this;
    }
}
