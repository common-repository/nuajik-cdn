<?php

/**
 * nuajik API wrapper class
 *
 * @version 0.1
 *
 * @author Arnaud Roux <arnaud_nuajik@axdev.io>
 * @link https://axdev.io Arnaud Roux
 */

class NuajikApi {

    public static $nuajik_api_url = "https://admin.nuajik.io/api/v2";
    protected $api_object = NULL;
    public static $api_key;

    function __construct($api_key) {
        NuajikApi::$api_key = $api_key;
    }

    protected function request($method, $ressource){
        $method_string = strtolower($method);
        $result = $this->get_rest_client()->$method_string($ressource);
        switch ($result->info->http_code) {
            case 200:
                 return $result->decode_response();
            case 401:
                throw new Exception('Unauthorized: your API key seems to be invalid or your account have been suspended</br>Please contact our support');
                break;
            case 404:
                throw new Exception('Not found: this ressource didn\'t exist on this server');
                break;
            case 500:
                throw new Exception('nuajik server error: we encountered an error, contact administrator if error persist');
                break;
        }
    }

    protected function get_rest_client() {
        if (empty($this->api_object)){
            $auth_array = [
                'base_url' => NuajikApi::$nuajik_api_url, 
                'headers' => ['Authorization' => 'Token '. NuajikApi::$api_key] 
            ];
            $rest_client = new RestClient($auth_array);
            $this->api_object =$rest_client;
        }
        return $this->api_object;
        
    }

    function get_slice_list() {  
        return $this->request('GET', '/slice');
    }

    function slice_purge($slice_pk) {  
        return $this->request('GET', '/slice/'.$slice_pk.'/purge');
    }
}
