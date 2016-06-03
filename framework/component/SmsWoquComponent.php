<?php
/**
 *
 * sms组件
 *
 * @author: rudy
 * @date: 2016/05/09
 */

namespace framework\component;


use \Http;

class SmsWoquComponent extends Component{

    protected $lastSendTime = 0;
    protected $sendMessageNum = 0;
    
    public function __construct($componentName='', $config=array()){
        parent::__construct($componentName, $config);
        
        if(!isset($config['serverUrl']) || !isset($config['Module']) || !isset($config['To']) || !isset($config['MsgType'])){
            throw new \Exception('sms config load failure!',-1);
        }
    }

    /**
     * 限制某段时间内的短信发送量,避免被轰炸
     * @return bool
     */
    protected function canSendMessage(){
        $interval_time = 600; // 10分钟内最多发送3条信息
        $maxSendNum = 3;
        $now = time();
        if(($now - $this->lastSendTime) > $interval_time){
            $this->lastSendTime = $now;
            $this->sendMessageNum = 1;
            return true;
        }
        if($this->sendMessageNum <= $maxSendNum){
            $this->sendMessageNum ++;
            return true;
        }
        return false;
    }


    /**
     * 发送消息函数
     * @param string $content  发送的消息内容
     * @param bool $needSend 当前短信是否必须发送出去
     * @return bool
     */
    public function sendMessage($content,$needSend=false){
        if(!($this->canSendMessage() || $needSend)){
            return false;
        }
        $phoneNumbers = explode(',',$this->oneConfig['To']);
        if(empty($this->oneConfig['To'])){
            Http::request($this->oneConfig['serverUrl'],array(
                'Module' => $this->oneConfig['Module'],
                'MsgType' => $this->oneConfig['MsgType'],
                'To' => '',
                'MsgText' => $content));
        }else{
            foreach($phoneNumbers as $key=>$phone){
                Http::request($this->oneConfig['serverUrl'],array(
                    'Module' => $this->oneConfig['Module'],
                    'MsgType' => $this->oneConfig['MsgType'],
                    'To' => $phone,
                    'MsgText' => $content));
            }
        }
        return true;
    }
}