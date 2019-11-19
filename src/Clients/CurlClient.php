<?php
/**
 * This is a PHP library that handles calling reCAPTCHA.
 *
 * BSD 3-Clause License
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

use RuntimeException;
use Google\ReCaptcha\ReCaptchaErrors;

class CurlClient implements ClientInterface
{
    use ClientMethods;

    /**
     * Boot the Client if needed.
     *
     * @return void
     * @throws \RuntimeException
     */
    protected function boot()
    {
        // We will throw a descriptive exception if the curl extension is not loaded
        if (!extension_loaded('curl')) {
            throw new RuntimeException('The [curl] extension is not loaded.');
        }

        $this->client = new CurlHandler;
    }

    /**
     * Receives a request and returns a response from reCAPTCHA servers.
     *
     * @param  string $token
     * @param  string|null $ip
     * @return array
     */
    public function send(string $token, string $ip = null) : array
    {
        $this->client->setoptArray(
            $resource = $this->client->init($this->url),
            [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $this->prepareContent($token, $ip),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/x-www-form-urlencoded'
                ],
                CURLINFO_HEADER_OUT => false,
                CURLOPT_HEADER => false,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_HTTP_VERSION => defined(CURL_HTTP_VERSION_2TLS)
                    ? CURL_HTTP_VERSION_2TLS
                    : CURL_HTTP_VERSION_1_1
            ]
        );

        // Safely close curl if something happens when executing the request.
        try {
            $response = $this->client->exec($resource);
        } finally {
            $this->client->close($resource);
        }

        if ($response !== false) {
            return json_decode($response, true) ?? [];
        }

        return $this->error(ReCaptchaErrors::E_CONNECTION_FAILED);
    }
}
