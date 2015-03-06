<?php

namespace ReCaptcha;

/**
 * Test RequestParameters
 */
class RequestParametersTest extends \PHPUnit_Framework_TestCase
{

    public function provideValidData()
    {
        return array(
            array('SECRET', 'RESPONSE', 'REMOTEIP', 'VERSION',
                array('secret' => 'SECRET', 'response' => 'RESPONSE', 'remoteip' => 'REMOTEIP', 'version' => 'VERSION'),
                'secret=SECRET&response=RESPONSE&remoteip=REMOTEIP&version=VERSION'),
            array('SECRET', 'RESPONSE', null, null,
                array('secret' => 'SECRET', 'response' => 'RESPONSE'),
                'secret=SECRET&response=RESPONSE'),
        );
    }

    /**
     * @covers ReCaptcha\RequestParameters::toArray
     * @dataProvider provideValidData
     */
    public function testToArray($secret, $response, $remoteIp, $version, $expectedArray, $expectedQuery)
    {
        $params = new RequestParameters($secret, $response, $remoteIp, $version);
        $this->assertEquals($params->toArray(), $expectedArray);
    }

    /**
     * @covers ReCaptcha\RequestParameters::toQueryString
     * @dataProvider provideValidData
     */
    public function testToQueryString($secret, $response, $remoteIp, $version, $expectedArray, $expectedQuery)
    {
        $params = new RequestParameters($secret, $response, $remoteIp, $version);
        $this->assertEquals($params->toQueryString(), $expectedQuery);
    }
}
