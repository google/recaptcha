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

namespace Google\ReCaptcha;

trait ParsesResponse
{
    /**
     * Checks the ReCaptchaResponse for constraint errors.
     *
     * @param  \Google\ReCaptcha\ReCaptchaResponse $response
     * @return \Google\ReCaptcha\ReCaptchaResponse
     */
    protected function checkErrors(ReCaptchaResponse $response)
    {
        $errors = [];

        $constraints = $response->constraints();

        if ($constraints['hostname']
            && strcasecmp($constraints['hostname'], $response->hostname) !== 0) {
            $errors[] = ReCaptchaErrors::E_HOSTNAME_MISMATCH;
        }

        if ($constraints['apk_package_name']
            && strcasecmp($constraints['apk_package_name'], $response->apk_package_name) !== 0) {
            $errors[] = ReCaptchaErrors::E_APK_PACKAGE_NAME_MISMATCH;
        }

        if ($constraints['action']
            && strcasecmp($constraints['action'], $response->action) !== 0) {
            $errors[] = ReCaptchaErrors::E_ACTION_MISMATCH;
        }

        if ($constraints['threshold']
            && $constraints['threshold'] > $response->score) {
            $errors[] = ReCaptchaErrors::E_SCORE_THRESHOLD_NOT_MET;
        }

        if ($constraints['challenge_ts']) {
            $timeout = strtotime($response->challenge_ts);

            if ($timeout > 0 && time() - $timeout > $constraints['challenge_ts']) {
                $errors[] = ReCaptchaErrors::E_CHALLENGE_TIMEOUT;
            }
        }

        // Add the errors to the existing array errors of the response
        return $response->addErrors($errors);
    }
}
