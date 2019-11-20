<?php

namespace Tests;

use Google\ReCaptcha\ReCaptchaResponse;
use PHPUnit\Framework\TestCase;

class ReCaptchaResponseTest extends TestCase
{
    protected $success = [
        'success' => true,
        'error-codes' => [],
        'hostname' => 'test.local.com',
        'challenge_ts' => '1970-01-01T00:00:00Z',
        'apk_package_name' => 'test_apk',
        'score' => 1.0,
        'action' => 'test_action',
    ];

    public function testReceivesAttributes()
    {
        $response = new ReCaptchaResponse($this->success);

        $this->assertContains($this->success, $response->toArray());
    }

    public function testResponseValid()
    {
        $response = new ReCaptchaResponse($this->success);

        $this->assertTrue($response->valid());
        $this->assertFalse($response->invalid());
    }

    public function testResponseInvalid()
    {
        $response = new ReCaptchaResponse(array_merge($this->success, [
            'success' => false,
        ]));

        $this->assertFalse($response->valid());
        $this->assertTrue($response->invalid());
    }

    public function testHasErrors()
    {
        $response = new ReCaptchaResponse(array_merge($this->success, [
            'success' => false,
        ]));

        $response->setErrors([ 'foo', 'bar' ]);

        $this->assertTrue($response->hasError('foo'));
        $this->assertTrue($response->hasError('foo', 'bar'));
        $this->assertFalse($response->hasError('invalid'));
        $this->assertFalse($response->hasError());


        $response->setErrors([]);

        $this->assertFalse($response->hasError('foo'));
        $this->assertFalse($response->hasError('foo', 'bar'));
        $this->assertFalse($response->hasError('invalid'));
    }

    public function testSingleConstraint()
    {
        $response = new ReCaptchaResponse($this->success);

        $response->setConstraints([
            'foo' => 'bar',
            'quz' => 'qux',
            'lol' => null,
        ]);

        $this->assertEquals('bar', $response->constraint('foo'));
        $this->assertNull($response->constraint('lol'));
    }

    public function testAllowsDynamicGet()
    {
        $response = new ReCaptchaResponse($this->success);

        foreach ($this->success as $key => $value) {
            $this->assertEquals($response->{$key}, $value);
        }
    }

    public function testSerializesToJson()
    {
        $response = new ReCaptchaResponse($this->success);

        $string = (string)$response;
        $json = $response->toJson();
        $encoded = json_encode($response);

        $this->assertIsString($string);
        $this->assertIsString($json);

        $this->assertEquals($string, $json);
        $this->assertEquals($string, $encoded);
        $this->assertEquals($json, $encoded);

        $this->assertNotEmpty(json_decode($json, true));
        $this->assertNotEmpty(json_decode($encoded, true));
        $this->assertNotEmpty(json_decode($string, true));

        $this->assertContains($this->success, json_decode($json, true));
        $this->assertContains($this->success, json_decode($encoded, true));
        $this->assertContains($this->success, json_decode($string, true));
    }

    public function testSerialization()
    {
        $response = new ReCaptchaResponse($this->success);

        $serialized = serialize($response);

        $unserialized = unserialize($serialized);

        $this->assertEquals($response, $unserialized);
    }
}
