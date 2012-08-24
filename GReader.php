<?php

/**
 * Google API stream reader. Performs ClientLogin authentication,
 * then requests any URL and pushes XML tokens stream to consumer.
 * The consumer is parser, that is fed by XML tokens by callbacks to
 * start(), end(), and data() methods.
 * Consumer should implement IConsumer interface.
 */

require_once 'IConsumer.php';

class GReader {

	protected $auth;
	protected $consumer;

	public function __construct($login, $password, IConsumer $consumer) {
		$this->consumer = $consumer;
		
		$clientlogin_url = "https://www.google.com/accounts/ClientLogin";
		$clientlogin_post = array(
		    "accountType" => "HOSTED_OR_GOOGLE",
		    "Email" => $login,
		    "Passwd" => $password,
		    "service" => "wise",
		    "source" => "my-fake-app"
		);

		// Initialize the curl object
		$curl = curl_init($clientlogin_url);

		// Set some options (some for SHTTP)
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $clientlogin_post);
		curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

		// Execute
		$response = curl_exec($curl);

		// Get the Auth string and save it
		preg_match("/Auth=([a-z0-9_-]+)/i", $response, $matches);
		$this->auth = $matches[1];

		curl_close($curl);
	}

	protected function createParser() {
		$sax = xml_parser_create();
		
		xml_parser_set_option($sax, XML_OPTION_CASE_FOLDING, false);
		xml_parser_set_option($sax, XML_OPTION_SKIP_WHITE, true);
		xml_set_element_handler($sax, array($this->consumer, 'start'), array($this->consumer, 'end'));
		xml_set_character_data_handler($sax, array($this->consumer, 'data'));
		return $sax;
	}
	public function request($url, $method, $params=array()) {
		$curl = curl_init($url);

		$ctx = stream_context_create(array(
			'gxml'=>array(
				'parser'=>$this->createParser()
		)));

		$fp = fopen("gxml://", "r+", false, $ctx);

		curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		// curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_FILE, $fp);
		curl_setopt($curl, CURLOPT_POST, $method==='post');

		$headers = array(
		    "Authorization: GoogleLogin auth=" . $this->auth,
		    "GData-Version: 3.0",
		);

		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

		curl_exec($curl);
		curl_close($curl);

		fclose($fp);
	}

}

/**
 * Stream wrapper, that emulates writeable stream and pushes
 * chunks of data to SAX xml parser,
 * specified in context['gxml']['parser']
 */
class GXMLStream {
    protected $buffer;
    public $context;

    function stream_open($path, $mode, $options, &$opened_path) {
        $ctx = $this->context;
        $options = stream_context_get_options($ctx);

        $this->parser = $options['gxml']['parser'];
        return true;
    }

    public function stream_write($data) {
        xml_parse($this->parser, $data);
        return strlen($data);
    }
}

stream_wrapper_register("gxml", "GXMLStream")
    or die("Failed to register protocol");
