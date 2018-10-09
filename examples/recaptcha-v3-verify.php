<?php
/**
 * @copyright Copyright (c) 2015, Google Inc.
 * @link      https://www.google.com/recaptcha
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
require __DIR__ . '/appengine-https.php';

// Initiate the autoloader. The file should be generated by Composer.
// You will provide your own autoloader or require the files directly if you did
// not install via Composer.
require_once __DIR__ . '/../vendor/autoload.php';

// Register API keys at https://www.google.com/recaptcha/admin
$siteKey = '';
$secret = '';

// Copy the config.php.dist file to config.php and update it with your keys to run the examples
if ($siteKey == '' && is_readable(__DIR__ . '/config.php')) {
    $config = include __DIR__ . '/config.php';
    if(is_array($config)) {
      $siteKey = $config['v3']['site'];
      $secret = $config['v3']['secret'];
    }
}

// Effectively we're providing an API endpoint here that will accept the token, verify it, and return the action / score to the page
$recaptcha = new \ReCaptcha\ReCaptcha($secret);
$resp = $recaptcha->setExpectedHostname($_SERVER['SERVER_NAME'])
                  ->setExpectedAction('homepage')
                  ->setScoreThreshold(0.5)
                  ->verify($_GET['token'], $_SERVER['REMOTE_ADDR']);
header('Content-type:application/json');
echo json_encode($resp->toArray());
