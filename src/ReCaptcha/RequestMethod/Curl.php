<?php

namespace ReCaptcha\RequestMethod;

use ReCaptcha\RequestMethod;
use ReCaptcha\RequestParameters;

/**
 * Sends cURL request to the reCAPTCHA service.
 */
class Curl implements RequestMethod
{
    /**
     * URL to which requests are sent via cURL.
     * @const string
     */
    const SITE_VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    /**
     * @var bool
     */
    private $forceIpv4 = false;

    /**
     * @param array|\Traversable $options
     */
    public function __construct($options = array())
    {
        if (!$this->hasCurl()) {
            throw new \RuntimeException('The cURL library must be installed');
        }

        if (!is_array($options) && !$options instanceof \Traversable) {
            throw new \InvalidArgumentException(
                sprintf(
                    '%s expects an array or Traversable argument; received "%s"',
                    __METHOD__,
                    (is_object($options) ? get_class($options) : gettype($options))
                )
            );
        }

        $this->setOptions($options);
    }

    /**
     * Submit the cURL request with the specified parameters.
     *
     * @param RequestParameters $params Request parameters
     * @return string Body of the reCAPTCHA response
     */
    public function submit(RequestParameters $params)
    {
        $handle = curl_init(self::SITE_VERIFY_URL);

        $options = array(
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $params->toQueryString(),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/x-www-form-urlencoded'
            ),
            CURLINFO_HEADER_OUT => false,
            CURLOPT_HEADER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true
        );

        if ($this->getForceIpv4()) {
            $options[CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4;
        }

        curl_setopt_array($handle, $options);

        $response = curl_exec($handle);
        curl_close($handle);

        return $response;
    }

    /**
     * @return bool
     */
    public function getForceIpv4()
    {
        return $this->forceIpv4;
    }

    /**
     * @param bool $forceIpv4
     * @throws \RuntimeException
     */
    public function setForceIpv4($forceIpv4)
    {
        if ($forceIpv4 === true && !$this->hasCurloptIpresolve()) {
            throw new \RuntimeException('The current cURL version does not support the "CURLOPT_IPRESOLVE" option.');
        }
        $this->forceIpv4 = $forceIpv4;
    }

    /**
     * @param array|\Traversable $options
     */
    private function setOptions($options)
    {
        foreach ($options as $key => $value) {
            $method = 'set' . str_replace('_', '', strtolower($key));
            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }
    }

    /**
     * Check if the cURL library is available
     *
     * @return bool
     */
    private function hasCurl()
    {
        return (function_exists('curl_init')) ? true : false;
    }

    /**
     * Check if the current cURL version is high enough
     * to support the "CURLOPT_IPRESOLVE" option
     *
     * @return bool
     */
    private function hasCurloptIpresolve()
    {
        $version = curl_version();
        return version_compare($version['version'], '7.10.8', '>=');
    }
}
