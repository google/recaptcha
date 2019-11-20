<?php

namespace Tests;

use TypeError;
use Google\ReCaptcha\ReCaptcha;
use PHPUnit\Framework\TestCase;
use Google\ReCaptcha\ReCaptchaErrors;
use Google\ReCaptcha\ReCaptchaResponse;
use Google\ReCaptcha\Clients\CurlClient;
use Google\ReCaptcha\Clients\ClientInterface;
use Google\ReCaptcha\FailedReCaptchaException;

class ReCaptchaTest extends TestCase
{
    public function testCreatesReCaptchaInstanceWithDefaultClient()
    {
        $recaptcha = ReCaptcha::make('test_secret');

        $this->assertInstanceOf(ReCaptcha::class, $recaptcha);

        $this->assertInstanceOf(CurlClient::class, $recaptcha->getClient());
    }

    public function testCreatesReCaptchaInstanceWithCustomClient()
    {
        $recaptcha = ReCaptcha::make('bar', TestHttpClient::class);

        $this->assertInstanceOf(TestHttpClient::class, $recaptcha->getClient());
    }

    public function testCreatesReCaptchaInstanceWithObjectClientReturnException()
    {
        $this->expectException(TypeError::class);

        $client = new TestHttpClient('test_secret', 'test_url');

        $recaptcha = ReCaptcha::make('bar', $client);

        $this->assertInstanceOf(TestHttpClient::class, $recaptcha->getclient());
    }

    public function testGetAndSetClient()
    {
        $recaptcha = ReCaptcha::make('bar');

        $client = new TestHttpClient('test_secret', 'test_url');

        $this->assertInstanceOf(CurlClient::class, $recaptcha->getclient());

        $recaptcha->setClient($client);

        $this->assertInstanceOf(TestHttpClient::class, $recaptcha->getclient());
    }

    public function testVerifiesReCaptcha()
    {
        $client = $this->createMock(ClientInterface::class);

        $client->method('send')
            ->with('test_token')
            ->willReturn($array = [
                'success' => true,
                'error-codes' => [],
                'hostname' => 'test.local.com',
                'challenge_ts' => '1970-01-01T00:00:00Z',
                'apk_package_name' => 'test_apk',
                'score' => 1.0,
                'action' => 'test_action',
            ]);

        $recaptcha = new ReCaptcha($client);

        $response = $recaptcha->verify('test_token');

        $this->assertTrue($response->valid());
        $this->assertInstanceOf(ReCaptchaResponse::class, $response);
        $this->assertContains($array, $response->toArray());
    }

    public function testVerifiesFailedReCaptcha()
    {
        $client = $this->createMock(ClientInterface::class);

        $client->method('send')
            ->with('test_token')
            ->willReturn($array = [
                'success' => false,
                'error-codes' => [
                    'test_error'
                ],
                'hostname' => 'test.local.com',
                'challenge_ts' => '1970-01-01T00:00:00Z',
                'apk_package_name' => 'test_apk',
                'score' => 1.0,
                'action' => 'test_action',
            ]);

        $recaptcha = new ReCaptcha($client);

        $response = $recaptcha->verify('test_token');

        $this->assertTrue($response->invalid());
        $this->assertEquals([
            'test_error'
        ], $response->errors());
    }

    public function testVerifiesReCaptchaWithIp()
    {
        $client = $this->createMock(ClientInterface::class);

        $client->method('send')
            ->with($test_token = 'test_token', $ip = 'test_ip')
            ->willReturn($array = [
                'success' => true,
                'error-codes' => [],
                'hostname' => 'test.local.com',
                'challenge_ts' => '1970-01-01T00:00:00Z',
                'apk_package_name' => 'test_apk',
                'score' => 1.0,
                'action' => 'test_action',
            ]);

        $recaptcha = new ReCaptcha($client);

        $response = $recaptcha->verify($test_token, $ip);

        $this->assertInstanceOf(ReCaptchaResponse::class, $response);
        $this->assertContains($array, $response->toArray());
    }

    public function testVerifiesReCaptchaOrThrowsException()
    {
        $this->expectException(FailedReCaptchaException::class);

        $client = $this->createMock(ClientInterface::class);

        $client->method('send')
            ->with($test_token = 'test_token', $ip = 'test_ip')
            ->willReturn($array = [
                'success' => false,
                'error-codes' => [],
                'hostname' => 'test.local.com',
                'challenge_ts' => '1970-01-01T00:00:00Z',
                'apk_package_name' => 'test_apk',
                'score' => 1.0,
                'action' => 'test_action',
            ]);

        try {
            (new ReCaptcha($client))->verifyOrThrow($test_token, $ip);
        } catch (FailedReCaptchaException $exception) {
            $this->assertInstanceOf(ReCaptchaResponse::class, $exception->getResponse());

            throw $exception;
        }
    }

