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
 * @internal
 *
 * @coversNothing
 */
class PostTest extends TestCase
{
    /**
     * @var null|callable
     */
    public static $assert;

    /**
     * @var RequestParameters
     */
    protected $parameters;

    /**
     * @var int
     */
    protected $runcount = 0;

    public function setUp(): void
    {
        $this->parameters = new RequestParameters('secret', 'response', 'remoteip', 'version');
    }

    public function tearDown(): void
    {
        self::$assert = null;
    }

    public function testHTTPContextOptions(): void
    {
        $req = new Post();
        self::$assert = [$this, 'httpContextOptionsCallback'];
        $req->submit($this->parameters);
        $this->assertEquals(1, $this->runcount, 'The assertion was ran');
    }

    public function testSSLContextOptions(): void
    {
        $req = new Post();
        self::$assert = [$this, 'sslContextOptionsCallback'];
        $req->submit($this->parameters);
        $this->assertEquals(1, $this->runcount, 'The assertion was ran');
    }

    public function testOverrideVerifyUrl(): void
    {
        $req = new Post('https://over.ride/some/path');
        self::$assert = [$this, 'overrideUrlOptions'];
        $req->submit($this->parameters);
        $this->assertEquals(1, $this->runcount, 'The assertion was ran');
    }

    public function testConnectionFailureReturnsError(): void
    {
        $req = new Post('https://bad.connection/');
        self::$assert = [$this, 'connectionFailureResponse'];
        $response = $req->submit($this->parameters);
        $this->assertEquals('{"success": false, "error-codes": ["'.ReCaptcha::E_CONNECTION_FAILED.'"]}', $response);
    }

    public function connectionFailureResponse(): bool
    {
        return false;
    }

    /**
     * @param array{0: string, 1: bool, 2: resource} $args
     */
    public function overrideUrlOptions(array $args): bool
    {
        ++$this->runcount;
        $this->assertEquals('https://over.ride/some/path', $args[0]);

        return false;
    }

    /**
     * @param array{0: string, 1: bool, 2: resource} $args
     */
    public function httpContextOptionsCallback(array $args): bool
    {
        ++$this->runcount;
        $this->assertCommonOptions($args);

        /** @var array<string, array<string, mixed>> $options */
        $options = stream_context_get_options($args[2]);
        $this->assertArrayHasKey('http', $options);

        $this->assertArrayHasKey('method', $options['http']);
        $this->assertEquals('POST', $options['http']['method']);

        $this->assertArrayHasKey('content', $options['http']);
        $this->assertEquals($this->parameters->toQueryString(), $options['http']['content']);

        $this->assertArrayHasKey('header', $options['http']);
        $this->assertIsString($options['http']['header']);
        $this->assertStringContainsStringIgnoringCase('Content-type: application/x-www-form-urlencoded', (string) $options['http']['header']);

        $this->assertArrayHasKey('timeout', $options['http']);
        $this->assertEquals(60, $options['http']['timeout']);

        return false;
    }

    /**
     * @param array{0: string, 1: bool, 2: resource} $args
     */
    public function sslContextOptionsCallback(array $args): bool
    {
        ++$this->runcount;
        $this->assertCommonOptions($args);

        /** @var array<string, array<string, mixed>> $options */
        $options = stream_context_get_options($args[2]);
        $this->assertArrayHasKey('ssl', $options);
        $this->assertArrayHasKey('verify_peer', $options['ssl']);
        $this->assertTrue($options['ssl']['verify_peer']);
        $this->assertArrayHasKey('verify_peer_name', $options['ssl']);
        $this->assertTrue($options['ssl']['verify_peer_name']);

        return false;
    }

    /**
     * @param array{0: string, 1: bool, 2: resource} $args
     */
    protected function assertCommonOptions(array $args): void
    {
        $this->assertCount(3, $args);
        $this->assertStringStartsWith('https://www.google.com/', $args[0]);
        $this->assertFalse($args[1]);
        $this->assertTrue(is_resource($args[2]), 'The context options should be a resource');
    }
}

/**
 * @param mixed ...$args
 *
 * @return false|string
 */
function file_get_contents(...$args)
{
    if (PostTest::$assert) {
        $result = call_user_func(PostTest::$assert, $args);

        return is_string($result) ? $result : false;
    }

    // Since we can't represent maxlen in userland...
    $result = call_user_func_array('file_get_contents', $args);

    return is_string($result) ? $result : false;
}
