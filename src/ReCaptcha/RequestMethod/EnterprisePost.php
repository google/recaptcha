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

namespace ReCaptcha\RequestMethod;

use ReCaptcha\EnterpriseRequestParameters;
use ReCaptcha\ReCaptcha;
use ReCaptcha\RequestMethod;
use ReCaptcha\RequestParameters;

/**
 * Sends POST request to the reCAPTCHA Enterprise service.
 */
class EnterprisePost implements RequestMethod
{
    /**
     * URL for reCAPTCHA assessments API.
     *
     * @var string
     */
    private $siteVerifyUrl;

    /**
     * Only needed if you want to override the defaults.
     *
     * @param string $siteVerifyUrl URL for reCAPTCHA assessments API
     */
    public function __construct($siteVerifyUrl = null)
    {
        $this->siteVerifyUrl = $siteVerifyUrl;
    }

    /**
     * Submit the POST request with the specified parameters.
     *
     * @param RequestParameters $params Request parameters
     *
     * @return string Body of the reCAPTCHA response
     *
     * @throws \InvalidArgumentException if parameters are not EnterpriseRequestParameters
     */
    public function submit(RequestParameters $params)
    {
        if (!$params instanceof EnterpriseRequestParameters) {
            throw new \InvalidArgumentException('Enterprise request methods require EnterpriseRequestParameters');
        }

        $options = [
            'http' => [
                'header' => "Content-type: application/json\r\n",
                'method' => 'POST',
                'content' => $params->toJson(),
                'verify_peer' => true,
            ],
        ];
        $context = stream_context_create($options);
        $request = file_get_contents($this->siteVerifyUrl, false, $context);

        if (false !== $request) {
            return $request;
        }

        return '{"tokenProperties": {"valid": false, "invalidReason": "'.ReCaptcha::E_CONNECTION_FAILED.'"}}';
    }
}
