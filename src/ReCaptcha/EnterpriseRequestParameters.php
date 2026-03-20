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
 * Stores and formats the parameters for the request to the reCAPTCHA Enterprise service.
 */
class EnterpriseRequestParameters extends RequestParameters
{
    /**
     * @var string
     */
    private $siteKey;

    /**
     * @var string
     */
    private $expectedAction;

    /**
     * Initialise parameters.
     *
     * @param string $siteKey        site key
     * @param string $response       value from g-captcha-response form field
     * @param string $expectedAction expected action
     */
    public function __construct($siteKey, $response, $expectedAction = null)
    {
        // Secret and remoteIp are not used in this format
        parent::__construct('', $response);
        $this->siteKey = $siteKey;
        $this->expectedAction = $expectedAction;
    }

    /**
     * JSON representation for HTTP request.
     *
     * @return string JSON formatted parameters
     */
    public function toJson()
    {
        $data = [
            'event' => [
                'token' => $this->toArray()['response'],
                'siteKey' => $this->siteKey,
            ],
        ];

        if (!is_null($this->expectedAction)) {
            $data['event']['expectedAction'] = $this->expectedAction;
        }

        return json_encode($data);
    }
}
