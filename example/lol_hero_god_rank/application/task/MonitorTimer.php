<?php
/**
 * User: rudy
 * Date: 2016/03/15 19:50
 *
 *  监控协程，实时监控，结束爬虫
 *
 */

namespace application\task;


use \Common;
use framework\base\BaseWorker;
use framework\base\Logger;
use framework\coroutine\TaskScheduler;
use framework\coroutine\TimerTask;
use framework\TSpider;

class MonitorTimer extends TimerTask{
    protected $intervalTime = 2;
    protected $log = null;

    function __construct($intervalTime=0, $taskName=''){
        $this->log = Logger::factory('monitor');
        parent::__construct($intervalTime, $taskName);
    }

    /**
     * 保存通用爬虫参数
     * @param $prefix
     * @param $dbName
     * @throws \framework\base\Exception
     */
    public function saveCrawlData($prefix,$dbName){
        $data = array(
            $prefix.'pre_version' => TSpider::$startCrawlTime,
            $prefix.'update_time' => date('Y-m-d H:i:s',TSpider::$startCrawlTime),
            $prefix.'use_time' => time()-TSpider::$startCrawlTime,
            $prefix.'version' => TSpider::$startCrawlTime
        );

        foreach($data as $key=>$value){
            $sql = '';
            try{
                $connection = TSpider::$component->$dbName;
                if($prefix.'pre_version' == $key){
                    $sql = "select `value` from control where `key`='{$prefix}version' limit 1;";
                    $rel = $connection->query($sql);
                    if(!empty($rel)){
                        $value = $rel[0]['value'];
                    }
                }
                $sql = "select `value` from control where `key`='{$key}';";
                $rel = $connection->query($sql);
                if(empty($rel)){
                    $sql = "insert control(`key`,`value`) values('{$key}','{$value}')";
                }else{
                    $sql = "update control set `value`='{$value}' where `key`='{$key}';";
                }

                $connection->query($sql);
            }catch(\Exception $e){
                Logger::factory('common')->error('sql error: '.$sql.' | '.$e->getMessage());
            }
        }
    }
    
    function execute(){
        $name = TSpider::$spiderName;
        $requestQueueCount = TSpider::$requestQueue->count();
        $requestNum = MonitorDownloadTask::$statisticsAllRequestNum;
        $downloadNum = MonitorDownloadTask::$statisticsAllDownloadNum;
        $downloadErrorNum = MonitorDownloadTask::$statisticsErrorNum;
        $downloadSpeed = MonitorDownloadTask::$statisticsDownloadSpeed;
        $parserNum = BaseWorker::$statisticsParserNum;
        $parserErrorNum = BaseWorker::$statisticsParserErrorNum;
        $saveNum = BaseWorker::$statisticsSaveNum;
        $saveErrorNum = BaseWorker::$statisticsSaveErrorNum;
        $memory = Common::getMemoryUsedSizeShow();
        $str = "Memory:{$memory}|Queue:{$requestQueueCount}|Downloader:{$requestNum}-{$downloadNum}-{$downloadErrorNum}|Parser:{$parserNum}-{$parserErrorNum}-{$saveNum}-{$saveErrorNum}|Speed:{$downloadSpeed}";
        $this->log->log($str);

        // 当下载器空闲多长时间，关闭爬虫
        $downloaderSpareTime = TSpider::$downloader->getSpareTime();
        if($downloaderSpareTime >= 10){
            TaskScheduler::closeAllTask();
            // 保存操作变量
            switch (TSpider::$taskGroup){
                case 'hero_god':{
                    $this->saveCrawlData('hero_god_rank_','dbSnatchLol');
                }break;
                case 'external':{
                    $this->saveCrawlData('hero_external_','dbSnatchLol');
                }break;
                default:{
                    $this->saveCrawlData('hero_god_rank_','dbSnatchLol');
                    $this->saveCrawlData('hero_external_','dbSnatchLol');
                }
            }
            // 发送结束短信
            $str = "{$name} End:an-{$requestNum},dn-{$downloadNum},de-{$downloadErrorNum},pn-{$parserNum},pe-{$parserErrorNum},sn-{$saveNum},se-{$saveErrorNum}";
            TSpider::$component->sms->sendMessage($str,true);
            Logger::factory('common')->log($str);
        }
    }
}