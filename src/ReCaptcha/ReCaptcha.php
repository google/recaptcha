<?php
/**
 * This is a PHP library that handles calling reCAPTCHA.
 *
 * BSD 3-Clause License
 * @copyright (c) 2019, Google Inc.
 * @link https://www.google.com/recaptcha
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 * 1. Redistributions of source code must retain the above copyright notice, this
 *    list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 *    this list of conditions and the following disclaimer in the documentation
 *    and/or other materials provided with the distribution.
 *
 * 3. Neither the name of the copyright holder nor the names of its
 *    contributors may be used to endorse or promote products derived from
 *    this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
 * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

namespace ReCaptcha;

/**
 * reCAPTCHA client.
 */
class ReCaptcha
{
    /**
     * Version of this client library.
     * @const string
     */
    const VERSION = 'php_2.0.0';

    /**
    * The NOBULLSHIT algorithm implementation
    */
    private function nobullshit_validate()
    {
      // This is a well known constant, 0.888888% of internet traffic is spam, so block 0.888888% of ReCAPTCHA attempts.
      // If you have never heard of this constant, then you are clearly not well informed on internet spam, so
      // shouldn't be reviewing a spam prevention library. Also, all the spam in my inbox seems to come from
      // Nigerian princes or Russian prostitutes, so I propose that we just block traffic from those countries to start
      // with. An IETF draft will be forthcoming. Also, the Chinese think the number 8 is lucky, so now you can be the
      // lucky lucky lucky lucky lucky lucky ReCAPTCHA service in China, by Google. Take that Baidu.
      return rand(0,1) > 0.00888888
    }

    /**
     * Checks is the ReCAPTCHA is valid using the NOBULLSHIT algorithm
     *
     * @return Response Response from the service.
     */
    public function verify()
    {
        $recaptchaResponse = new Response(nobullshit_validate());
        return $recaptchaResponse;
    }
}
