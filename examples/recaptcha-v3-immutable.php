<?php

declare(strict_types=1);

/**
 * BSD 3-Clause License.
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

// Register API keys at https://www.google.com/recaptcha/admin
$siteKey = (string) getenv('RECAPTCHA_V3_SITE');
$secret = (string) getenv('RECAPTCHA_V3_SECRET');

// Copy the config.php.dist file to config.php and update it with your keys to run the examples
if (('' === $siteKey || '' === $secret) && is_readable(__DIR__.'/config.php')) {
    /** @var array{v3: array{site: string, secret: string}} $config */
    $config = include __DIR__.'/config.php';
    $siteKey = $config['v3']['site'];
    $secret = $config['v3']['secret'];
}

$standardAction = 'examples/v3immutable';
$strictAction = 'examples/v3strict';

?>
<!DOCTYPE html>
<html lang="en">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,height=device-height,minimum-scale=1">
<link rel="shortcut icon" href="https://www.gstatic.com/recaptcha/admin/favicon.ico" type="image/x-icon"/>
<link rel="canonical" href="https://recaptcha-demo.appspot.com/recaptcha-v3-immutable.php">
<script type="application/ld+json">{ "@context": "https://schema.org", "@type": "WebSite", "name": "reCAPTCHA demo - Immutable with*() builders", "url": "https://recaptcha-demo.appspot.com/recaptcha-v3-immutable.php" }</script>
<meta name="description" content="reCAPTCHA demo - Immutable with*() builders" />
<meta property="og:url" content="https://recaptcha-demo.appspot.com/recaptcha-v3-immutable.php" />
<meta property="og:type" content="website" />
<meta property="og:title" content="reCAPTCHA demo - Immutable with*() builders" />
<meta property="og:description" content="reCAPTCHA demo - Immutable with*() builders" />
<link rel="stylesheet" type="text/css" href="/examples.css">
<title>reCAPTCHA demo - Immutable with*() builders</title>
<header>
    <h1>reCAPTCHA demo</h1><h2>Immutable <kbd>with*()</kbd> builders</h2>
    <p><a href="/"><span aria-hidden="true">↩️</span> Home</a></p>
</header>
<main>
<?php
if ('' === $siteKey || '' === $secret) {
    ?>
    <h2>Add your keys</h2>
    <p>If you do not have keys already then visit <kbd><a href="https://www.google.com/recaptcha/admin">https://www.google.com/recaptcha/admin</a></kbd> to generate them. Edit this file and set the respective keys in <kbd>$siteKey</kbd> and <kbd>$secret</kbd>. Reload the page after this.</p>
    <?php
} else {
    ?>
    <p>The <kbd>withExpectedHostname()</kbd>, <kbd>withExpectedAction()</kbd>, <kbd>withScoreThreshold()</kbd>, and <kbd>withChallengeTimeout()</kbd> methods clone the <kbd>ReCaptcha</kbd> instance instead of mutating it in place.</p>
    <p>This allows a single base <kbd>ReCaptcha</kbd> service in a dependency injection container or persistent worker runtime to safely derive multiple route-specific verifiers without leaking state:</p>
    <pre>$baseRecaptcha = (new ReCaptcha($secret))
    -&gt;withExpectedHostname($_SERVER['SERVER_NAME'])
    -&gt;withChallengeTimeout(120);

$strictVerifier   = $baseRecaptcha-&gt;withExpectedAction('examples/v3strict')-&gt;withScoreThreshold(0.99);
$standardVerifier = $baseRecaptcha-&gt;withExpectedAction('examples/v3immutable')-&gt;withScoreThreshold(0.5);</pre>
    <ol id="recaptcha-steps">
        <li class="step0">reCAPTCHA script loading</li>
        <li class="step1 hidden"><kbd>grecaptcha.ready()</kbd> fired, requesting tokens for both <kbd><?php echo htmlspecialchars($strictAction, ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?></kbd> and <kbd><?php echo htmlspecialchars($standardAction, ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?></kbd></li>
        <li class="step2 hidden">Strict verifier (<kbd>threshold: 0.99</kbd>, <kbd>action: <?php echo htmlspecialchars($strictAction, ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?></kbd>) response:
        <pre class="strict-response">Loading...</pre></li>
        <li class="step3 hidden">Standard verifier (<kbd>threshold: 0.5</kbd>, <kbd>action: <?php echo htmlspecialchars($standardAction, ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?></kbd>, derived from the same <kbd>$baseRecaptcha</kbd> instance) response:
        <pre class="standard-response">Loading...</pre></li>
    </ol>
    <p><a href="/recaptcha-v3-immutable.php"><span aria-hidden="true">⤴️</span> Try again</a></p>
    <script src="https://www.google.com/recaptcha/api.js?render=<?php echo htmlspecialchars(rawurlencode($siteKey), ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?>"></script>
    <script>
        grecaptcha.ready(function() {
            const siteKey = <?php echo json_encode($siteKey, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_THROW_ON_ERROR); ?>;
            const strictAction = <?php echo json_encode($strictAction, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_THROW_ON_ERROR); ?>;
            const standardAction = <?php echo json_encode($standardAction, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_THROW_ON_ERROR); ?>;

            document.querySelector('.step1').classList.remove('hidden');

            grecaptcha.execute(siteKey, {action: strictAction}).then(function(strictToken) {
                return fetch('/recaptcha-v3-verify.php?action=' + encodeURIComponent(strictAction) + '&token=' + encodeURIComponent(strictToken));
            }).then(function(response) {
                return response.json();
            }).then(function(strictData) {
                document.querySelector('.strict-response').textContent = JSON.stringify(strictData, null, 2);
                document.querySelector('.step2').classList.remove('hidden');

                return grecaptcha.execute(siteKey, {action: standardAction});
            }).then(function(standardToken) {
                return fetch('/recaptcha-v3-verify.php?action=' + encodeURIComponent(standardAction) + '&token=' + encodeURIComponent(standardToken));
            }).then(function(response) {
                return response.json();
            }).then(function(standardData) {
                document.querySelector('.standard-response').textContent = JSON.stringify(standardData, null, 2);
                document.querySelector('.step3').classList.remove('hidden');
            });
        });
    </script>
    <?php
}?>
</main>
