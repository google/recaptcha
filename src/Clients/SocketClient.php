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

use Google\ReCaptcha\ReCaptchaErrors;

class SocketClient implements ClientInterface
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
        $this->client = new SocketHandler;
    }

    /**
     * Receives a request and returns a response from reCAPTCHA servers.
     *
     * @param  string $token The token that identifies the reCAPTCHA challenge.
     * @param  string|null $ip The optional IP of the user challenged.
     * @return array The response from reCAPTCHA servers as an array, which is later parsed.
     */
    public function send(string $token, string $ip = null) : array
    {
        $url = parse_url($this->url);

        if (! $this->openConnection($url['host'])) {
            return $this->error(ReCaptchaErrors::E_CONNECTION_FAILED);
        }

        $response = $this->submit($this->makeRequest($url, $token, $ip));

        if (! strpos($response, '200 OK')) {
            return $this->error(ReCaptchaErrors::E_BAD_RESPONSE);
        }

        return json_decode(preg_split("#\n\s*\n#Uis", $response, 2)[1], true);
    }

    /**
     * Opens a socket connection
     *
     * @param  string $host
     * @return mixed
     */
    protected function openConnection(string $host)
    {
        $errno = 0;
        $errstr = '';
        return $this->client->fsockopen('ssl://' . $host, 443, $errno, $errstr, 30);
    }

    /**
     * Creates a digestible request for the socket connection
     *
     * @param  array $url
     * @param  string $token
     * @param  string|null $ip
     * @return string
     */
    protected function makeRequest(array $url, string $token, string $ip = null)
    {
        $content = $this->prepareContent($token, $ip);

        return implode("\r\n", [
            'POST ' . $url['path'] . ' HTTP/1.1',
            'Host: ' . $url['host'],
            'Content-Type: application/x-www-form-urlencoded',
            'Content-length: ' . strlen($content),
            "Connection: close\r\n",
            "$content\r\n",
        ]);
    }

    /**
     * Submits the information to the server through the socket
     *
     * @param $request
     * @return string
     */
    protected function submit($request)
    {
        try {
            $this->client->fwrite($request);

            $response = '';

            while (! $this->client->feof()) {
                $response .= $this->client->fgets(4096);
            }
        } finally {
            $this->client->fclose();
        }

        return $response;
    }
}
