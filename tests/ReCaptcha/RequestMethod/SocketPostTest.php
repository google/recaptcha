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

use PHPUnit\Framework\TestCase;
use ReCaptcha\ReCaptcha;
use ReCaptcha\RequestParameters;

/**
 * Global state for mocking socket functions.
 */
class SocketPostGlobalState
{
    public static ?string $fsockopenHostname = null;
    public static int $fsockopenErrno = 0;
    public static string $fsockopenErrstr = '';
    public static bool $fsockopenSuccess = true;
    public static string $fwriteData = '';

    /**
     * @var array<int, false|string>
     */
    public static array $fgetsResponses = [];
    public static int $feofCount = 0;
    public static bool $fcloseCalled = false;
}

/**
 * Mock fsockopen in the ReCaptcha\RequestMethod namespace.
 */
function fsockopen(string $hostname, int $port = -1, int &$errno = 0, string &$errstr = '', ?float $timeout = null): false|\stdClass
{
    SocketPostGlobalState::$fsockopenHostname = $hostname;
    $errno = SocketPostGlobalState::$fsockopenErrno;
    $errstr = SocketPostGlobalState::$fsockopenErrstr;

    return SocketPostGlobalState::$fsockopenSuccess ? new \stdClass() : false;
}

/**
 * Mock fwrite in the ReCaptcha\RequestMethod namespace.
 */
function fwrite(\stdClass $handle, string $string, ?int $length = null): int
{
    SocketPostGlobalState::$fwriteData .= $string;

    return strlen($string);
}

/**
 * Mock fgets in the ReCaptcha\RequestMethod namespace.
 */
function fgets(\stdClass $handle, ?int $length = null): false|string
{
    $response = array_shift(SocketPostGlobalState::$fgetsResponses);

    return null === $response ? false : $response;
}

/**
 * Mock feof in the ReCaptcha\RequestMethod namespace.
 */
function feof(\stdClass $handle): bool
{
    return empty(SocketPostGlobalState::$fgetsResponses);
}

/**
 * Mock fclose in the ReCaptcha\RequestMethod namespace.
 */
function fclose(\stdClass $handle): bool
{
    SocketPostGlobalState::$fcloseCalled = true;

    return true;
}

/**
 * @internal
 *
 * @coversNothing
 */
class SocketPostTest extends TestCase
{
    protected function setUp(): void
    {
        SocketPostGlobalState::$fsockopenHostname = null;
        SocketPostGlobalState::$fsockopenErrno = 0;
        SocketPostGlobalState::$fsockopenErrstr = '';
        SocketPostGlobalState::$fsockopenSuccess = true;
        SocketPostGlobalState::$fwriteData = '';
        SocketPostGlobalState::$fgetsResponses = [];
        SocketPostGlobalState::$fcloseCalled = false;
    }

    public function testSubmit(): void
    {
        SocketPostGlobalState::$fgetsResponses = [
            "HTTP/1.0 200 OK\r\n",
            "Content-Type: application/json\r\n",
            "\r\n",
            'RESPONSEBODY',
        ];

        $sp = new SocketPost();
        $response = $sp->submit(new RequestParameters('secret', 'response'));

        $this->assertEquals('ssl://www.google.com', SocketPostGlobalState::$fsockopenHostname);
        $this->assertStringContainsString('secret=secret', SocketPostGlobalState::$fwriteData);
        $this->assertStringContainsString('response=response', SocketPostGlobalState::$fwriteData);
        $this->assertEquals('RESPONSEBODY', $response);
        $this->assertTrue(SocketPostGlobalState::$fcloseCalled);
    }

    public function testUrlFailureReturnsError(): void
    {
        $sp = new SocketPost('invalid_url');
        $response = $sp->submit(new RequestParameters('secret', 'response'));

        $this->assertEquals('{"success": false, "error-codes": ["'.ReCaptcha::E_CONNECTION_FAILED.'"]}', $response);
    }

    public function testSubmitWithFgetsFailure(): void
    {
        SocketPostGlobalState::$fgetsResponses = [
            "HTTP/1.0 200 OK\r\n",
            false,
            "Content-Type: application/json\r\n",
            "\r\n",
            'RESPONSEBODY',
        ];

        $sp = new SocketPost();
        $response = $sp->submit(new RequestParameters('secret', 'response'));

        $this->assertEquals('RESPONSEBODY', $response);
        $this->assertTrue(SocketPostGlobalState::$fcloseCalled);
    }

    public function testMalformedResponseReturnsError(): void
    {
        SocketPostGlobalState::$fgetsResponses = [
            "HTTP/1.0 200 OK\r\n",
            "Content-Type: application/json\r\n",
        ];

        $sp = new SocketPost();
        $response = $sp->submit(new RequestParameters('secret', 'response'));

        $this->assertEquals('{"success": false, "error-codes": ["'.ReCaptcha::E_BAD_RESPONSE.'"]}', $response);
    }

    public function testConnectionFailureReturnsError(): void
    {
        SocketPostGlobalState::$fsockopenSuccess = false;
        $sp = new SocketPost();
        $response = $sp->submit(new RequestParameters('secret', 'response'));

        $this->assertEquals('{"success": false, "error-codes": ["'.ReCaptcha::E_CONNECTION_FAILED.'"]}', $response);
    }

    public function testBadResponseReturnsError(): void
    {
        SocketPostGlobalState::$fgetsResponses = [
            "HTTP/1.0 500 Internal Server Error\r\n",
            "\r\n",
            'FAIL',
        ];

        $sp = new SocketPost();
        $response = $sp->submit(new RequestParameters('secret', 'response'));

        $this->assertEquals('{"success": false, "error-codes": ["'.ReCaptcha::E_BAD_RESPONSE.'"]}', $response);
    }
}
