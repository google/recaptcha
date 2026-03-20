<?php

/**
 * This is a PHP library that handles calling reCAPTCHA.
 *
 * BSD 3-Clause License
 *
 * @copyright (c) 2019, Google Inc.
 *
 * @see https://www.google.com/recaptcha
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
 * Parses the response returned from the Enterprise service into a classic Response object.
 */
class EnterpriseResponseParser
{
    /**
     * Build the response from the expected JSON returned by the Enterprise service.
     *
     * @param string $json
     *
     * @return Response
     */
    public static function fromJson($json)
    {
        $responseData = json_decode($json, true);

        if (!$responseData) {
            return new Response(false, [ReCaptcha::E_INVALID_JSON]);
        }

        $tokenProperties = $responseData['tokenProperties'] ?? [];
        $riskAnalysis = $responseData['riskAnalysis'] ?? [];

        $success = isset($tokenProperties['valid']) ? (bool) $tokenProperties['valid'] : false;

        $errorCodes = [];
        if (!$success && isset($tokenProperties['invalidReason'])) {
            $errorCodes[] = $tokenProperties['invalidReason'];
        }

        $hostname = $tokenProperties['hostname'] ?? '';
        $challengeTs = $tokenProperties['createTime'] ?? '';
        $apkPackageName = $tokenProperties['androidPackageName'] ?? ''; // Enterprise often uses androidPackageName but tokenProperties uses different keys if any
        $score = isset($riskAnalysis['score']) ? floatval($riskAnalysis['score']) : null;
        $action = $tokenProperties['action'] ?? '';

        return new Response($success, $errorCodes, $hostname, $challengeTs, $apkPackageName, $score, $action);
    }
}
