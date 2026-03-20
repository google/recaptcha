<?php

namespace ReCaptcha\RequestMethod;

use PHPUnit\Framework\TestCase;
use ReCaptcha\EnterpriseRequestParameters;
use ReCaptcha\RequestParameters;

/**
 * @internal
 *
 * @coversNothing
 */
class EnterpriseCurlPostTest extends TestCase
{
    protected function setUp(): void
    {
        if (!function_exists('curl_version')) {
            $this->markTestSkipped('cURL is not installed');
        }
    }

    public function testSubmitThrowsOnInvalidParameters()
    {
        $this->expectException(\InvalidArgumentException::class);
        $method = new EnterpriseCurlPost();
        $params = new RequestParameters('secret', 'response');
        $method->submit($params);
    }

    public function testSubmitSendsJson()
    {
        $curl = $this->createMock(Curl::class);
        $curl->expects($this->once())
            ->method('init')
            ->willReturn('handle')
        ;

        $curl->expects($this->once())
            ->method('setoptArray')
            ->with('handle', $this->callback(function ($options) {
                return true === $options[CURLOPT_POST]
                    && false !== strpos($options[CURLOPT_HTTPHEADER][0], 'Content-Type: application/json')
                    && false !== strpos($options[CURLOPT_POSTFIELDS], 'event');
            }))
        ;

        $curl->expects($this->once())
            ->method('exec')
            ->willReturn('{"tokenProperties": {"valid": true}}')
        ;

        $method = new EnterpriseCurlPost($curl, 'http://test');
        $params = new EnterpriseRequestParameters('sitekey', 'response');
        $response = $method->submit($params);
        $this->assertEquals('{"tokenProperties": {"valid": true}}', $response);
    }
}
