<?php

/**
 * reCAPTCHA Enterprise API verify example.
 */

require_once dirname(__DIR__).'/src/autoload.php';

use ReCaptcha\ReCaptchaEnterprise;

// Register API keys at https://cloud.google.com/recaptcha-enterprise/docs
$siteKey = '';
$projectId = '';
$apiKey = '';

// In production, always sanitize and validate the input you retrieve from the request.
$recaptcha = new ReCaptchaEnterprise($projectId, $apiKey, $siteKey);
$resp = $recaptcha->setExpectedHostname($_SERVER['SERVER_NAME'])
    ->setExpectedAction($_GET['action'] ?? 'homepage')
    ->setScoreThreshold(0.5)
    ->verify($_GET['token'] ?? '')
;

header('Content-type:application/json');
echo json_encode($resp->toArray());
