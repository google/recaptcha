<?php

namespace ReCaptcha\RequestMethod;

use PHPUnit\Framework\TestCase;
use ReCaptcha\RequestParameters;

/**
 * @internal
 *
 * @coversNothing
 */
class EnterprisePostTest extends TestCase
{
    public function testSubmitThrowsOnInvalidParameters()
    {
        $this->expectException(\InvalidArgumentException::class);
        $method = new EnterprisePost();
        $params = new RequestParameters('secret', 'response');
        $method->submit($params);
    }
}
