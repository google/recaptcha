<?php

namespace ReCaptcha;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class EnterpriseResponseParserTest extends TestCase
{
    public function testFromJsonValid()
    {
        $json = json_encode([
            'tokenProperties' => [
                'valid' => true,
                'hostname' => 'example.com',
                'action' => 'login',
                'createTime' => '2023-10-27T10:00:00Z',
            ],
            'riskAnalysis' => [
                'score' => 0.9,
            ],
        ]);

        $response = EnterpriseResponseParser::fromJson($json);

        $this->assertTrue($response->isSuccess());
        $this->assertEquals('example.com', $response->getHostname());
        $this->assertEquals('login', $response->getAction());
        $this->assertEquals('2023-10-27T10:00:00Z', $response->getChallengeTs());
        $this->assertEquals(0.9, $response->getScore());
        $this->assertEmpty($response->getErrorCodes());
    }

    public function testFromJsonInvalid()
    {
        $json = json_encode([
            'tokenProperties' => [
                'valid' => false,
                'invalidReason' => 'BROWSER_ERROR',
            ],
        ]);

        $response = EnterpriseResponseParser::fromJson($json);

        $this->assertFalse($response->isSuccess());
        $this->assertEquals(['BROWSER_ERROR'], $response->getErrorCodes());
    }

    public function testFromJsonInvalidFormat()
    {
        $json = 'invalid json';

        $response = EnterpriseResponseParser::fromJson($json);

        $this->assertFalse($response->isSuccess());
        $this->assertEquals([ReCaptcha::E_INVALID_JSON], $response->getErrorCodes());
    }
}
