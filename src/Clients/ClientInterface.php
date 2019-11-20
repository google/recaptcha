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

interface ClientInterface
{
    /**
     * Client constructor.
     *
     * @param  string $secret The shared secret for the reCAPTCHA service.
     * @param  string $url The endpoint verify the reCAPTCHA challenge.
     * @return void
     */
    public function __construct(string $secret,
                                string $url = ReCaptcha::SITE_VERIFY_URL);

    /**
     * Receives a request and returns a response from reCAPTCHA servers.
     *
     * @param  string $token The token that identifies the reCAPTCHA challenge.
     * @param  string|null $ip The optional IP of the user challenged.
     * @return array The response from reCAPTCHA servers as an array, which is later parsed.
     */
    public function send(string $token, string $ip = null) : array;

    /**
     * Sets the HTTP Client to use with this.
     *
     * @param mixed $client The object that this client will use for
     * @return \Google\ReCaptcha\Clients\ClientInterface Ensure you return this client instance
     */
    public function setClient($client) : ClientInterface;
}
