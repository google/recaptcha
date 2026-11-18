<?php

declare(strict_types=1);

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

use ReCaptcha\ReCaptcha;
use ReCaptcha\RequestMethod;
use ReCaptcha\RequestParameters;

/**
 * Sends a POST request to the reCAPTCHA service, but makes use of fsockopen()
 * instead of file_get_contents(). This is to account for people who may be on
 * servers where allow_url_fopen is disabled.
 */
class SocketPost implements RequestMethod
{
    private string $siteVerifyUrl;

    /**
     * Timeout (in seconds) for the socket connection and stream read.
     */
    private int $timeout;

    /**
     * Only needed if you want to override the defaults.
     *
     * @param null|string $siteVerifyUrl URL for reCAPTCHA siteverify API
     * @param int         $timeout       timeout in seconds for the request (defaults to 60)
     */
    public function __construct(?string $siteVerifyUrl = null, int $timeout = 60)
    {
        $this->siteVerifyUrl = $siteVerifyUrl ?? ReCaptcha::SITE_VERIFY_URL;
        $this->timeout = $timeout;
    }

    /**
     * Submit the POST request with the specified parameters.
     *
     * @param RequestParameters $params Request parameters
     *
     * @return string Body of the reCAPTCHA response
     */
    public function submit(RequestParameters $params): string
    {
        if ($this->timeout < 0 || 1 === preg_match('/[\r\n\0]/', $this->siteVerifyUrl)) {
            return '{"success": false, "error-codes": ["'.ReCaptcha::E_CONNECTION_FAILED.'"]}';
        }

        $errno = 0;
        $errstr = '';
        $urlParsed = parse_url($this->siteVerifyUrl);

        if (false === $urlParsed || !isset($urlParsed['scheme'], $urlParsed['host'], $urlParsed['path']) || 'https' !== strtolower($urlParsed['scheme'])) {
            return '{"success": false, "error-codes": ["'.ReCaptcha::E_CONNECTION_FAILED.'"]}';
        }

        $host = $urlParsed['host'];
        $port = $urlParsed['port'] ?? 443;
        $requestTarget = $urlParsed['path'].(isset($urlParsed['query']) ? '?'.$urlParsed['query'] : '');
        $hostHeader = 443 === $port ? $host : $host.':'.$port;

        $handle = fsockopen('ssl://'.$host, $port, $errno, $errstr, $this->timeout);

        if (false === $handle) {
            return '{"success": false, "error-codes": ["'.ReCaptcha::E_CONNECTION_FAILED.'"]}';
        }

        if (0 !== $errno || '' !== $errstr || false === stream_set_timeout($handle, $this->timeout)) {
            fclose($handle);

            return '{"success": false, "error-codes": ["'.ReCaptcha::E_CONNECTION_FAILED.'"]}';
        }

        $content = $params->toQueryString();

        $request = 'POST '.$requestTarget." HTTP/1.0\r\n";
        $request .= 'Host: '.$hostHeader."\r\n";
        $request .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $request .= 'Content-Length: '.strlen($content)."\r\n";
        $request .= "Connection: close\r\n\r\n";
        $request .= $content;

        $totalWritten = 0;
        $requestLength = strlen($request);
        while ($totalWritten < $requestLength) {
            $written = fwrite($handle, substr($request, $totalWritten));
            if (false === $written || 0 === $written) {
                fclose($handle);

                return '{"success": false, "error-codes": ["'.ReCaptcha::E_CONNECTION_FAILED.'"]}';
            }
            $totalWritten += $written;
        }

        $response = stream_get_contents($handle);

        fclose($handle);

        if (!is_string($response)) {
            $response = '';
        }

        if (1 !== preg_match('#^HTTP/1\.[01]\s+200\b#', $response)) {
            return '{"success": false, "error-codes": ["'.ReCaptcha::E_BAD_RESPONSE.'"]}';
        }

        $parts = preg_split('#\r?\n\r?\n#', $response, 2);

        if (!is_array($parts) || !isset($parts[1])) {
            return '{"success": false, "error-codes": ["'.ReCaptcha::E_BAD_RESPONSE.'"]}';
        }

        return $parts[1];
    }
}
