<?php
final class DefaultTwilio{
	public $errors = array();
	private $registry;
	private $config;
	private $sender;
	public function __construct(){
		$this->registry = Registry::getInstance();
		$this->registry->get('language')->load('default_twilio/default_twilio');
		$this->config = $this->registry->get('config');
		try{
			include_once('Services/Twilio.php');
		    $AccountSid = $this->config->get('default_twilio_username');
		    $AuthToken = $this->config->get('default_twilio_token');

			$this->sender = new Services_Twilio($AccountSid, $AuthToken);

		}catch(Exception $e){
			if($this->config->get('default_twilio_logging')){
				$this->registry->get('log')->write('Twilio error: '.$e->getMessage().'. Error Code:'.$e->getCode());
			}
		}
	}

	public function getProtocol(){
		return 'sms';
	}

	public function getProtocolTitle(){
		return $this->registry->get('language')->get('default_twilio_protocol_title') ;
	}

	public function getName(){
		return 'Twilio';
	}

	public function send($to, $text){
		if(!$to || !$text){
			return null;
		}
		$to = '+'.ltrim($to,'+');
		try{
			if($this->config->get('default_twilio_test')){
				//sandbox number without errors from api
				$from = '+15005550006';
			}else{
				$from = $this->config->get('default_twilio_sender_phone');
				$from = '+'.ltrim($from,'+');
			}
			$result = $this->sender->account->messages->sendMessage($from,$to,$text);
		}catch(Exception $e){
			if($this->config->get('default_twilio_logging')){
				$this->registry->get('log')->write('Twilio error: '.$e->getMessage().'. Error Code:'.$e->getCode());
			}
		}

		return true;
	}

	public function sendFew($to, $text){
		foreach($to as $uri){
			$this->send($uri, $text);
		}
	}

	public function validateURI($uri){
		$this->errors = array();
		$uri = trim($uri);
		$uri = trim($uri,',');

		$uris = explode(',',$uri);
		foreach($uris as $u){
			$u = trim($u);
			if(!$u){
				continue;
			}
			$u = preg_replace('/[^0-9\+]/','',$u);
			if($u[0]!='+'){
				$u = '+'.$u;
			}
			if(!preg_match('/^\+[1-9]{1}[0-9]{3,14}$/',$u) ){
				$this->errors[] = 'Mobile number '.$u.' is not valid!';
			}
		}

		if($this->errors){
			return false;
		}else{
			return true;
		}
	}

	/**
	 * Function builds form element for storefront side (customer account page)
	 *
	 * @param AForm $form
	 * @param string $value
	 * @return object
	 */
	public function getURIField($form, $value=''){
		$this->registry->get('language')->load('default_twilio/default_twilio');
		return $form->getFieldHtml(
										array(
		                                        'type' => 'phone',
		                                        'name' => 'sms',
		                                        'value' => $value,
												'label_text' => $this->registry->get('language')->get('entry_sms')
										));
	}
}