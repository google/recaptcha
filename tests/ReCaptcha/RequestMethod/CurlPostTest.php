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
 * Global state for mocking curl functions.
 */
class CurlPostGlobalState
{
    public static ?string $initUrl = null;

    /**
     * @var null|array<int, mixed>
     */
    public static ?array $setoptArrayOptions = null;

    public static bool|string $execResponse = 'RESPONSEBODY';
}

/**
 * Mock curl_init in the ReCaptcha\RequestMethod namespace.
 */
function curl_init(?string $url = null): \stdClass
{
    CurlPostGlobalState::$initUrl = $url;

    return new \stdClass();
}

/**
 * Mock curl_setopt_array in the ReCaptcha\RequestMethod namespace.
 *
 * @param array<int, mixed> $options
 */
function curl_setopt_array(\stdClass $ch, array $options): bool
{
    CurlPostGlobalState::$setoptArrayOptions = $options;

    return true;
}

/**
 * Mock curl_exec in the ReCaptcha\RequestMethod namespace.
 */
function curl_exec(\stdClass $ch): bool|string
{
    return CurlPostGlobalState::$execResponse;
}

/**
 * @internal
 *
 * @coversNothing
 */
class CurlPostTest extends TestCase
{
    protected function setUp(): void
    {
        CurlPostGlobalState::$initUrl = null;
        CurlPostGlobalState::$setoptArrayOptions = null;
        CurlPostGlobalState::$execResponse = 'RESPONSEBODY';
    }

    public function testSubmit(): void
    {
        $pc = new CurlPost();
        $response = $pc->submit(new RequestParameters('secret', 'response'));

        $this->assertEquals(ReCaptcha::SITE_VERIFY_URL, CurlPostGlobalState::$initUrl);

        /** @var array<int, mixed> $options */
        $options = CurlPostGlobalState::$setoptArrayOptions;
        $this->assertTrue($options[CURLOPT_POST]);
        $this->assertEquals('RESPONSEBODY', $response);
    }

    public function testOverrideSiteVerifyUrl(): void
    {
        $url = 'OVERRIDE';
        $pc = new CurlPost($url);
        $response = $pc->submit(new RequestParameters('secret', 'response'));

        $this->assertEquals($url, CurlPostGlobalState::$initUrl);
        $this->assertEquals('RESPONSEBODY', $response);
    }

    public function testConnectionFailureReturnsError(): void
    {
        CurlPostGlobalState::$execResponse = false;
        $pc = new CurlPost();
        $response = $pc->submit(new RequestParameters('secret', 'response'));

        $this->assertEquals('{"success": false, "error-codes": ["'.ReCaptcha::E_CONNECTION_FAILED.'"]}', $response);
    }
}
