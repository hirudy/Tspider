<?php
/**
 * User: rudy
 * Date: 2016/02/29 18:23
 *
 *  请求类
 *
 */

namespace framework\base;

use framework\TSpider;

class Request{
    public static $maxRepeat;
    public static $timeOut;

    public $url;
    public $workerName;
    public $type;
    public $header = array();
    public $postData = array();
    public $options = array();
    public $extData = array(); //额外捎带的数据

    protected $repeat = 1;

    /**
     * Request constructor.
     * @param $url
     * @param string $workerName
     * @param array $postData
     * @param array $extData
     */
    public function __construct($url,$workerName='framework\base\BaseWorker',$postData=array(),$extData=array()){
        $this->url = $url;
        $this->workerName = $workerName;

        $this->options[CURLOPT_URL] = $url;
        $this->options[CURLOPT_TIMEOUT] = self::$timeOut;
        $this->options[CURLOPT_USERAGENT] = 'Mozilla/5.0 (Windows NT 6.2; WOW64; Trident/7.0; rv:11.0) like Gecko';
        $this->options[CURLOPT_ENCODING] = 'gzip, deflate ';
        $this->extData = $extData;
        if(empty($postData) || !is_array($postData)){
            $this->type = 'GET';
        }else{
            $this->type = 'POST';
            $this->postData = $postData;
            $this->options[CURLOPT_POST] = true;
            $this->options[CURLOPT_POSTFIELDS] = $postData;
        }
    }

    /**
     * 对象字符串化
     * @return string
     */
    public function __toString(){
        $json_arr = array(
            'url' => $this->url,
            'workerName' => $this->workerName,
            'options' => $this->options,
            'postData' => $this->postData,
            'repeat' => $this->repeat,
            'header' => $this->header,
        );
        $returnString = json_encode($json_arr);
        if(!is_string($returnString)){
            $returnString = '';
        }
        return $returnString;
    }

    /**
     * 设置头部信息数组
     * @param $options
     */
    public function setOptions($options){
        foreach($options as $key=>$value){
            $this->options[$key] = $value;
        }
    }

    /**
     * 累加重复次数
     */
    public function addRepeat(){
        $this->repeat ++;
    }

    /**
     * 获取重复次数
     * @return int
     */
    public function getRepeatNum(){
        return $this->repeat;
    }

    /**
     * 是否能重复下载
     * @return bool
     */
    public function canRepeat(){
        if(self::$maxRepeat < $this->repeat){
            return false;
        }
        return true;
    }

    /**
     * 重新下载
     * @return bool true-添加成功,false-添加失败
     */
    public function redownload(){
        $this->addRepeat();
        if($this->canRepeat()) {
            TSpider::$requestQueue->add($this);
            return true;
        }
        return false;
    }


    /**
     * 将请求放入下载队列中，队列满了，返回失败
     * @return bool
     */
    public function download(){
        if(TSpider::$requestQueue->isFull()){
            return false;
        }
        TSpider::$requestQueue->add($this);
        return true;
    }


    public function createCurlObject(){
        $ch = curl_init();
        if($this->canRepeat()){
            if(!empty($this->options)){
                curl_setopt_array($ch,$this->options);
            }
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        }else{
            $ch = false;
        }
        return $ch;
    }
}