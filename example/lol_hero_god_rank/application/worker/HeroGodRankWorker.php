<?php

/**
 * User: rudy
 * Date: 2016/04/14 18:38
 *
 *  具体的业务处理，请求处理
 *
 */
namespace application\worker;

use application\task\HeroGodRankRequestTimer;
use framework\base\BaseWorker;
use framework\base\Logger;
use framework\base\Request;
use framework\base\Response;
use framework\TSpider;

class HeroGodRankWorker extends BaseWorker{
    protected $errorLog = null;
    protected $log = null;
    
    public function __construct(){
        $this->errorLog = Logger::factory('errorParse');
        $this->log = Logger::factory('worker');
    }


    /**
     * 重写解析方法
     * @param Request $request
     * @param Response $response
     */
    public function parse(Request $request,Response $response){
        if($response->code == 200){
            $newRequest = HeroGodRankRequestTimer::createRequest($request->extData['tgpId'],$request->extData['page']+1);
            $newRequest->download();
        }else{
            return null;
        }
        $extractResult = $this->extract($response->getData());
        if($extractResult == false){
            // 重新下载
            if(!$request->redownload()){
                // 最终下载失败的
                $url = substr($request->url,-35);
                TSpider::$component->sms->sendMessage("parse error:$url");
                $this->errorLog->error('parse error:'.$request->url.'|'.$response->code.'|'.$response->getData());
            }
            return;
        }
        
        $this->log->log($request->url.' | '.$request->getRepeatNum());
        $this->save($extractResult,$request->extData['tgpId']);
    }

    /**
     * 提取数据
     * @param $data
     * @return array|bool
     */
    public function extract($data){
        $data = str_replace(array("try{heroSkillCallback(",")}catch(e){}"),array('',''),$data);
        $data = json_decode($data,true);
        if(!isset($data['retCode']) || !isset($data['data']['skillRank']) || $data['retCode'] !== 0){
            return false;
        }
        return $data['data']['skillRank'];
    }


    /**
     * 存储经过提取后的数据
     * @param $extractData
     * @throws \Exception
     */
    public function save($extractData,$heroId=0){
        try{
            $data_list = array();
            foreach($extractData as $row){
                $temp = array();
                $temp['version'] = TSpider::$startCrawlTime;
                $temp['hero_id'] = $heroId;
                $temp['rank'] = $row['index'];
                $temp['area_id'] = $row['area_id'];
                $temp['area_name'] = $row['areaName'];
                $temp['icon_id'] = $row['iconId'];
                $temp['uin'] = $row['uin'];
                $temp['username'] = $row['uName'];
                $temp['proficiency'] = $row['cevValue'];

                $data_list[] = $temp;
            }
            $rel = TSpider::$component->dbSnatchLol->insertMulti('hero_god_rank',$data_list);
            if($rel <= 0){
                throw new \Exception('insert error:',-1);
            }
            self::$statisticsSaveNum ++;
        }catch(\Exception $e){
            $temp = substr($e->getMessage(),-35);
            TSpider::$component->sms->sendMessage("save error:{$temp}");
            $this->errorLog->error('save error:'.$e->getMessage().'|'.json_encode($extractData));
        }
    }
}