<?php

namespace ReCaptcha;

/**
 * Test Response
 */
class ResponseTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers ReCaptcha\Response::fromJson
     * @dataProvider provideJson
     */
    public function testFromJson($json, $success, $errorCodes)
    {
        $response = Response::fromJson($json);
        $this->assertEquals($success, $response->isSuccess());
        $this->assertEquals($errorCodes, $response->getErrorCodes());
    }

    public function provideJson()
    {
        return array(
            array('{"success": true}', true, array()),
            array('{"success": false, "error-codes": ["test"]}', false, array('test')),
            array('{"success": true, "error-codes": ["test"]}', true, array()),
        );
    }

    /**
     * @covers ReCaptcha\Response::isSuccess
     * @todo   Implement testIsSuccess().
     */
    public function testIsSuccess()
    {
        $response = new Response(true);
        $this->assertTrue($response->isSuccess());

        $response = new Response(false);
        $this->assertFalse($response->isSuccess());
    }

    /**
     * @covers ReCaptcha\Response::getErrorCodes
     * @todo   Implement testGetErrorCodes().
     */
    public function testGetErrorCodes()
    {
        $errorCodes = array('test');
        $response = new Response(true, $errorCodes);
        $this->assertEquals($errorCodes, $response->getErrorCodes());
    }
}
