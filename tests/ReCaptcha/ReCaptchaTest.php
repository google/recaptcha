<?php
/**
 * This is a PHP library that handles calling reCAPTCHA.
 *
 * @copyright Copyright (c) 2015, Google Inc.
 * @link      https://www.google.com/recaptcha
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace ReCaptcha;

use PHPUnit\Framework\TestCase;

class ReCaptchaTest extends TestCase
{

    /**
     * @expectedException \RuntimeException
     * @dataProvider invalidSecretProvider
     */
    public function testExceptionThrownOnInvalidSecret($invalid)
    {
        $rc = new ReCaptcha($invalid);
    }

    public function invalidSecretProvider()
    {
        return array(
            array(''),
            array(null),
            array(0),
            array(new \stdClass()),
            array(array()),
        );
    }

    public function testVerifyReturnsErrorOnMissingResponse()
    {
        $rc = new ReCaptcha('secret');
        $response = $rc->verify('');
        $this->assertFalse($response->isSuccess());
        $this->assertEquals(array(Recaptcha::E_MISSING_INPUT_RESPONSE), $response->getErrorCodes());
    }

    private function getMockRequestMethod($responseJson)
    {
        $method = $this->getMockBuilder(\ReCaptcha\RequestMethod::class)
            ->disableOriginalConstructor()
            ->setMethods(array('submit'))
            ->getMock();
        $method->expects($this->any())
            ->method('submit')
            ->with($this->callback(function ($params) {
                return true;
            }))
            ->will($this->returnValue($responseJson));
        return $method;
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
        $this->assertEquals(array(ReCaptcha::E_HOSTNAME_MISMATCH), $response->getErrorCodes());
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
        $this->assertEquals(array(ReCaptcha::E_APK_PACKAGE_NAME_MISMATCH), $response->getErrorCodes());
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
        $this->assertEquals(array(ReCaptcha::E_ACTION_MISMATCH), $response->getErrorCodes());
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
        $this->assertEquals(array(ReCaptcha::E_SCORE_THRESHOLD_NOT_MET), $response->getErrorCodes());
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
        $this->assertEquals(array(ReCaptcha::E_CHALLENGE_TIMEOUT), $response->getErrorCodes());
    }

    public function testVerifyMergesErrors()
    {
        $method = $this->getMockRequestMethod('{"success": false, "error-codes": ["initial-error"], "score": "0.1"}');
        $rc = new ReCaptcha('secret', $method);
        $response = $rc->setScoreThreshold('0.5')->verify('response');
        $this->assertFalse($response->isSuccess());
        $this->assertEquals(array('initial-error', ReCaptcha::E_SCORE_THRESHOLD_NOT_MET), $response->getErrorCodes());
    }
}
