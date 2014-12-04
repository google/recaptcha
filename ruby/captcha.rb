require 'net/http'
require 'addressable/uri'

class Captcha
	HTTP_URL = "https://www.google.com/recaptcha/admin"
	SITE_VERIFY_URL = "https://www.google.com/recaptcha/api/siteverify?"
	VERSION = 'ruby-0.1'
	
	# It is better to load configurations from YAML files
	CONFIG = {
		:secret => '',
		:site_key => ''
	}
	
	SSL_PEER_VERIFICATION = false #Could be something like Rails.env.production?
	
	def self.check?(response, ip = nil)
		return false if no_config? or response.strip.empty?
		
		
		result = request({
			:remote_ip => ip,
			:response => response.gsub('/','')
		})
		
		unless result['success']
			p "Google Recaptcha Verification failed."
			p result['error-codes']
			return false
		end
		
		true
	end
	
	def self.show(lang = nil, use_fallback = false)
		return if no_config?
		script_url = "https://www.google.com/recaptcha/api.js"
		script_url << "?lang=#{lang}" if lang
		output = %(
			<div class='g-recaptcha' data-sitekey='#{CONFIG[:site_key]}'></div>
			<script type="text/javascript" async defer
				src="#{script_url}">
			</script>
		)
		output << fallback if use_fallback
		output.respond_to?(:html_safe) ? output.html_safe : output
	end
	
	private
	
	def self.no_config?
		return false if CONFIG[:site_key].present? and CONFIG[:secret].present?
		
		warn "RECaptcha Site Key and Secret not configured. Register API keys at https://www.google.com/recaptcha/admin"
		true
	end
	
	def self.fallback
		%(
<noscript>
<div style="width: 302px; height: 352px;">
<div style="width: 302px; height: 352px; position: relative;">
<div style="width: 302px; height: 352px; position: absolute;">
<iframe src="https://www.google.com/recaptcha/api/fallback?k=#{your_site_key}"
frameborder="0" scrolling="no"
style="width: 302px; height:352px; border-style: none;">
</iframe>
</div>
<div style="width: 250px; height: 80px; position: absolute; border-style: none;
bottom: 21px; left: 25px; margin: 0px; padding: 0px; right: 25px;">
<textarea id="g-recaptcha-response" name="g-recaptcha-response"
class="g-recaptcha-response"
style="width: 250px; height: 80px; border: 1px solid #c1c1c1;
margin: 0px; padding: 0px; resize: none;" value="">
</textarea>
</div>
</div>
</div>
</noscript>
		)
	end
	
	def self.request( data)
		
		begin
			data.merge!({
				:secret => CONFIG[:secret],
				:v => VERSION
			})
			
			data.delete(:remote_ip) if data[:remote_ip] == '127.0.0.1'
			
			uri = URI(SITE_VERIFY_URL + parameterize(data))
			http = Net::HTTP.new(uri.host, uri.port)
		  http.use_ssl = true
		  http.verify_mode = SSL_PEER_VERIFICATION ? OpenSSL::SSL::VERIFY_PEER : OpenSSL::SSL::VERIFY_NONE
		  response = JSON.parse(http.request(request).body)
			
		rescue => e
			return {'error-code' => e.message }
		end
		
		response
	end
	
	def self.parameterize(data)
		uri = Addressable::URI.new
		uri.query_values = data
		uri.query
	end
end