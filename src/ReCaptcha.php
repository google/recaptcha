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

use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Component\HttpClient\Psr18Client;
use Psr\Http\Client\ClientInterface as PsrClient;
use Psr\Http\Message\StreamFactoryInterface as StreamFactory;
use Psr\Http\Message\RequestFactoryInterface as RequestFactory;

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
     * The PSR18 Client
     *
     * @var \Psr\Http\Client\ClientInterface
     */
    protected $client;

    /**
     * Request Factory
     *
     * @var \Psr\Http\Message\RequestFactoryInterface
     */
    protected $request;

    /**
     * Stream Factory
     *
     * @var \Psr\Http\Message\StreamFactoryInterface
     */
    protected $stream;

    /**
     * Secret to authenticate the account with the reCAPTCHA servers
     *
     * @var string
     */
    protected $secret;

    /**
     * ReCaptcha constructor.
     *
     * @param  \Psr\Http\Client\ClientInterface $client
     * @param  \Psr\Http\Message\RequestFactoryInterface $request
     * @param  \Psr\Http\Message\StreamFactoryInterface $stream
     */
    public function __construct(PsrClient $client, RequestFactory $request, StreamFactory $stream)
    {
        $this->client = $client;
        $this->request = $request;
        $this->stream = $stream;
    }

    /**
     * Returns the HTTP Client to use against the reCAPTCHA servers.
     *
     * @return \Psr\Http\Client\ClientInterface
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Sets the HTTP Client to use with this.
     *
     * @param  mixed $client The object that this client will use for
     * @return \Google\ReCaptcha\ReCaptcha
     */
    public function setClient($client)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Sets the Request Factory
     *
     * @param  \Psr\Http\Message\RequestFactoryInterface $request
     * @return \Google\ReCaptcha\ReCaptcha
     */
    public function setRequest(RequestFactory $request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Sets the Stream Factory
     *
     * @param  \Psr\Http\Message\StreamFactoryInterface $stream
     * @return \Google\ReCaptcha\ReCaptcha
     */
    public function setStream(StreamFactory $stream)
    {
        $this->stream = $stream;

        return $this;
    }

    /**
     * Sets the secret to use against the reCAPTCHA service.
     *
     * @param  string $secret
     * @return \Google\ReCaptcha\ReCaptcha
     */
    public function setSecret(string $secret)
    {
        $this->secret = $secret;

        return $this;
    }

    /**
     * Verifies a challenge and returns a response.
     *
     * @param  string $token
     * @param  string|null $ip
     * @return \Google\ReCaptcha\ReCaptchaResponse
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function verify(string $token, string $ip = null)
    {
        $array = $this->send($token, $ip);

        $response = $this->parseResponse($array);

        return $this->checkErrors($response);
    }

    /**
     * Receives a request and returns a response from reCAPTCHA servers.
     *
     * @param  string $token The token that identifies the reCAPTCHA challenge.
     * @param  string|null $ip The optional IP of the user challenged.
     * @return array The response from reCAPTCHA servers as an array, which is later parsed.
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function send(string $token, string $ip = null)
    {
        $response = $this->client->sendRequest($this->makeRequest($token, $ip));

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Creates a Request Interface to be consumed by the HTTP Client
     *
     * @param  string $token
     * @param  string|null $ip
     * @return \Psr\Http\Message\RequestInterface
     */
    protected function makeRequest(string $token, string $ip = null)
    {
        return $this->request->createRequest('POST', static::SITE_VERIFY_URL)
            ->withBody($this->prepareBody($token, $ip))
            ->withProtocolVersion('2.0')
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded');
    }

    /**
     * Create a Stream to send with the Request
     *
     * @param  string $token
     * @param  string|null $ip
     * @return \Psr\Http\Message\StreamInterface
     */
    protected function prepareBody(string $token, string $ip = null)
    {
        return $this->stream->createStream(
            http_build_query(array_filter([
                'secret' => $this->secret,
                'response' => $token,
                'remoteip' => $ip,
                'version' => static::VERSION
            ]))
        );
    }

    /**
     * Verifies the challenge or throws an exception if its invalid.
     *
     * @param  string $token
     * @param  string|null $ip
     * @return \Google\ReCaptcha\ReCaptchaResponse
     * @throws \Google\ReCaptcha\FailedReCaptchaException
     * @throws \Psr\Http\Client\ClientExceptionInterface
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
            return (new ReCaptchaResponse)->addErrors([ReCaptchaErrors::E_INVALID_JSON]);
        }

        // The response is malformed, this may mean an unknown error
        if (! isset($response['success'])) {
            return (new ReCaptchaResponse)->addErrors([ReCaptchaErrors::E_UNKNOWN_ERROR]);
        }

        $instance = new ReCaptchaResponse($this->buildArray($response));

        $instance->setConstraints($this->constraints);

        $this->flushConstraints();

        return $instance;
    }

    /**
     * Parses the array to be injected into the ReCaptchaResponse instance.
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
     * Creates a new ReCaptcha challenge verification instance with a default HTTP Client.
     *
     * @param  string $secret
     * @return \Google\ReCaptcha\ReCaptcha
     */
    public static function make(string $secret)
    {
        // This object includes all the necessary PSR-17 factories in a convenient
        // instance, so there is no need to instance multiple factories: we just
        // instance this and add the same to all the classes that depend on it.
        $factory = new Psr17Factory;

        $instance = new static(new Psr18Client(null, $factory, $factory), $factory, $factory);

        return $instance->setSecret($secret);
    }
}
