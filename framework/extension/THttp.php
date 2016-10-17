<?php
/**
 *
 * http请求工具集合
 *
 * @author: rudy
 * @date: 2016/09/19
 */

class THttp{
    const VERSION = '1.0.1';            // http组件版本号
    const MAX_REDIRECT_NUM = 5;         // 重定向最大次数,避免重定向死循环

    const DEFAULT_USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36';
    const DEFAULT_TIMEOUT = 10;         // http默认执行时间
    /**
     * 构造 curl 请求执行对象
     * @param string $url  请求url地址
     * @param array  $postData 请求post参数数组
     * @param array  $header 请求附带请求头部数组
     * @param int    $timeOut 超时时间
     * @param string $proxy 代理设置
     * @return resource
     */
    private static function buildCurlObject($url, $postData, $header, $timeOut, $proxy){
        // 构造url请求
        $options = array();
        $url = trim($url);
        $options[CURLOPT_URL] = $url;
        $options[CURLOPT_TIMEOUT] = (int)$timeOut;
        $options[CURLOPT_USERAGENT] = self::DEFAULT_USER_AGENT;
        $options[CURLOPT_RETURNTRANSFER] = true;
        $options[CURLOPT_HEADER] = true;

        // 配置代理
        if (!empty($proxy)){
            $options[CURLOPT_PROXY] = $proxy;
        }

        // 合并请求头部信息
        foreach($header as $key=>$value){
            $options[$key] =$value;
        }

        // 是否是post请求
        if(!empty($postData) && is_array($postData)){
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = http_build_query($postData);
        }

        // 是否是https
        if(stripos($url,'https') === 0){
            $options[CURLOPT_SSL_VERIFYPEER] = false;
        }

        // 返回curl对象
        $ch = curl_init();
        curl_setopt_array($ch,$options);
        return $ch;
    }

    /**
     * 将字符串解析为http响应头部数组
     * @param $strResponseHeader
     * @return array
     */
    private static function parseResponseHeader($strResponseHeader){
        $headerList = array();
        $tempHeaderList = explode("\r\n",$strResponseHeader);
        foreach ($tempHeaderList as $row){
            if (stripos($row,':') === false){
                $tmp = explode(" ",$row);
                $headerList['Protocol'] = isset($tmp[0])?$tmp[0]:'';
                $headerList['Status'] = (int)(isset($tmp[1])?$tmp[1]:0);
                $headerList['Message'] = isset($tmp[2])?$tmp[2]:'';
            }else{
                $tmp = explode(":",$row, 2);
                if (count($tmp) != 2){
                    continue;
                }
                $key = trim($tmp[0]);
                $value = trim($tmp[1]);
                if ($key == 'Set-Cookie'){
                    if (!isset($headerList[$key])){
                        $headerList[$key] = array();
                    }
                    $tmpCookieList = explode(";",$value);
                    foreach ($tmpCookieList as $oneCookie){
                        $tmpCookie = explode("=",$oneCookie,2);
                        if (count($tmpCookie) != 2){
                            continue;
                        }
                        $key_cookie = trim($tmpCookie[0]);
                        $value_cookie = trim($tmpCookie[1]);
                        $headerList[$key][$key_cookie] = $value_cookie;
                    }
                }else{
                    $headerList[$key] = $value;
                }
            }
        }

        return $headerList;
    }

    /**
     * 从curl对象中提取请求的结果
     * @param $ch
     * @param $rel
     * @return array
     */
    private static function fetchResponse($ch, $rel){
        $response = array(
            'status' => false,
            'code' => 0,
            'header' => array(),
            'body' => '',
            'extraInfo' => array(),
            'errorInfo' => array()
        );
        $response['extraInfo'] = curl_getinfo($ch);
        if($rel == false){
            $error = array();
            $error['code'] = curl_errno( $ch );
            $error['info'] = curl_error($ch);
            $response['errorInfo'] = $error;
        }else{
            // 切割header 与 body
            $header_body = explode("\r\n\r\n",$rel);
            do{
                if (count($header_body) !== 2){
                    $error =array();
                    $error['code'] = 0;
                    $error['info'] = 'split header and body error';
                    $error['list'] = $rel;
                    $response['errorInfo'] = $error;
                    break;
                }

                // 格式化返回结果
                $response['body'] = $header_body[1];
                $response['header'] = self::parseResponseHeader($header_body[0]);
                $response['code'] = $response['header']['Status'];
                $response['status'] = true;
            }while(false);
        }

        return $response;
    }

