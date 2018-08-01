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

class ResponseTest extends TestCase
{

    /**
     * @dataProvider provideJson
     */
    public function testFromJson($json, $success, $errorCodes, $hostname, $challengeTs, $apkPackageName, $score, $action)
    {
        $response = Response::fromJson($json);
        $this->assertEquals($success, $response->isSuccess());
        $this->assertEquals($errorCodes, $response->getErrorCodes());
        $this->assertEquals($hostname, $response->getHostname());
        $this->assertEquals($challengeTs, $response->getChallengeTs());
        $this->assertEquals($apkPackageName, $response->getApkPackageName());
        $this->assertEquals($score, $response->getScore());
        $this->assertEquals($action, $response->getAction());
    }

    public function provideJson()
    {
        return array(
            array(
                '{"success": true}',
                true, array(), null, null, null, null, null,
            ),
            array(
                '{"success": true, "hostname": "google.com"}',
                true, array(), 'google.com', null, null, null, null,
            ),
            array(
                '{"success": false, "error-codes": ["test"]}',
                false, array('test'), null, null, null, null, null,
            ),
            array(
                '{"success": false, "error-codes": ["test"], "hostname": "google.com"}',
                false, array('test'), 'google.com', null, null, null, null,
            ),
            array(
                '{"success": false, "error-codes": ["test"], "hostname": "google.com", "challenge_ts": "timestamp", "apk_package_name": "apk", "score": "0.5", "action": "action"}',
                false, array('test'), 'google.com', 'timestamp', 'apk', 0.5, 'action',
            ),
            array(
                '{"success": true, "error-codes": ["test"]}',
                true, array(), null, null, null, null, null,
            ),
            array(
                '{"success": true, "error-codes": ["test"], "hostname": "google.com"}',
                true, array(), 'google.com', null, null, null, null,
            ),
            array(
                '{"success": false}',
                false, array(ReCaptcha::E_UNKNOWN_ERROR), null, null, null, null, null,
            ),
            array(
                '{"success": false, "hostname": "google.com"}',
                false, array(ReCaptcha::E_UNKNOWN_ERROR), 'google.com', null, null, null, null,
            ),
            array(
                'BAD JSON',
                false, array(ReCaptcha::E_INVALID_JSON), null, null, null, null, null,
            ),
        );
    }

    public function testIsSuccess()
    {
        $response = new Response(true);
        $this->assertTrue($response->isSuccess());

        $response = new Response(false);
        $this->assertFalse($response->isSuccess());

        $response = new Response(true, array(), 'example.com');
        $this->assertEquals('example.com', $response->getHostName());
    }

    public function testGetErrorCodes()
    {
        $errorCodes = array('test');
        $response = new Response(true, $errorCodes);
        $this->assertEquals($errorCodes, $response->getErrorCodes());
    }

    public function testGetHostname()
    {
        $hostname = 'google.com';
        $errorCodes = array();
        $response = new Response(true, $errorCodes, $hostname);
        $this->assertEquals($hostname, $response->getHostname());
    }

    public function testGetChallengeTs()
    {
        $timestamp = 'timestamp';
        $errorCodes = array();
        $response = new Response(true, array(), 'hostname', $timestamp);
        $this->assertEquals($timestamp, $response->getChallengeTs());
    }

    public function TestGetApkPackageName()
    {
        $apk = 'apk';
        $response = new Response(true, array(), 'hostname', 'timestamp', 'apk');
        $this->assertEquals($apk, $response->getApkPackageName());
    }

    public function testGetScore()
    {
        $score = 0.5;
        $response = new Response(true, array(), 'hostname', 'timestamp', 'apk', $score);
        $this->assertEquals($score, $response->getScore());
    }

    public function testGetAction()
    {
        $action = 'homepage';
        $response = new Response(true, array(), 'hostname', 'timestamp', 'apk', '0.5', 'homepage');
        $this->assertEquals($action, $response->getAction());
    }

    public function testToArray()
    {
        $response = new Response(true, array(), 'hostname', 'timestamp', 'apk', '0.5', 'homepage');
        $expected = array(
            'success' => true,
            'error-codes' => array(),
            'hostname' => 'hostname',
            'challenge_ts' => 'timestamp',
            'apk_package_name' => 'apk',
            'score' => 0.5,
            'action' => 'homepage',
        );
        $this->assertEquals($expected, $response->toArray());
    }
}
