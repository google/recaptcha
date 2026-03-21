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

namespace ReCaptcha;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Global state for mocking functions in the ReCaptcha namespace.
 */
class GlobalState
{
    public static $isCurlAvailable;
}

/**
 * Mock function_exists in the ReCaptcha namespace.
 *
 * @param mixed $function
 */
function function_exists($function)
{
    if ('curl_version' === $function && !is_null(GlobalState::$isCurlAvailable)) {
        return GlobalState::$isCurlAvailable;
    }

    return \function_exists($function);
}

/**
 * @internal
 *
 * @coversNothing
 */
class ReCaptchaTest extends TestCase
{
    protected function tearDown(): void
    {
        GlobalState::$isCurlAvailable = null;
    }

    #[DataProvider('invalidSecretProvider')]
    public function testExceptionThrownOnInvalidSecret($invalid)
    {
        $this->expectException(\RuntimeException::class);
        $rc = new ReCaptcha($invalid);
    }

    public static function invalidSecretProvider()
    {
        return [
            [''],
            [null],
            [0],
            [new \stdClass()],
            [[]],
        ];
    }

    public function testVerifyReturnsErrorOnMissingResponse()
    {
        $rc = new ReCaptcha('secret');
        $response = $rc->verify('');
        $this->assertFalse($response->isSuccess());
        $this->assertEquals([Recaptcha::E_MISSING_INPUT_RESPONSE], $response->getErrorCodes());
    }

    public function testDefaultRequestMethodWithCurl()
    {
        GlobalState::$isCurlAvailable = true;
        $rc = new ReCaptcha('secret');
        $reflection = new \ReflectionClass($rc);
        $property = $reflection->getProperty('requestMethod');
        $requestMethod = $property->getValue($rc);

        $this->assertInstanceOf(RequestMethod\CurlPost::class, $requestMethod);
    }

    public function testDefaultRequestMethodWithoutCurl()
    {
        GlobalState::$isCurlAvailable = false;
        $rc = new ReCaptcha('secret');
        $reflection = new \ReflectionClass($rc);
        $property = $reflection->getProperty('requestMethod');
        $requestMethod = $property->getValue($rc);

        $this->assertInstanceOf(RequestMethod\Post::class, $requestMethod);
    }

    public function testVerifyReturnsResponse()
    {
        $method = $this->getMockRequestMethod('{"success": true}');
        $rc = new ReCaptcha('secret', $method);
        $response = $rc->verify('response');
        $this->assertTrue($response->isSuccess());
    }

    public function testVerifyReturnsInitialResponseWithoutAdditionalChecks()
    {
        $method = $this->getMockRequestMethod('{"success": true}');
        $rc = new ReCaptcha('secret', $method);
        $initialResponse = $rc->verify('response');
        $this->assertEquals($initialResponse, $rc->verify('response'));
    }

    public function testVerifyHostnameMatch()
    {
        $method = $this->getMockRequestMethod('{"success": true, "hostname": "host.name"}');
        $rc = new ReCaptcha('secret', $method);
        $response = $rc->setExpectedHostname('host.name')->verify('response');
        $this->assertTrue($response->isSuccess());
    }

    public function testVerifyHostnameMisMatch()
    {
        $method = $this->getMockRequestMethod('{"success": true, "hostname": "host.NOTname"}');
        $rc = new ReCaptcha('secret', $method);
        $response = $rc->setExpectedHostname('host.name')->verify('response');
        $this->assertFalse($response->isSuccess());
        $this->assertEquals([ReCaptcha::E_HOSTNAME_MISMATCH], $response->getErrorCodes());
    }

    public function testVerifyApkPackageNameMatch()
    {
        $method = $this->getMockRequestMethod('{"success": true, "apk_package_name": "apk.name"}');
        $rc = new ReCaptcha('secret', $method);
        $response = $rc->setExpectedApkPackageName('apk.name')->verify('response');
        $this->assertTrue($response->isSuccess());
    }

    public function testVerifyApkPackageNameMisMatch()
    {
        $method = $this->getMockRequestMethod('{"success": true, "apk_package_name": "apk.NOTname"}');
        $rc = new ReCaptcha('secret', $method);
        $response = $rc->setExpectedApkPackageName('apk.name')->verify('response');
        $this->assertFalse($response->isSuccess());
        $this->assertEquals([ReCaptcha::E_APK_PACKAGE_NAME_MISMATCH], $response->getErrorCodes());
    }

    public function testVerifyActionMatch()
    {
        $method = $this->getMockRequestMethod('{"success": true, "action": "action/name"}');
        $rc = new ReCaptcha('secret', $method);
        $response = $rc->setExpectedAction('action/name')->verify('response');
        $this->assertTrue($response->isSuccess());
    }

    public function testVerifyActionMisMatch()
    {
        $method = $this->getMockRequestMethod('{"success": true, "action": "action/NOTname"}');
        $rc = new ReCaptcha('secret', $method);
        $response = $rc->setExpectedAction('action/name')->verify('response');
        $this->assertFalse($response->isSuccess());
        $this->assertEquals([ReCaptcha::E_ACTION_MISMATCH], $response->getErrorCodes());
    }

    public function testVerifyAboveThreshold()
    {
        $method = $this->getMockRequestMethod('{"success": true, "score": "0.9"}');
        $rc = new ReCaptcha('secret', $method);
        $response = $rc->setScoreThreshold('0.5')->verify('response');
        $this->assertTrue($response->isSuccess());
    }

    public function testVerifyBelowThreshold()
    {
        $method = $this->getMockRequestMethod('{"success": true, "score": "0.1"}');
        $rc = new ReCaptcha('secret', $method);
        $response = $rc->setScoreThreshold('0.5')->verify('response');
        $this->assertFalse($response->isSuccess());
        $this->assertEquals([ReCaptcha::E_SCORE_THRESHOLD_NOT_MET], $response->getErrorCodes());
    }

    public function testVerifyWithinTimeout()
    {
        // Responses come back like 2018-07-31T13:48:41Z
        $challengeTs = date('Y-M-d\TH:i:s\Z', time());
        $method = $this->getMockRequestMethod('{"success": true, "challenge_ts": "'.$challengeTs.'"}');
        $rc = new ReCaptcha('secret', $method);
        $response = $rc->setChallengeTimeout('1000')->verify('response');
        $this->assertTrue($response->isSuccess());
    }

    public function testVerifyOverTimeout()
    {
        // Responses come back like 2018-07-31T13:48:41Z
        $challengeTs = date('Y-M-d\TH:i:s\Z', time() - 600);
        $method = $this->getMockRequestMethod('{"success": true, "challenge_ts": "'.$challengeTs.'"}');
        $rc = new ReCaptcha('secret', $method);
        $response = $rc->setChallengeTimeout('60')->verify('response');
        $this->assertFalse($response->isSuccess());
        $this->assertEquals([ReCaptcha::E_CHALLENGE_TIMEOUT], $response->getErrorCodes());
    }

    public function testVerifyMergesErrors()
    {
        $method = $this->getMockRequestMethod('{"success": false, "error-codes": ["initial-error"], "score": "0.1"}');
        $rc = new ReCaptcha('secret', $method);
        $response = $rc->setScoreThreshold('0.5')->verify('response');
        $this->assertFalse($response->isSuccess());
        $this->assertEquals(['initial-error', ReCaptcha::E_SCORE_THRESHOLD_NOT_MET], $response->getErrorCodes());
    }

    private function getMockRequestMethod($responseJson)
    {
        $method = $this->createStub(RequestMethod::class);
        $method->method('submit')
            ->willReturn($responseJson)
        ;

        return $method;
    }
}