    /**
     * 通用请求方法
     * @param string $url 请求url地址
     * @param array  $postData 请求post参数数组
     * @param array  $header  请求附带请求头部数组
     * @param int    $timeOut 超时时间
     * @param string $proxy 代理设置
     * @return array
     */
    public static function request($url,$postData=array(),$header=array(),$timeOut=self::DEFAULT_TIMEOUT, $proxy=''){
        // 执行请求
        $ch = self::buildCurlObject($url, $postData, $header, $timeOut, $proxy);
        $rel = curl_exec($ch);
        $response = self::fetchResponse($ch, $rel);
        curl_close($ch);
        return $response;
    }

    /**
     * 简单返回请求方法
     * @param string $url 请求url地址
     * @param array  $postData 请求post参数数组
     * @param array  $header  请求附带请求头部数组
     * @param int    $timeOut 超时时间
     * @param string $proxy 代理设置
     * @return bool|mixed 成功返回body字符串,失败返回false
     */
    public static function simpleResponseRequest($url,$postData=array(), $header=array(), $timeOut=self::DEFAULT_TIMEOUT, $proxy=''){
        $result = self::request($url, $postData, $header, $timeOut, $proxy);
        if ($result['status']){
            return $result['body'];
        }else{
            print_r($result['extraInfo']);
            print_r($result['errorInfo']);
            return false;
        }
    }

    /**
     * 多个http请求并行执行
     * @param array $requestList 参数同self::request,只不过改成了数组
     * @return arrayMAX_REDIRECT_NUM = 5
     */
    public static function multiRequest(Array $requestList){
        // 创建curl对象,存放到数组,添加到下载器中
        $requestCurlObjectList = array();
        $downloader = curl_multi_init();
        foreach ($requestList as $row){

            $url = isset($row['url'])?$row['url']:'';
            $postData = isset($row['postData'])?$row['postData']:array();
            $header = isset($row['header'])?$row['header']:array();
            $timeOut = isset($row['timeOut'])?$row['timeOut']:self::DEFAULT_TIMEOUT;
            $proxy = isset($row['proxy'])?$row['proxy']:'';
            $tmpCurlObject = self::buildCurlObject($url,$postData,$header,$timeOut,$proxy);
            $requestCurlObjectList[] = $tmpCurlObject;
            curl_multi_add_handle($downloader,$tmpCurlObject);
        }

        // 并行执行多个curl对象,等待所有请求完毕退出循环
        $active = true;
        $mrc = CURLM_OK;
        while ($active && $mrc == CURLM_OK) {
            do {
                $mrc = curl_multi_exec($downloader, $active);
            } while ($mrc == CURLM_CALL_MULTI_PERFORM);

            if (curl_multi_select($downloader) == -1) {
                usleep(100);
            }
        }

        // 解析每一个请求对象
        $responseList = array();
        foreach ($requestCurlObjectList as $key=>$ch){
            $rel = curl_multi_getcontent($ch);
            $response = self::fetchResponse($ch, $rel);
            $responseList[$key] = $response;
            curl_multi_remove_handle($downloader, $ch);
            curl_close($ch);
        }
        curl_multi_close($downloader);
        return $responseList;
    }
}

//测试
if(strtolower(PHP_SAPI) == 'cli' && isset($argv) && basename(__FILE__) == basename($argv[0])){
    // 串行请求
    $start_time = microtime(true);
    $response1 = THttp::request('https://www.baidu.com/');
    $response2 = THttp::request('http://www.jd.com');
    $response3 = THttp::request('http://www.jianshu.com/');
    $response4 = THttp::request('http://www.zhihu.com/');
    $response5 = THttp::request('http://www.php.net/');
    $response6 = THttp::request('https://github.com/hirudy');
    $response7 = THttp::request('http://www.toutiao.com/');
    $response8 = THttp::request('http://www.mi.com/');
    //    $response9 = THttp::request('https://www.google.com');
    echo "serial request take time : ", microtime(true)-$start_time,"\n";

    // 并行请求
    $start_time = microtime(true);
    $responseList = THttp::multiRequest(array(
        array('url'=>'https://www.baidu.com/'),
        array('url'=>'http://www.jd.com'),
        array('url'=>'http://www.jianshu.com/'),
        array('url'=>'http://www.zhihu.com/'),
        array('url'=>'http://www.php.net/'),
        array('url'=>'https://github.com/hirudy'),
        array('url'=>'http://www.toutiao.com/'),
        array('url'=>'http://www.mi.com/'),
//        array('url'=>'https://www.google.com')
    ));
    echo "parallel requests take time : ", microtime(true)-$start_time,"\n";

}