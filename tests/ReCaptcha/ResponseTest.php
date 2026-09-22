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

namespace ReCaptcha;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class ResponseTest extends TestCase
{
    /**
     * @param array<string> $errorCodes
     */
    #[DataProvider('provideJson')]
    public function testFromJson(string $json, bool $success, array $errorCodes, ?string $hostname, ?string $challengeTs, ?string $apkPackageName, ?float $score, ?string $action): void
    {
        $response = Response::fromJson($json);
        $this->assertEquals($success, $response->isSuccess());
        $this->assertEquals($errorCodes, $response->getErrorCodes());
        $this->assertEquals($hostname ?? '', $response->getHostname());
        $this->assertEquals($challengeTs ?? '', $response->getChallengeTs());
        $this->assertEquals($apkPackageName ?? '', $response->getApkPackageName());
        $this->assertEquals($score, $response->getScore());
        $this->assertEquals($action ?? '', $response->getAction());
    }

    /**
     * @return array<int, array{0: string, 1: bool, 2: array<string>, 3: null|string, 4: null|string, 5: null|string, 6: null|float, 7: null|string}>
     */
    public static function provideJson(): array
    {
        return [
            [
                '{"success": true}',
                true, [], null, null, null, null, null,
            ],
            [
                '{"success": true, "hostname": "google.com"}',
                true, [], 'google.com', null, null, null, null,
            ],
            [
                '{"success": false, "error-codes": ["test"]}',
                false, ['test'], null, null, null, null, null,
            ],
            [
                '{"success": false, "error-codes": ["test"], "hostname": "google.com"}',
                false, ['test'], 'google.com', null, null, null, null,
            ],
            [
                '{"success": false, "error-codes": ["test"], "hostname": "google.com", "challenge_ts": "timestamp", "apk_package_name": "apk", "score": "0.5", "action": "action"}',
                false, ['test'], 'google.com', 'timestamp', 'apk', 0.5, 'action',
            ],
            [
                '{"success": true, "error-codes": ["test"]}',
                true, [], null, null, null, null, null,
            ],
            [
                '{"success": true, "error-codes": ["test"], "hostname": "google.com"}',
                true, [], 'google.com', null, null, null, null,
            ],
            [
                '{"success": false}',
                false, [ReCaptcha::E_UNKNOWN_ERROR], null, null, null, null, null,
            ],
            [
                '{"success": false, "hostname": "google.com"}',
                false, [ReCaptcha::E_UNKNOWN_ERROR], 'google.com', null, null, null, null,
            ],
            [
                'BAD JSON',
                false, [ReCaptcha::E_INVALID_JSON], null, null, null, null, null,
            ],
            [
                '{"success": false, "error-codes": "invalid-input-secret"}',
                false, [ReCaptcha::E_UNKNOWN_ERROR], null, null, null, null, null,
            ],
            [
                '{"success": false, "error-codes": null}',
                false, [ReCaptcha::E_UNKNOWN_ERROR], null, null, null, null, null,
            ],
            [
                '{"success": false, "error-codes": 123}',
                false, [ReCaptcha::E_UNKNOWN_ERROR], null, null, null, null, null,
            ],
        ];
    }

    public function testIsSuccess(): void
    {
        $response = new Response(true);
        $this->assertTrue($response->isSuccess());

        $response = new Response(false);
        $this->assertFalse($response->isSuccess());

        $response = new Response(true, [], 'example.com');
        $this->assertEquals('example.com', $response->getHostname());
    }

    public function testGetErrorCodes(): void
    {
        $errorCodes = ['test'];
        $response = new Response(true, $errorCodes);
        $this->assertEquals($errorCodes, $response->getErrorCodes());
    }

    public function testGetHostname(): void
    {
        $hostname = 'google.com';
        $errorCodes = [];
        $response = new Response(true, $errorCodes, $hostname);
        $this->assertEquals($hostname, $response->getHostname());
    }

    public function testGetChallengeTs(): void
    {
        $timestamp = 'timestamp';
        $response = new Response(true, [], 'hostname', $timestamp);
        $this->assertEquals($timestamp, $response->getChallengeTs());
    }

    public function testGetApkPackageName(): void
    {
        $apk = 'apk';
        $response = new Response(true, [], 'hostname', 'timestamp', 'apk');
        $this->assertEquals($apk, $response->getApkPackageName());
    }

    public function testGetScore(): void
    {
        $score = 0.5;
        $response = new Response(true, [], 'hostname', 'timestamp', 'apk', $score);
        $this->assertEquals($score, $response->getScore());
    }

    public function testGetAction(): void
    {
        $action = 'homepage';
        $response = new Response(true, [], 'hostname', 'timestamp', 'apk', 0.5, 'homepage');
        $this->assertEquals($action, $response->getAction());
    }

    public function testToArray(): void
    {
        $response = new Response(true, [], 'hostname', 'timestamp', 'apk', 0.5, 'homepage');
        $expected = [
            'success' => true,
            'error-codes' => [],
            'hostname' => 'hostname',
            'challenge_ts' => 'timestamp',
            'apk_package_name' => 'apk',
            'score' => 0.5,
            'action' => 'homepage',
        ];
        $this->assertEquals($expected, $response->toArray());
    }
}
