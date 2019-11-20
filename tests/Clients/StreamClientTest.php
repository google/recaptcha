<?php

namespace Tests\Clients;

use Google\ReCaptcha\ReCaptcha;
use Google\ReCaptcha\ReCaptchaErrors;
use PHPUnit\Framework\Constraint\IsType;
use Google\ReCaptcha\Clients\StreamClient;
use PHPUnit\Framework\TestCase;
use Google\ReCaptcha\Clients\StreamHandler;

class StreamClientTest extends TestCase
{
    public function testSends()
    {
        $handler = $this->createMock(StreamHandler::class);

        $handler->method('streamContextCreate')
            ->with(new IsType(IsType::TYPE_ARRAY))
            ->willReturn('resource');

        $handler->method('fileGetContents')
            ->with(ReCaptcha::SITE_VERIFY_URL, false, 'resource')
            ->willReturn(json_encode(['success' => true]));

        $response = (new StreamClient('test_secret', ReCaptcha::SITE_VERIFY_URL))
            ->setClient($handler)
            ->send('test_token', '255.255.255.255');

        $this->assertEquals(['success' => true], $response);
    }

    public function testReturnsEmpty()
    {
        $handler = $this->createMock(StreamHandler::class);

        $handler->method('streamContextCreate')
            ->with(new IsType(IsType::TYPE_ARRAY))
            ->willReturn('resource');

        $handler->method('fileGetContents')
            ->with(ReCaptcha::SITE_VERIFY_URL, false, 'resource')
            ->willReturn(false);

        $response = (new StreamClient('test_secret', ReCaptcha::SITE_VERIFY_URL))
            ->setClient($handler)
            ->send('test_token', '255.255.255.255');

        $this->assertEquals([
            'success' => false,
            'error-codes' => [ReCaptchaErrors::E_CONNECTION_FAILED]
        ], $response);
    }
}
