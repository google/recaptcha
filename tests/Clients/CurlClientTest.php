<?php

namespace Tests\Clients;

use Google\ReCaptcha\ReCaptcha;
use PHPUnit\Framework\TestCase;
use Google\ReCaptcha\ReCaptchaErrors;
use Google\ReCaptcha\Clients\CurlClient;
use PHPUnit\Framework\Constraint\IsType;
use Google\ReCaptcha\Clients\CurlHandler;

class CurlClientTest extends TestCase
{
    public function testSends()
    {
        $handler = $this->createMock(CurlHandler::class);

        $handler->method('init')
            ->with(ReCaptcha::SITE_VERIFY_URL)
            ->willReturn(true);

        $handler->method('setoptArray')
            ->with(true, new IsType(IsType::TYPE_ARRAY))
            ->willReturn(true);

        $handler->method('exec')
            ->with(true)
            ->willReturn(json_encode([
                'success' => true
            ]));

        $handler->method('close')
            ->with(true);

        $client = new CurlClient('secret', ReCaptcha::SITE_VERIFY_URL);

        $client->setClient($handler);

        $response = $client->send('test_token', '255.255.255.255');

        $this->assertEquals(['success' => true], $response);
    }

    public function testSendsAndReceivesError()
    {
        $handler = $this->createMock(CurlHandler::class);

        $handler->method('init')
            ->with(ReCaptcha::SITE_VERIFY_URL)
            ->willReturn(true);

        $handler->method('setoptArray')
            ->with(true, new IsType(IsType::TYPE_ARRAY))
            ->willReturn(true);

        $handler->method('exec')
            ->with(true)
            ->willReturn(false);

        $handler->method('close')
            ->with(true);

        $client = new CurlClient('secret', ReCaptcha::SITE_VERIFY_URL);

        $client->setClient($handler);

        $response = $client->send('test_token', '255.255.255.255');

        $this->assertEquals([
            'success' => false,
            'error-codes' => [ReCaptchaErrors::E_CONNECTION_FAILED]
        ], $response);
    }
}
