<?php

namespace Tests\Clients;

use Google\ReCaptcha\ReCaptcha;
use PHPUnit\Framework\TestCase;
use Google\ReCaptcha\ReCaptchaErrors;
use PHPUnit\Framework\Constraint\IsType;
use Google\ReCaptcha\Clients\SocketClient;
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

        $handler->expects($this->exactly(2))
            ->method('feof')
            ->will($this->onConsecutiveCalls(false, true));

        $handler->method('fgets')
            ->with(4096)
            ->willReturn(
                <<<EOF
HTTP/1.1 200 OK
Content-Type: application/json; charset=utf-8
Date: Thu, 1 Jan 1970 00:00:00 GMT
Expires: Thu, 1 Jan 1970 00:00:00 GMT
Cache-Control: private, max-age=0
X-Content-Type-Options: nosniff
X-Frame-Options: SAMEORIGIN
X-XSS-Protection: 1; mode=block
Server: GSE
Alt-Svc: quic=":443"; ma=2592000; v="46,43",h3-Q050=":443"; ma=2592000,h3-Q049=":443"; ma=2592000,h3-Q048=":443"; ma=2592000,h3-Q046=":443"; ma=2592000,h3-Q043=":443"; ma=2592000
Accept-Ranges: none
Vary: Accept-Encoding
Connection: close

{
  "success": true,
  "challenge_ts": "1970-01-01T00:00:01Z",
  "hostname": "testkey.google.com"
}
EOF
            );

        $handler->method('fclose')
            ->willReturn(true);

        $client = new SocketClient('secret', ReCaptcha::SITE_VERIFY_URL);

        $client->setClient($handler);

        $response = $client->send('test_token', '255.255.255.255');

        $this->assertEquals([
            'success' => true,
            'challenge_ts' => '1970-01-01T00:00:01Z',
            'hostname' => 'testkey.google.com',
        ], $response);
    }

    public function testCantOpenConnection()
    {
        $handler = $this->createMock(SocketHandler::class);

        $handler->method('fsockopen')
            ->with('ssl://www.google.com', 443, 0, '', 30)
            ->willReturn(false);

        $client = new SocketClient('secret', ReCaptcha::SITE_VERIFY_URL);

        $client->setClient($handler);

        $response = $client->send('test_token', '255.255.255.255');

        $this->assertEquals([
            'success' => false,
            'error-codes' => [ReCaptchaErrors::E_CONNECTION_FAILED]
        ], $response);
    }

    public function testSendsAndReceivesError()
    {
        $handler = $this->createMock(SocketHandler::class);

        $handler->method('fsockopen')
            ->with('ssl://www.google.com', 443, 0, '', 30)
            ->willReturn(true);

        $handler->method('fwrite')
            ->with(new IsType(IsType::TYPE_STRING))
            ->willReturn(true);

        $handler->expects($this->exactly(2))
            ->method('feof')
            ->will($this->onConsecutiveCalls(false, true));

        $handler->method('fgets')
            ->with(4096)
            ->willReturn(
                <<<EOF
HTTP/1.1 500 NOPE
Content-Type: application/json; charset=utf-8
Date: Thu, 1 Jan 1970 00:00:00 GMT
Expires: Thu, 1 Jan 1970 00:00:00 GMT
Cache-Control: private, max-age=0
X-Content-Type-Options: nosniff
X-Frame-Options: SAMEORIGIN
X-XSS-Protection: 1; mode=block
Server: GSE
Alt-Svc: quic=":443"; ma=2592000; v="46,43",h3-Q050=":443"; ma=2592000,h3-Q049=":443"; ma=2592000,h3-Q048=":443"; ma=2592000,h3-Q046=":443"; ma=2592000,h3-Q043=":443"; ma=2592000
Accept-Ranges: none
Vary: Accept-Encoding
Connection: close
EOF
            );

        $handler->method('fclose')
            ->willReturn(true);

        $client = new SocketClient('secret', ReCaptcha::SITE_VERIFY_URL);

        $client->setClient($handler);

        $response = $client->send('test_token', '255.255.255.255');

        $this->assertEquals([
            'success' => false,
            'error-codes' => [ReCaptchaErrors::E_BAD_RESPONSE]
        ], $response);
    }
}
