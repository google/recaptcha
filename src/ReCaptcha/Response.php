<?php

declare(strict_types=1);

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
 * The response returned from the service.
 */
readonly class Response
{
    /**
     * Constructor.
     *
     * @param bool          $success        success or failure
     * @param array<string> $errorCodes     error code strings
     * @param string        $hostname       the hostname of the site where the reCAPTCHA was solved
     * @param string        $challengeTs    timestamp of the challenge load (ISO format yyyy-MM-dd'T'HH:mm:ssZZ)
     * @param string        $apkPackageName APK package name
     * @param ?float        $score          score assigned to the request
     * @param string        $action         action as specified by the page
     */
    public function __construct(
        private bool $success,
        private array $errorCodes = [],
        private string $hostname = '',
        private string $challengeTs = '',
        private string $apkPackageName = '',
        private ?float $score = null,
        private string $action = '',
    ) {}

    /**
     * Build the response from the expected JSON returned by the service.
     */
    public static function fromJson(string $json): Response
    {
        $responseData = json_decode($json, true);

        if (!is_array($responseData)) {
            return new Response(false, [ReCaptcha::E_INVALID_JSON]);
        }

        $hostname = isset($responseData['hostname']) && is_string($responseData['hostname']) ? $responseData['hostname'] : '';
        $challengeTs = isset($responseData['challenge_ts']) && is_string($responseData['challenge_ts']) ? $responseData['challenge_ts'] : '';
        $apkPackageName = isset($responseData['apk_package_name']) && is_string($responseData['apk_package_name']) ? $responseData['apk_package_name'] : '';
        $score = isset($responseData['score']) && is_numeric($responseData['score']) ? floatval($responseData['score']) : null;
        $action = isset($responseData['action']) && is_string($responseData['action']) ? $responseData['action'] : '';

        if (isset($responseData['success']) && true === $responseData['success']) {
            return new Response(true, [], $hostname, $challengeTs, $apkPackageName, $score, $action);
        }

        if (isset($responseData['error-codes']) && is_array($responseData['error-codes'])) {
            /** @var array<string> $errorCodes */
            $errorCodes = $responseData['error-codes'];

            return new Response(false, $errorCodes, $hostname, $challengeTs, $apkPackageName, $score, $action);
        }

        return new Response(false, [ReCaptcha::E_UNKNOWN_ERROR], $hostname, $challengeTs, $apkPackageName, $score, $action);
    }

    /**
     * Is success?
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Get error codes.
     *
     * @return array<string>
     */
    public function getErrorCodes(): array
    {
        return $this->errorCodes;
    }

    /**
     * Get hostname.
     */
    public function getHostname(): string
    {
        return $this->hostname;
    }

    /**
     * Get challenge timestamp.
     */
    public function getChallengeTs(): string
    {
        return $this->challengeTs;
    }

    /**
     * Get APK package name.
     */
    public function getApkPackageName(): string
    {
        return $this->apkPackageName;
    }

    /**
     * Get score.
     */
    public function getScore(): ?float
    {
        return $this->score;
    }

    /**
     * Get action.
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * Array representation.
     *
     * @return array{
     *     success: bool,
     *     hostname: string,
     *     challenge_ts: string,
     *     apk_package_name: string,
     *     score: null|float,
     *     action: string,
     *     error-codes: string[]
     * }
     */
    public function toArray(): array
    {
        return [
            'success' => $this->isSuccess(),
            'hostname' => $this->getHostname(),
            'challenge_ts' => $this->getChallengeTs(),
            'apk_package_name' => $this->getApkPackageName(),
            'score' => $this->getScore(),
            'action' => $this->getAction(),
            'error-codes' => $this->getErrorCodes(),
        ];
    }
}
