<?php
/**
 * User: rudy
 * Date: 2016/04/15 16:21
 *
 *  英雄大神榜请求产生器
 *  初始化时候使用
 *
 */

namespace application\task;

use application\extension\Http;
use framework\base\Logger;
use framework\base\Request;
use framework\coroutine\TimerTask;
use framework\TSpider;

class HeroGodRankRequestTimer extends TimerTask{
    protected $intervalTime = 1;
    protected $requestGenerator = null;

    public function __construct($intervalTime=0, $taskName=''){
        $this->requestGenerator = $this->getOneRequest();
        parent::__construct($intervalTime, $taskName);
    }

    public static function createRequest($tgpId,$page=1){
        $time = time().''.rand(100,999);
        $url = "http://img.lol.qq.com/js/cevRank/{$tgpId}/{$page}.js?t={$time}";
        return new Request($url,'application\worker\HeroGodRankWorker',array(),array('tgpId'=>$tgpId,'page'=>$page));
    }
    
    public function getOneRequest(){
        $tgpIdList = Http::request('http://lol.anzogame.com/apis/rest/RolesService/tgpId2IdForPkg?iamsuperman=2');
        $tgpIdList = json_decode($tgpIdList,true);
        $tgpIdList = array_keys($tgpIdList);
        if(empty($tgpIdList)){
            $str = 'get tgpId error! exit spider';
            Logger::factory('common')->error($str);
            TSpider::$component->sms->sendMessage($str,true);
            exit($str);
        }

        foreach($tgpIdList as $tgpId){
            yield self::createRequest($tgpId);
        }
    }

    /**
     * 执行入口,复写父类相同名称方法
     */
    function execute(){
        for($i=0;$i<30;$i++){
            $request = $this->requestGenerator->current();
            if($request instanceof Request){
                if(!$request->download()){
                    break;
                }
                $this->requestGenerator->next();
            }else{
                break;
            }
        }
    }
}