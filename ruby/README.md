# Ruby ReCAPTCHA - How to use

To display the Captcha in your form, use:
> <%= Captcha::display %>

To force a language while displaying the captcha use:
> <%= Captcha::display(:en) %>

List of supported [languages]

To Verify the response submitted:
> Captcha::check?(params["g-recaptcha-response"], request.remote_ip)
  
Remember to set the Site Key and Secret in the CONFIG constant.



[languages]:https://developers.google.com/recaptcha/docs/language
