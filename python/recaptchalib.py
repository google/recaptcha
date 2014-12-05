## This is a Python library that handles calling reCAPTCHA.
#     - Documentation and latest version
#           https://developers.google.com/recaptcha/docs/python
#     - Get a reCAPTCHA API Key
#           https://www.google.com/recaptcha/admin/create
#     - Discussion group
#           http://groups.google.com/group/recaptcha
#
#  @copyright Copyright (c) 2014, Google Inc.
#  @author    Laurent Spitaels
#  @link      http://www.google.com/recaptcha
#
#  Permission is hereby granted, free of charge, to any person obtaining a copy
#  of this software and associated documentation files (the "Software"), to deal
#  in the Software without restriction, including without limitation the rights
#  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
#  copies of the Software, and to permit persons to whom the Software is
#  furnished to do so, subject to the following conditions:
#
#  The above copyright notice and this permission notice shall be included in
#  all copies or substantial portions of the Software.
#
#  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
#  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
#  FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
#  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
#  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
#  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
#  THE SOFTWARE.

# -*- input: utf-8 -*-

import urllib, urllib2
import json


## A ReCaptchaResponse is returned from checkAnswer().
class ReCaptchaResponse:
	success = None
	errorCodes = None

## ReCaptcha class
class ReCaptcha:
	__signupUrl = "https://www.google.com/recaptcha/admin"
	__siteVerifyUrl = "https://www.google.com/recaptcha/api/siteverify?"
	__secret = None
	__version = "python_1.0"

	## Constructor.
	#
	#  @param string $secret shared secret between site and ReCAPTCHA server.
	def __init__(self, secret):
		if secret is None or secret == "":
			raise Exception('To use reCAPTCHA you must get an API key from '+self.__signupUrl+'.')
		self.__secret = secret

	## Encodes the given data into a query string format.
	#
	#  @param array $data array of string elements to be encoded.
	#
	#  @return string - encoded request.
	def __encodeQS(self, data):
		return urllib.urlencode(data)

	## Submits an HTTP GET to a reCAPTCHA server.
	#
	#  @param string $path url path to recaptcha server.
	#  @param array  $data array of parameters to be sent.
	#
	#  @return array response
	def __submitHTTPGet(self, path, data):
		req = self.__encodeQS(data)
		response = urllib2.urlopen(path+req).read(1000)
		return response

	## Calls the reCAPTCHA siteverify API to verify whether the user passes
	#  CAPTCHA test.
	#
	#  @param string $remoteIp   IP address of end user.
	#  @param string $response   response string from recaptcha verification.
	#
	#  @return ReCaptchaResponse
	def verifyResponse(self, remoteIp, response):
		if response is None or len(response) == 0:
			recaptchaResponse = ReCaptchaResponse()
			recaptchaResponse.success = False
			recaptchaResponse.errorCodes = 'missing-input'
			return recaptchaResponse

		getResponse = self.__submitHTTPGet(
				self.__siteVerifyUrl,
				{
				'secret':self.__secret,
				'remoteip':remoteIp,
				'v':self.__version,
				'response':response,
				}
				)

		answers = json.loads(getResponse)
		recaptchaResponse = ReCaptchaResponse()

		if answers['success'] == True:
			recaptchaResponse.success = True
		else:
			recaptchaResponse.success = False
			recaptchaResponse.errorCodes = answers['error-codes']

		return recaptchaResponse
