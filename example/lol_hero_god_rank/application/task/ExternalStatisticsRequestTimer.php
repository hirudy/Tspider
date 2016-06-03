<?php
/**
 * User: rudy
 * Date: 2016/04/15 16:21
 *
 *  请求国外的每个英雄的统计数据产生器
 *  http://champion.gg/champion/garen
 *
 */

namespace application\task;

use \Http;
use framework\base\Logger;
use framework\base\Request;
use framework\coroutine\TimerTask;
use framework\TSpider;

class ExternalStatisticsRequestTimer extends TimerTask{
    protected $intervalTime = 1;
    protected $requestGenerator = null;

    public static $championInfoList = array();

    public function __construct($intervalTime=0, $taskName=''){
        $this->requestGenerator = $this->getOneRequest();
        parent::__construct($intervalTime, $taskName);
    }

    /**
     * 获取英雄信息
     * @return array|bool
     * @throws \framework\base\Exception
     */
    public static function getChampionInfo(){
        if(empty(self::$championInfoList)){
            $url = 'http://lol.zhangyoubao.com/apis/rest/RolesService/championInfo?iamsuperman=2';
            $championInfoList = false;
            for ($i=0;$i < 5;$i++){
                $data = Http::request($url);
                $data = json_decode($data,true);
                if(is_array($data)){
                    $championInfoList = $data;
                    break;
                }
            }
            if($championInfoList == false){
                $str = "spider exit: championInfo get error;";
                Logger::factory('common')->error($str);
                TSpider::$component->sms->sendMessage($str,true);
                exit($str);
            }else{
                foreach ($championInfoList as $key=>$row){
                    if($row['id'] == 1){
                        // 奎恩
                        $championInfoList[$key]['enname'] = 'Quinn';
                    }
                    self::$championInfoList[$championInfoList[$key]['enname']] =$championInfoList[$key];
                }
            }
        }
        return self::$championInfoList;
    }

    public function getOneRequest(){
        $championInfoList = self::getChampionInfo();
        foreach ($championInfoList as $enname => $row){
            yield self::createRequest($enname,$row);
        }
    }

    public static function createRequest($championAlias,$championInfo){
        $url = "http://champion.gg/champion/{$championAlias}";
        return new Request($url,'application\worker\ExternalStatisticsWorker',array(),array('championAlias'=>$championAlias,'championInfo'=>$championInfo));
    }

    /**
     * 执行入口,复写父类相同名称方法
     */
    function execute(){
        for($i=0;$i<20;$i++){
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