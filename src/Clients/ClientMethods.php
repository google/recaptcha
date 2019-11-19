<?php
/**
 * This is a PHP library that handles calling reCAPTCHA.
 *
 * BSD 3-Clause License
 *
 * @copyright (c) 2019, Google Inc.
 * @link https://www.google.com/recaptcha
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

namespace Google\ReCaptcha\Clients;

use Google\ReCaptcha\ReCaptcha;
use Google\ReCaptcha\ReCaptchaErrors;

trait ClientMethods
{
    /**
     * Shared secret for the site.
     *
     * @var string
     */
    protected $secret;

    /**
     * The Site Verify URL.
     *
     * @var string
     */
    protected $url;

    /**
     * Underlying HTTP Client instance to use.
     *
     * @var object
     */
    protected $client;

    /**
     * Client constructor.
     *
     * @param  string $secret
     * @param  string $url
     */
    public function __construct(string $secret, string $url = null)
    {
        $this->secret = $secret;
        $this->url = $url ?? ReCaptcha::SITE_VERIFY_URL;

        // Let the developer execute additional logic when the Client is instanced,
        // like adding the underlying HTTP Client instance or anything else.
        $this->boot();
    }

    /**
     * Boot this class if needed.
     *
     * @return void
     */
    protected function boot()
    {
        //
    }

    /**
     * Sets the underlying HTTP Client to use.
     *
     * @param $client
     * @return \Google\ReCaptcha\Clients\ClientInterface
     */
    public function setClient($client) : ClientInterface
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Prepares the Query String to send to reCAPTCHA servers.
     *
     * @param  string $token
     * @param  null $ip
     * @return string
     */
    protected function prepareContent(string $token, $ip = null)
    {
        return http_build_query(array_filter([
            'secret' => $this->secret,
            'response' => $token,
            'remoteip' => $ip,
            'version' => ReCaptcha::VERSION
        ]));
    }

    /**
     * Returns an array with an error as response.
     *
     * @param  string $error
     * @return array
     */
    protected function error(string $error = ReCaptchaErrors::E_UNKNOWN_ERROR)
    {
        return [
            'success' => false,
            'error-codes' => [$error]
        ];
    }
}