    public function testVerifiesReCaptchaDoesntThrowsException()
    {
        $client = $this->createMock(ClientInterface::class);

        $client->method('send')
            ->with($test_token = 'test_token', $ip = 'test_ip')
            ->willReturn($array = [
                'success' => true,
                'error-codes' => [],
                'hostname' => 'test.local.com',
                'challenge_ts' => '1970-01-01T00:00:00Z',
                'apk_package_name' => 'test_apk',
                'score' => 1.0,
                'action' => 'test_action',
            ]);

        $response = (new ReCaptcha($client))->verifyOrThrow($test_token, $ip);

        $this->assertTrue($response->valid());
    }

    public function testBuildsConstraints()
    {
        $recaptcha = ReCaptcha::make('test_secret');

        $build = $recaptcha->hostname($hostname = 'test_hostname')
            ->apkPackageName($apkPackageName = 'test_apk_package_name')
            ->action($action = 'test_action')
            ->threshold($threshold = 0.7)
            ->challengeTs($challengeTs = rand(1, 120));

        $this->assertInstanceOf(ReCaptcha::class, $build);

        $array = [
            'hostname' => $hostname,
            'apk_package_name' => $apkPackageName,
            'action' => $action,
            'threshold' => $threshold,
            'challenge_ts' => $challengeTs,
        ];

        $this->assertEquals($array, $recaptcha->getConstraints());
        $this->assertEquals($array, $build->getConstraints());
    }

    public function testFlushesConstraints()
    {
        $recaptcha = ReCaptcha::make('test_secret')
            ->hostname($hostname = 'test_hostname')
            ->apkPackageName($apkPackageName = 'test_apk_package_name')
            ->action($action = 'test_action')
            ->threshold($threshold = 0.7)
            ->challengeTs($challengeTs = rand(1, 120));

        $this->assertEquals(ReCaptcha::CONSTRAINTS_ARRAY, $recaptcha->flushConstraints()->getConstraints());
    }

    public function testSanitizesAction()
    {
        $action = '/unsanitized@action/to-test_here?foo=bar&quz=qux';

        $recaptcha = ReCaptcha::make('test_secret')
            ->saneAction($action);

        $this->assertEquals('/unsanitizedaction/to_test_here', $recaptcha->getConstraints()['action']);
    }

    public function testConstraintsErrors()
    {
        $client = $this->createMock(ClientInterface::class);

        $client->method('send')
            ->with($test_token = 'test_token', $ip = 'test_ip')
            ->willReturn($array = [
                'success' => true,
                'error-codes' => [],
                'hostname' => 'test.local.com',
                'challenge_ts' => '1970-01-01T00:00:01Z',
                'apk_package_name' => 'test_apk',
                'score' => 0.5,
                'action' => 'test_action',
            ]);

        $response = (new ReCaptcha($client))
            ->hostname('invalid')
            ->apkPackageName('invalid')
            ->challengeTs(1)
            ->threshold(0.9)
            ->action('invalid')
            ->verify($test_token, $ip);

        $errors = [
            ReCaptchaErrors::E_HOSTNAME_MISMATCH,
            ReCaptchaErrors::E_APK_PACKAGE_NAME_MISMATCH,
            ReCaptchaErrors::E_ACTION_MISMATCH,
            ReCaptchaErrors::E_SCORE_THRESHOLD_NOT_MET,
            ReCaptchaErrors::E_CHALLENGE_TIMEOUT,
        ];

        $this->assertEquals($errors, $response->errors());
        $this->assertFalse($response->valid());
    }

    public function testInvalidJsonResponse()
    {
        $client = $this->createMock(ClientInterface::class);

        $client->method('send')
            ->with($test_token = 'test_token', $ip = 'test_ip')
            ->willReturn([]);

        $response = (new ReCaptcha($client))
            ->hostname('invalid')
            ->apkPackageName('invalid')
            ->challengeTs(1)
            ->threshold(0.9)
            ->action('invalid')
            ->verify($test_token, $ip);

        $this->assertEquals([ReCaptchaErrors::E_INVALID_JSON], $response->errors());
        $this->assertFalse($response->valid());
    }

    public function testUnknownError()
    {
        $client = $this->createMock(ClientInterface::class);

        $client->method('send')
            ->with($test_token = 'test_token', $ip = 'test_ip')
            ->willReturn([
                'invalid' => 'value'
            ]);

        $response = (new ReCaptcha($client))
            ->hostname('invalid')
            ->apkPackageName('invalid')
            ->challengeTs(1)
            ->threshold(0.9)
            ->action('invalid')
            ->verify($test_token, $ip);

        $this->assertEquals([ReCaptchaErrors::E_UNKNOWN_ERROR], $response->errors());
        $this->assertFalse($response->valid());
    }
}

class TestHttpClient implements ClientInterface
{
    public function __construct(string $secret, string $url = ReCaptcha::SITE_VERIFY_URL)
    {
    }
    public function send(string $token, string $ip = null) : array
    {
    }
    public function setClient($client) : ClientInterface
    {
    }
}
