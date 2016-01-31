<head>
<script src='https://www.google.com/recaptcha/api.js'></script>
</head>
<style>
.error {
  color:red;
}
</style>
<?php
// define variables and set to empty values
$nameErr = $emailErr = $emailconfErr = $captchaErr = "";
$name = $email = $message = $emailconf = "";
/* set Site and Secret Key we got from the ReCaptcha API - they only appear in the php-source not th html output so it's OK. If you just want to
use this for testing from https://developers.google.com/recaptcha/docs/faq?hl=en - if they don't change them:
Site key: 6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI
Secret key: 6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe
Just copy them to the variable-definitions below
*/
$secret = "your-secret-key";
$sitekey = "your-public-key";

//Creating a function to strip and sanitize all inputs
function test_input($data) {
   $data = trim($data);
   $data = stripslashes($data);
   $data = htmlspecialchars($data);
   return $data;
}

//Now reCaptcha - server side. Has to be on this page, since we post the form to this page.


$postdata = http_build_query(
    array(
        'secret' => $secret,
        'response' => $_POST["g-recaptcha-response"]
    )
);

$opts = array('http' =>
    array(
        'method'  => 'POST',
        'header'  => 'Content-type: application/x-www-form-urlencoded',
        'content' => $postdata
    )
);

$context = stream_context_create($opts);

$result = file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context);
/* So we packed the g-recaptcha-response and sent it with our secret key to the reCaptcha server
 and in $result we got back a string that happens to be a JSON object which we will need to decoded_result
*/
$decoded_result = json_decode($result, true);
$captcha_response = $decoded_result["success"];
//Due to json_decode (true is necessary to be an associative array) the $captcha_response will be either 1 or empty so we can decide to process the for if there is no error

if ($_SERVER["REQUEST_METHOD"] == "POST" and $captcha_response != 1) {
     $captchaErr = "Try again!";
   }


if ($_SERVER["REQUEST_METHOD"] == "POST") {
   if (empty($_POST["name"])) {
     $nameErr = "Please enter your name!";
   } else {
     $name = test_input($_POST["name"]);
     // check if name only contains letters and whitespace
     if (!preg_match("/^[a-zA-Z ]*$/",$name)) {
       $nameErr = "Only letters and white space allowed in name!";
     }
   }

   if (empty($_POST["email"])) {
     $emailErr = "Please enter your email!";
   } else {
     $email = test_input($_POST["email"]);
     // check if e-mail address is well-formed
     if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
       $emailErr = "Please check your email - invalid format!";
     }
   }

   if (empty($_POST["emailconf"])) {
     $emailconfErr = "Please re-enter your email!";
   } else {
     $emailconf = test_input($_POST["emailconf"]);
     // check if e-mails match?
     if ($emailconf != $email) {
       $emailconfErr = "E-mail doesn't match - please check!";
     }
   }

   if (empty($_POST["message"])) {
     $message = "";
   } else {
     $message = test_input($_POST["message"]);
   }

}

?>

<form method="post" action="<?php echo htmlspecialchars($_SERVER["REQUEST_URI"]);?>">
   Name: <input class="text" type="text" name="name" value="<?php echo $name;?>">
   <span class="error"><?php echo $nameErr;?></span>
   <br /><br />
   E-mail: <input class="text" type="text" name="email" value="<?php echo $email;?>">
   <span class="error"><?php echo $emailErr;?></span>
   <br /><br />
   Confirm E-mail: <input class="text" type="text" name="emailconf" value="<?php echo $emailconf;?>">
   <span class="error"><?php echo $emailconfErr;?></span>
   <br /><br />
   Message: <br /><textarea name="message" rows="5" cols="40"><?php echo $message;?></textarea>
   <br />
   <div class="g-recaptcha" data-sitekey="<?php echo $sitekey ?>"></div> <span class="error"><?php echo $captchaErr ?></span>
   <br />
   <input type="submit" name="submit" value="Submit">
</form>

<?php

if ($_SERVER["REQUEST_METHOD"] == "POST" and $nameErr == "" and $emailErr == "" and $emailconfErr == "" and $captchaErr == "") {
// the message
$msg = "\r\n\r\nNew message from yourwebsite.com" . "\r\n\r\n" . "From: " . $name . "\r\n\r\n" . "From Email: " . $email . "\r\n\r\n" . "Message:" . "\r\n" . $message;

// use wordwrap() if lines are longer than 70 characters
$msg = wordwrap($msg,70);

// send email
$to      = "you@yourwebsite.com";
$subject = "New enquiry " . $name;
$headers = "From: " . $email . "\r\n" .
    "Reply-To: " . $email . "\r\n" .
    'X-Mailer: PHP/' . phpversion();

mail($to, $subject, $msg, $headers);

//In this case - email successfully sent with all enquiry data, we are sending the user to a thank-you page (you will need to add your own) and stop the script.
header("Location:/thank_you.html");

exit; // Header set, script stopped
} elseif ($_SERVER["REQUEST_METHOD"] == "POST") {
  echo "<br /><span class=\"error\">Your message haven't been sent! Please correct the mistakes above!<span>";
}
?>
