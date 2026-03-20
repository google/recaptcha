<?php

namespace ReCaptcha;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class EnterpriseRequestParametersTest extends TestCase
{
    public function testToJson()
    {
        $params = new EnterpriseRequestParameters('site-key', 'token-response', 'expected-action');
        $expected = json_encode([
            'event' => [
                'token' => 'token-response',
                'siteKey' => 'site-key',
                'expectedAction' => 'expected-action',
            ],
        ]);
        $this->assertEquals($expected, $params->toJson());
    }

    public function testToJsonWithoutAction()
    {
        $params = new EnterpriseRequestParameters('site-key', 'token-response');
        $expected = json_encode([
            'event' => [
                'token' => 'token-response',
                'siteKey' => 'site-key',
            ],
        ]);
        $this->assertEquals($expected, $params->toJson());
    }
}
