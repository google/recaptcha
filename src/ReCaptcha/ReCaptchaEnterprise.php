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

use ReCaptcha\RequestMethod\EnterpriseCurlPost;
use ReCaptcha\RequestMethod\EnterprisePost;

/**
 * reCAPTCHA Enterprise client.
 */
class ReCaptchaEnterprise extends ReCaptcha
{
    /**
     * URL template for reCAPTCHA Enterprise assessments API.
     *
     * @var string
     */
    public const SITE_VERIFY_URL_TEMPLATE = 'https://recaptchaenterprise.googleapis.com/v1/projects/%s/assessments?key=%s';

    private $siteKey;
    private $expectedAction;

    /**
     * Create a configured instance to use the reCAPTCHA Enterprise service.
     *
     * @param string        $projectId     the Google Cloud Project ID
     * @param string        $apiKey        the API key for the project
     * @param string        $siteKey       the site key configured in the project
     * @param RequestMethod $requestMethod method used to send the request. Defaults to POST.
     *
     * @throws \RuntimeException if $projectId, $apiKey, or $siteKey is invalid
     */
    public function __construct($projectId, $apiKey, $siteKey, ?RequestMethod $requestMethod = null)
    {
        if (empty($projectId) || empty($apiKey) || empty($siteKey)) {
            throw new \RuntimeException('Project ID, API key, and site key must be provided');
        }

        // Initialize base class with dummy secret since we don't use it
        parent::__construct('dummy_secret', $requestMethod);

        $this->siteKey = $siteKey;

        $siteVerifyUrl = sprintf(self::SITE_VERIFY_URL_TEMPLATE, $projectId, $apiKey);

        if (!is_null($requestMethod)) {
            $this->requestMethod = $requestMethod;
        } elseif (function_exists('curl_version')) {
            $this->requestMethod = new EnterpriseCurlPost(null, $siteVerifyUrl);
        } else {
            $this->requestMethod = new EnterprisePost($siteVerifyUrl);
        }
    }

    /**
     * Calls the reCAPTCHA Enterprise assessments API to verify whether the user passes
     * CAPTCHA test and additionally runs any specified additional checks.
     *
     * @param string $response the user response token provided by reCAPTCHA, verifying the user on your site
     * @param string $remoteIp the end user's IP address (ignored in Enterprise, kept for compatibility)
     *
     * @return Response response from the service
     */
    public function verify($response, $remoteIp = null)
    {
        // Discard empty solution submissions
        if (empty($response)) {
            return new Response(false, [self::E_MISSING_INPUT_RESPONSE]);
        }

        $params = new EnterpriseRequestParameters($this->siteKey, $response, $this->expectedAction);
        $rawResponse = $this->requestMethod->submit($params);
        $initialResponse = EnterpriseResponseParser::fromJson($rawResponse);

        return $this->verifyResponse($initialResponse);
    }

    /**
     * Provide an action to match against in verify()
     * This should be set per page.
     *
     * @param string $action Expected action
     *
     * @return ReCaptchaEnterprise Current instance for fluent interface
     */
    public function setExpectedAction($action)
    {
        $this->expectedAction = $action;
        parent::setExpectedAction($action);

        return $this;
    }
}
