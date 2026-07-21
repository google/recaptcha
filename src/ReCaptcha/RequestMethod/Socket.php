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

namespace ReCaptcha\RequestMethod;

/**
 * Convenience wrapper around native socket and file functions to allow for
 * mocking.
 */
class Socket
{
    /**
     * @var null|false|resource
     */
    private $handle;

    /**
     * fsockopen.
     *
     * @see http://php.net/fsockopen
     *
     * @param string $hostname
     * @param int    $port
     * @param int    $errno
     * @param string $errstr
     * @param float  $timeout
     *
     * @return false|resource
     */
    public function fsockopen($hostname, $port = -1, &$errno = 0, &$errstr = '', $timeout = null)
    {
        $resolvedTimeout = is_null($timeout) ? (float) ini_get('default_socket_timeout') : $timeout;
        $this->handle = fsockopen($hostname, $port, $errno, $errstr, $resolvedTimeout);

        if (false != $this->handle && 0 === $errno && '' === $errstr) {
            return $this->handle;
        }

        return false;
    }

    /**
     * fwrite.
     *
     * @see http://php.net/fwrite
     *
     * @param string $string
     * @param int    $length
     *
     * @return bool|int
     */
    public function fwrite($string, $length = null)
    {
        if (false === $this->handle || null === $this->handle) {
            return false;
        }

        $resolvedLength = is_null($length) ? strlen($string) : max(0, $length);

        return fwrite($this->handle, $string, $resolvedLength);
    }

    /**
     * fgets.
     *
     * @see http://php.net/fgets
     *
     * @param int $length
     *
     * @return string
     */
    public function fgets($length = null)
    {
        if (false === $this->handle || null === $this->handle) {
            return '';
        }

        $resolvedLength = is_null($length) ? null : max(0, $length);
        $line = fgets($this->handle, $resolvedLength);

        return false === $line ? '' : $line;
    }

    /**
     * feof.
     *
     * @see http://php.net/feof
     *
     * @return bool
     */
    public function feof()
    {
        if (false === $this->handle || null === $this->handle) {
            return true;
        }

        return feof($this->handle);
    }

    /**
     * fclose.
     *
     * @see http://php.net/fclose
     *
     * @return bool
     */
    public function fclose()
    {
        if (false === $this->handle || null === $this->handle) {
            return false;
        }

        return fclose($this->handle);
    }
}
