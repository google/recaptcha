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
    public static $fsockopenHostname;
    public static $fsockopenErrno = 0;
    public static $fsockopenErrstr = '';
    public static $fsockopenSuccess = true;
    public static $fwriteData = '';
    public static $fgetsResponses = [];
    public static $feofCount = 0;
    public static $fcloseCalled = false;
}

/**
 * Mock fsockopen in the ReCaptcha\RequestMethod namespace.
 *
 * @param mixed      $hostname
 * @param mixed      $port
 * @param mixed      $errno
 * @param mixed      $errstr
 * @param null|mixed $timeout
 */
function fsockopen($hostname, $port = -1, &$errno = 0, &$errstr = '', $timeout = null)
{
    SocketPostGlobalState::$fsockopenHostname = $hostname;
    $errno = SocketPostGlobalState::$fsockopenErrno;
    $errstr = SocketPostGlobalState::$fsockopenErrstr;

    return SocketPostGlobalState::$fsockopenSuccess ? new \stdClass() : false;
}

/**
 * Mock fwrite in the ReCaptcha\RequestMethod namespace.
 *
 * @param mixed      $handle
 * @param mixed      $string
 * @param null|mixed $length
 */
function fwrite($handle, $string, $length = null)
{
    SocketPostGlobalState::$fwriteData .= $string;

    return strlen($string);
}

/**
 * Mock fgets in the ReCaptcha\RequestMethod namespace.
 *
 * @param mixed      $handle
 * @param null|mixed $length
 */
function fgets($handle, $length = null)
{
    return array_shift(SocketPostGlobalState::$fgetsResponses);
}

/**
 * Mock feof in the ReCaptcha\RequestMethod namespace.
 *
 * @param mixed $handle
 */
function feof($handle)
{
    return empty(SocketPostGlobalState::$fgetsResponses);
}

/**
 * Mock fclose in the ReCaptcha\RequestMethod namespace.
 *
 * @param mixed $handle
 */
function fclose($handle)
{
    SocketPostGlobalState::$fcloseCalled = true;
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

    public function testSubmit()
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

    public function testConnectionFailureReturnsError()
    {
        SocketPostGlobalState::$fsockopenSuccess = false;
        $sp = new SocketPost();
        $response = $sp->submit(new RequestParameters('secret', 'response'));

        $this->assertEquals('{"success": false, "error-codes": ["'.ReCaptcha::E_CONNECTION_FAILED.'"]}', $response);
    }

    public function testBadResponseReturnsError()
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
