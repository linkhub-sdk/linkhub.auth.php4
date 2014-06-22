<?php
/**
* =====================================================================================
* Class for develop interoperation with Linkhub APIs.
* Functionalities are authentication for Linkhub api products, and to support
* several base infomation(ex. Remain point).
*
* This module uses curl and openssl for HTTPS Request. So related modules must
* be installed and enabled.
*
* http://www.linkhub.co.kr
* Author : Kim Seongjun (pallet027@gmail.com)
* Written : 2014-06-23
*
* Thanks for your interest.
* We welcome any suggestions, feedbacks, blames or anythings.
* ======================================================================================
*/
require_once 'JSON.php';

class Linkhub 
{
	var $json;

	function Linkhub($LinkID,$SecretKey) {
		$this->json = new Services_JSON();

		$this->ServiceURL = 'https://auth.linkhub.co.kr';
		$this->VERSION = '1.0';
		$this->__LinkID = $LinkID;
		$this->__SecretKey = $SecretKey;
	}


	function hmac ( $text, $key)
	{
		$SHA1_BLOCKSIZE = 64;

		$key = str_pad($key, $SHA1_BLOCKSIZE, chr(0x00));
		$ipad = str_repeat(chr(0x36), $SHA1_BLOCKSIZE);
		$opad = str_repeat(chr(0x5c), $SHA1_BLOCKSIZE);
		$hash1 = $this->LH_SHA1(($key ^ $ipad) . $text, true);
		$hmac = $this->LH_SHA1(($key ^ $opad) . $hash1, true);
		return $hmac;
	}	
	function LH_MD5($text) {
		$hex = md5($text);
		$raw = '';
		for ($i = 0; $i < 32; $i += 2) {
			$hexcode = substr($hex, $i, 2);
			$charcode = (int)base_convert($hexcode, 16, 10);
			$raw .= chr($charcode);
			}
		return $raw;
	}
	function LH_SHA1($text) {
		$hex = sha1($text);
		$raw = '';
		for ($i = 0; $i < 40; $i += 2) {
			$hexcode = substr($hex, $i, 2);
			$charcode = (int)base_convert($hexcode, 16, 10);
			$raw .= chr($charcode);
		}
		return $raw;
	}

	function executeCURL($url,$header = array(),$isPost = false, $postdata = null) {
		$http = curl_init($url);
		
		if($isPost) {
			curl_setopt($http, CURLOPT_POST,1);
			curl_setopt($http, CURLOPT_POSTFIELDS, $postdata);   
		}
		curl_setopt($http, CURLOPT_HTTPHEADER,$header);
		curl_setopt($http, CURLOPT_RETURNTRANSFER, TRUE);
	        
		// SSL 인증서 검증은 필수 입니다.
		// http://curl.haxx.se/docs/caextract.html 에서 루트인증서를 업데이트 하십시오.
		// ex. /usr/share/ssl/certs/ca-bundle.crt
		//curl_setopt ($http, CURLOPT_SSL_VERIFYHOST, 0);
		//curl_setopt ($http, CURLOPT_SSL_VERIFYPEER, 0); 

		$responseJson = curl_exec($http);
		
		$http_status = curl_getinfo($http, CURLINFO_HTTP_CODE);
		
		if($http_status == 0) {
			echo curl_error($http);
		}
		curl_close($http);
		
		if($http_status != 200) {
			return new LinkhubException($responseJson);
		}
		
		return $this->json->decode($responseJson);
	}
	
	function getToken($ServiceID, $access_id, $scope = array() , $forwardIP = null)
	{
		//TODO UTC Check.
		//date_default_timezone_set("UTC");
		$xDate = date("Y-m-d\TH:i:s\Z", time()); 
		
		$uri = '/' . $ServiceID . '/Token';
		$header = array();
		
		$TokenRequest = new TokenRequest();
		$TokenRequest->access_id = $access_id;
		$TokenRequest->scope = $scope;
	
		$postdata = $this->json->encode($TokenRequest);
	
		$digestTarget = 'POST'.chr(10);
		$digestTarget = $digestTarget.base64_encode($this->LH_MD5($postdata)).chr(10);
		$digestTarget = $digestTarget.$xDate.chr(10);
		if(!(is_null($forwardIP) || $forwardIP == '')) {
			$digestTarget = $digestTarget.$forwardIP.chr(10);
		}
		$digestTarget = $digestTarget.$this->VERSION.chr(10);
		$digestTarget = $digestTarget.$uri;
	
		$digest = base64_encode($this->hmac($digestTarget,base64_decode(strtr($this->__SecretKey, '-_', '+/'))));

		$header[] = 'x-lh-date: '.$xDate;
		$header[] = 'x-lh-version: '.$this->VERSION;
		if(!(is_null($forwardIP) || $forwardIP == '')) {
			$header[] = 'x-lh-forwarded: '.$forwardIP;
		}
		
		$header[] = 'Authorization: LINKHUB '.$this->__LinkID.' '.$digest;
		$header[] = 'Content-Type: Application/json';
		
		return $this->executeCURL($this->ServiceURL.$uri , $header,true,$postdata);
		
	}
	
	
	function getBalance($bearerToken, $ServiceID)
	{
		$header = array();
		$header[] = 'Authorization: Bearer '.$bearerToken;
		
		$uri = '/'.$ServiceID.'/Point';
		
		$response = $this->executeCURL($this->ServiceURL . $uri,$header);
		return $response->remainPoint;
		
	}
	
	function getPartnerBalance($bearerToken, $ServiceID)
	{
		$header = array();
		$header[] = 'Authorization: Bearer '.$bearerToken;
		
		$uri = '/'.$ServiceID.'/PartnerPoint';
		
		$response = $this->executeCURL($this->ServiceURL . $uri,$header);
		return $response->remainPoint;
		
	}
	
	function json_encode($obj) {
		return $this->json->encode($obj);
	}
	function json_decode($text) {
		return $this->json->decode($text);
	}
}

class TokenRequest
{
	var $access_id;
	var $scope;
}

class LinkhubException
{
	var $code;
	var $message;

	function LinkhubException($responseJson) {
		$json = new Services_JSON();
		$result = $json->decode($responseJson);
		$this->code = $result->code;
		$this->message = $result->message;
		$this->isException = true;
		return $this;
	}
	function __toString() {
       	return "[code : {$this->code}] : {$this->message}\n";
    }

}

?>
