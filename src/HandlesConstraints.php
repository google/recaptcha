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

trait HandlesConstraints
{
    /**
     * The constraints to check after the response is received.
     *
     * @var array
     */
    protected $constraints = ReCaptcha::CONSTRAINTS_ARRAY;

    /**
     * Verify if the challenge hostname is equal to the one set.
     *
     * @param  string $hostname
     * @return \Google\ReCaptcha\ReCaptcha
     */
    public function hostname(string $hostname)
    {
        $this->constraints['hostname'] = $hostname;

        return $this;
    }

    /**
     * Verify if the challenge APK Package Name is equal to the one set.
     *
     * @param  string $apkPackageName
     * @return \Google\ReCaptcha\ReCaptcha
     */
    public function apkPackageName(string $apkPackageName)
    {
        $this->constraints['apk_package_name'] = $apkPackageName;

        return $this;
    }

    /**
     * Verify if the action name is equal to the one set.
     *
     * @param  string $action
     * @return \Google\ReCaptcha\ReCaptcha
     */
    public function action(string $action)
    {
        $this->constraints['action'] = $action;

        return $this;
    }

    /**
     * Sanitizes the Action and verifies if its equal to the one set.
     *
     * @param  string $action
     * @return \Google\ReCaptcha\ReCaptcha
     */
    public function saneAction(string $action)
    {
        return $this->action(preg_replace('/[^a-zA-Z0-9-]/', '', $action));
    }

    /**
     * Verify if the score threshold is above the one set.
     *
     * @param  float $threshold
     * @return \Google\ReCaptcha\ReCaptcha
     */
    public function threshold(float $threshold)
    {
        $this->constraints['threshold'] = $threshold;

        return $this;
    }

    /**
     * Verify if the challenge was send before the seconds set
     *
     * @param  int $seconds
     * @return \Google\ReCaptcha\ReCaptcha
     */
    public function challengeTs(int $seconds)
    {
        $this->constraints['challenge_ts'] = $seconds;

        return $this;
    }

    /**
     * Flushes all constraints
     *
     * @return \Google\ReCaptcha\ReCaptcha
     */
    public function flushConstraints()
    {
        $this->constraints = ReCaptcha::CONSTRAINTS_ARRAY;

        return $this;
    }
}
