<?php

class rocketchat{
	private $endpointUrl;
	private $onErrorFunc; // variáveis de funções
	private $authUserId, $authToken; // variaveis de autenticacao
	function __construct($apiEndpointUrl, $rcUser = false, $rcPass = false){
		$this->endpointUrl = trim($apiEndpointUrl, '/');
		if($rcUser && $rcPass){
			return $this->auth($rcUser, $rcPass);
		}
		return true;
	}
	function auth($username, $password){
		$callUrl = $this->endpointUrl.'/login';
        if($retorno = $this->curlConnect($callUrl, null, Array('user' => $username, 'password' => $password))){
        	if(isset($retorno['data']['userId']) && isset($retorno['data']['authToken'])){
        		$this->authUserId = $retorno['data']['userId'];
				$this->authToken = $retorno['data']['authToken'];
				return true;
        	}
        }
		return false;
	}
	function get($funcName, $parameters = null){
		$callUrl = $this->endpointUrl.'/'.$funcName;
		$headers = ($this->authUserId && $this->authToken)? Array('X-Auth-Token: '.$this->authToken, 'X-User-Id: '.$this->authUserId) : null;
        if($retorno = $this->curlConnect($callUrl, $parameters, null, $headers)){
        	if(isset($retorno['success']) && isset($retorno[$funcName])){
        		return $retorno[$funcName];
        	}
        }
        return $retorno;
    }
	function post($funcName, $parameters = null){
		$callUrl = $this->endpointUrl.'/'.$funcName;
		$headers = ($this->authUserId && $this->authToken)? Array('X-Auth-Token: '.$this->authToken, 'X-User-Id: '.$this->authUserId) : null;
        if($retorno = $this->curlConnect($callUrl, null, $parameters, $headers)){
        	if(isset($retorno['success']) && isset($retorno[$funcName])){
        		return $retorno[$funcName];
        	}
        }
        return $retorno;
    }
	private function curlConnect($url, $get = null, $post = null, $headers = null, $timeout = 5){
		$headers[] = 'Content-type:application/json';
        if($get && !empty($get)){
            $url = trim($url, ' /') . '?' . http_build_query($get);
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        if($post){
            curl_setopt($ch, CURLOPT_POST, 1);
        }
        if (!empty($post)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));
        }
        if ($headers && !empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        $returnData = curl_exec($ch);
        curl_close($ch);
        if($jsonDecoded = @json_decode($returnData, true)){
            $this->analyseReturnErrors($jsonDecoded);
            return $jsonDecoded;
        }else{
            $this->throwError('invalid-return', $returnData);
        }
        return $returnData;
    }
	private function analyseReturnErrors($returnData){
        if(isset($returnData['status']) && $returnData['status'] == 'error'){
			$errorCode = (isset($returnData['error']))? $returnData['error'] : 0;
            $this->throwError($errorCode, $returnData['message']);
            return false;
        }
        return true;
    }
	private function throwError($code, $description){
        if($this->onErrorFunc && is_callable($this->onErrorFunc)){
            if(call_user_func($this->onErrorFunc, $code, $description)){
                return true;
            }
        }
        throw new Exception('Rocketchat API Error: ['.$code.'], '.$description);
        return true;
    }
    function onError($errorFunc){
        if(is_callable($errorFunc)){
            $this->onErrorFunc = $errorFunc;
            return true;
        }
        return false;
    }
}

