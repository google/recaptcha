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
class RequestParametersTest extends TestCase
{
    /**
     * @param array<string, string> $expectedArray
     */
    #[DataProvider('provideValidData')]
    public function testToArray(string $secret, string $response, ?string $remoteIp, ?string $version, array $expectedArray, string $expectedQuery): void
    {
        $params = new RequestParameters($secret, $response, $remoteIp, $version);
        $this->assertEquals($params->toArray(), $expectedArray);
    }

    /**
     * @param array<string, string> $expectedArray
     */
    #[DataProvider('provideValidData')]
    public function testToQueryString(string $secret, string $response, ?string $remoteIp, ?string $version, array $expectedArray, string $expectedQuery): void
    {
        $params = new RequestParameters($secret, $response, $remoteIp, $version);
        $this->assertEquals($params->toQueryString(), $expectedQuery);
    }

    /**
     * @return array<int, array{0: string, 1: string, 2: null|string, 3: null|string, 4: array<string, string>, 5: string}>
     */
    public static function provideValidData(): array
    {
        return [
            [
                'SECRET', 'RESPONSE', 'REMOTEIP', 'VERSION',
                ['secret' => 'SECRET', 'response' => 'RESPONSE', 'remoteip' => 'REMOTEIP', 'version' => 'VERSION'],
                'secret=SECRET&response=RESPONSE&remoteip=REMOTEIP&version=VERSION',
            ],
            [
                'SECRET', 'RESPONSE', null, null,
                ['secret' => 'SECRET', 'response' => 'RESPONSE'],
                'secret=SECRET&response=RESPONSE',
            ],
        ];
    }
}
