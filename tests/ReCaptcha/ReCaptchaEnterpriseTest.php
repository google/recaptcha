<?php

namespace ReCaptcha;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class ReCaptchaEnterpriseTest extends TestCase
{
    public function testVerifyReturnsErrorOnMissingResponse()
    {
        $rc = new ReCaptchaEnterprise('project-id', 'api-key', 'site-key');
        $response = $rc->verify('');
        $this->assertFalse($response->isSuccess());
        $this->assertEquals([ReCaptcha::E_MISSING_INPUT_RESPONSE], $response->getErrorCodes());
    }

    public function testVerifyValidToken()
    {
        $method = $this->createStub(RequestMethod::class);
        $method->method('submit')
            ->willReturn('{"tokenProperties": {"valid": true}}')
        ;

        $rc = new ReCaptchaEnterprise('project-id', 'api-key', 'site-key', $method);
        $response = $rc->verify('token-response');
        $this->assertTrue($response->isSuccess());
    }

    public function testVerifyInvalidToken()
    {
        $method = $this->createStub(RequestMethod::class);
        $method->method('submit')
            ->willReturn('{"tokenProperties": {"valid": false, "invalidReason": "BROWSER_ERROR"}}')
        ;

        $rc = new ReCaptchaEnterprise('project-id', 'api-key', 'site-key', $method);
        $response = $rc->verify('token-response');
        $this->assertFalse($response->isSuccess());
        $this->assertEquals(['BROWSER_ERROR'], $response->getErrorCodes());
    }

    public function testDefaultRequestMethod()
    {
        $rc = new ReCaptchaEnterprise('project-id', 'api-key', 'site-key');
        $reflection = new \ReflectionClass($rc);
        $property = $reflection->getProperty('requestMethod');
        $requestMethod = $property->getValue($rc);

        if (function_exists('curl_version')) {
            $this->assertInstanceOf(RequestMethod\EnterpriseCurlPost::class, $requestMethod);
        } else {
            $this->assertInstanceOf(RequestMethod\EnterprisePost::class, $requestMethod);
        }
    }
}
