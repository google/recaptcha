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

namespace ReCaptcha\RequestMethod;

use ReCaptcha\ReCaptcha;
use ReCaptcha\RequestParameters;
use PHPUnit\Framework\TestCase;

class SocketPostTest extends TestCase
{
    public function testSubmitSuccess()
    {
        $socket = $this->getMockBuilder(\ReCaptcha\RequestMethod\Socket::class)
            ->disableOriginalConstructor()
            ->setMethods(array('fsockopen', 'fwrite', 'fgets', 'feof', 'fclose'))
            ->getMock();
        $socket->expects($this->once())
                ->method('fsockopen')
                ->willReturn(true);
        $socket->expects($this->once())
                ->method('fwrite');
        $socket->expects($this->once())
                ->method('fgets')
                ->willReturn("HTTP/1.1 200 OK\n\nRESPONSEBODY");
        $socket->expects($this->exactly(2))
                ->method('feof')
                ->will($this->onConsecutiveCalls(false, true));
        $socket->expects($this->once())
                ->method('fclose')
                ->willReturn(true);

        $ps = new SocketPost($socket);
        $response = $ps->submit(new RequestParameters("secret", "response", "remoteip", "version"));
        $this->assertEquals('RESPONSEBODY', $response);
    }

    public function testOverrideSiteVerifyUrl()
    {
        $socket = $this->getMockBuilder(\ReCaptcha\RequestMethod\Socket::class)
            ->disableOriginalConstructor()
            ->setMethods(array('fsockopen', 'fwrite', 'fgets', 'feof', 'fclose'))
            ->getMock();
        $socket->expects($this->once())
                ->method('fsockopen')
                ->with('ssl://over.ride', 443, 0, '', 30)
                ->willReturn(true);
        $socket->expects($this->once())
                ->method('fwrite')
                ->with($this->matchesRegularExpression('/^POST \/some\/path.*Host: over\.ride/s'));
        $socket->expects($this->once())
                ->method('fgets')
                ->willReturn("HTTP/1.1 200 OK\n\nRESPONSEBODY");
        $socket->expects($this->exactly(2))
                ->method('feof')
                ->will($this->onConsecutiveCalls(false, true));
        $socket->expects($this->once())
                ->method('fclose')
                ->willReturn(true);

        $ps = new SocketPost($socket, 'https://over.ride/some/path');
        $response = $ps->submit(new RequestParameters("secret", "response", "remoteip", "version"));
        $this->assertEquals('RESPONSEBODY', $response);
    }

    public function testSubmitBadResponse()
    {
        $socket = $this->getMockBuilder(\ReCaptcha\RequestMethod\Socket::class)
            ->disableOriginalConstructor()
            ->setMethods(array('fsockopen', 'fwrite', 'fgets', 'feof', 'fclose'))
            ->getMock();
        $socket->expects($this->once())
                ->method('fsockopen')
                ->willReturn(true);
        $socket->expects($this->once())
                ->method('fwrite');
        $socket->expects($this->once())
                ->method('fgets')
                ->willReturn("HTTP/1.1 500 NOPEn\\nBOBBINS");
        $socket->expects($this->exactly(2))
                ->method('feof')
                ->will($this->onConsecutiveCalls(false, true));
        $socket->expects($this->once())
                ->method('fclose')
                ->willReturn(true);

        $ps = new SocketPost($socket);
        $response = $ps->submit(new RequestParameters("secret", "response", "remoteip", "version"));
        $this->assertEquals('{"success": false, "error-codes": ["'.ReCaptcha::E_BAD_RESPONSE.'"]}', $response);
    }

    public function testSubmitBadRequest()
    {
        $socket = $this->getMockBuilder(\ReCaptcha\RequestMethod\Socket::class)
            ->disableOriginalConstructor()
            ->setMethods(array('fsockopen'))
            ->getMock();
        $socket->expects($this->once())
                ->method('fsockopen')
                ->willReturn(false);
        $ps = new SocketPost($socket);
        $response = $ps->submit(new RequestParameters("secret", "response", "remoteip", "version"));
        $this->assertEquals('{"success": false, "error-codes": ["'.ReCaptcha::E_BAD_CONNECTION.'"]}', $response);
    }
}
