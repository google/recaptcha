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

namespace Google\ReCaptcha;

use Closure;
use Google\ReCaptcha\Clients\CurlClient;
use Google\ReCaptcha\Clients\ClientInterface;

class ReCaptcha
{
    use HandlesConstraints;
    use ParsesResponse;

    /**
     * Version of this client library.
     *
     * @const string
     */
    public const VERSION = 'php_2.0.0';

    /**
     * URL for reCAPTCHA `siteverify` API.
     *
     * @const string
     */
    public const SITE_VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    /**
     * Default state of the constraints array
     *
     * @var array
     */
    public const CONSTRAINTS_ARRAY = [
        'hostname' => null,
        'apk_package_name' => null,
        'action' => null,
        'threshold' => null,
        'challenge_ts' => null,
    ];

    /**
     * The HTTP client to verify the challenge.
     *
     * @var \Google\ReCaptcha\Clients\ClientInterface
     */
    protected $client;

    /**
     * ReCaptcha constructor.
     *
     * @param  \Google\ReCaptcha\Clients\ClientInterface $client
     */
    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * Returns the HTTP Client to use against the reCAPTCHA servers.
     *
     * @return \Google\ReCaptcha\Clients\ClientInterface
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Sets the HTTP Client to use against the reCAPTCHA servers.
     *
     * @param  \Google\ReCaptcha\Clients\ClientInterface $client
     * @return \Google\ReCaptcha\ReCaptcha
     */
    public function setClient(ClientInterface $client)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Verifies a challenge and returns a response.
     *
     * @param  string $token
     * @param  string|null $ip
     * @return \Google\ReCaptcha\ReCaptchaResponse
     */
    public function verify(string $token, string $ip = null)
    {
        $array = $this->client->send($token, $ip);

        $response = $this->parseResponse($array);

        return $this->checkErrors($response);
    }

    /**
     * Verifies the challenge or throws an exception if its invalid.
     *
     * @param  string $token
     * @param  string|null $ip
     * @return \Google\ReCaptcha\ReCaptchaResponse
     * @throws \Google\ReCaptcha\FailedReCaptchaException
     */
    public function verifyOrThrow(string $token, string $ip = null)
    {
        $response = $this->verify($token, $ip);

        if ($response->valid()) {
            return $response;
        }

        throw new FailedReCaptchaException($response);
    }

    /**
     * Parses the response from reCAPTCHA servers.
     *
     * @param  array $response
     * @return \Google\ReCaptcha\ReCaptchaResponse
     */
    protected function parseResponse(array $response)
    {
        // If the JSON returned an empty array, then it's invalid
        if (empty($response)) {
            return (new ReCaptchaResponse)->setErrors([ReCaptchaErrors::E_INVALID_JSON]);
        }

        // The response is malformed, this may mean an unknown error
        if (! isset($response['success'])) {
            return (new ReCaptchaResponse)->setErrors([ReCaptchaErrors::E_UNKNOWN_ERROR]);
        }

        $instance = new ReCaptchaResponse($this->buildArray($response));

        $instance->setConstraints($this->constraints);

        $this->flushConstraints();

        return $instance;
    }

    /**
     * Parses the array to be injected into the reCAPTCHA ReCaptchaResponse instance.
     *
     * @param  array $response
     * @return array
     */
    protected function buildArray(array $response)
    {
        return [
            'success'          => $response['success'] ?? false,
            'error-codes'      => $response['error-codes'] ?? [],
            'hostname'         => $response['hostname'] ?? null,
            'apk_package_name' => $response['apk_package_name'] ?? null,
            'challenge_ts'     => $response['challenge_ts'] ?? null,
            'score'            => $response['score'] ?? null,
            'action'           => $response['action'] ?? null,
        ];
    }

    /**
     * Creates a new ReCaptcha challenge verification instance.
     *
     * @param  string $secret
     * @param  null|string $client
     * @return \Google\ReCaptcha\ReCaptcha
     */
    public static function make(string $secret, string $client = null)
    {
        $client = $client ?? CurlClient::class;

        return new static(new $client($secret));
    }
}
