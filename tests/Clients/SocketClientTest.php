<?php

namespace Tests\Clients;

use Google\ReCaptcha\ReCaptcha;
use Google\ReCaptcha\ReCaptchaErrors;
use PHPUnit\Framework\Constraint\IsType;
use Google\ReCaptcha\Clients\CurlClient;
use Google\ReCaptcha\Clients\CurlHandler;
use Google\ReCaptcha\Clients\SocketClient;
use PHPUnit\Framework\TestCase;
use Google\ReCaptcha\Clients\SocketHandler;

class SocketClientTest extends TestCase
{
    public function testSends()
    {
        $handler = $this->createMock(SocketHandler::class);

        $handler->method('fsockopen')
            ->with('ssl://www.google.com', 443, 0, '', 30)
            ->willReturn(true);

        $handler->method('fwrite')
            ->with(new IsType(IsType::TYPE_STRING))
            ->willReturn(true);

        $handler->method('feof')
            ->willReturn(true);

        $handler->method('fgets')
            ->with(4096)
            ->willReturn( ''
                /* TODO: return string with response */
            );

        $handler->method('feof')
            ->willReturn(false);

        $handler->method('fclose')
            ->willReturn(false);

        $client = new SocketClient('secret', ReCaptcha::SITE_VERIFY_URL);

        $client->setClient($handler);

        $response = $client->send('test_token', '255.255.255.255');

        $this->assertEquals(['success' => true], $response);
    }

    public function testSendsAndReceivesError()
    {
        // ..
    }
}
