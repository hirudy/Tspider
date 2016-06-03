<?php

/**
 * User: rudy
 * Date: 2016/03/16 9:38
 *
 *  基础的Http相关服务
 *
 */

class Http {

    public static $lastError = array('error'=>'','errorList'=>array());

    public static function request($url,$postData=array(),$header=array()){
        $options = array();
        $url = trim($url);
        $options[CURLOPT_URL] = $url;
        $options[CURLOPT_TIMEOUT] = 10;
        $options[CURLOPT_USERAGENT] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/44.0.2403.89 Safari/537.36';
        $options[CURLOPT_RETURNTRANSFER] = true;
        foreach($header as $key=>$value){
            $options[$key] =$value;
        }
        if(!empty($postData) && is_array($postData)){
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = http_build_query($postData);
        }
        if(stripos($url,'https') === 0){
            $options[CURLOPT_SSL_VERIFYPEER] = false;
        }
        $ch = curl_init();
        curl_setopt_array($ch,$options);
        $rel = curl_exec($ch);
        if($rel == false){
            $errno = curl_errno( $ch );
            $error = curl_error($ch);
            self::$lastError['error'] = "({$errno})$error";
            self::$lastError['errorList'] = curl_getinfo($ch);
        }
        curl_close($ch);
        return $rel;
    }
}