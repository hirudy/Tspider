<?php
/**
 * User: rudy
 * Date: 2016/03/15 21:29
 *
 *  重写下载器before,after钩子，完成中间任务的处理（添加代理/记录日志等），以及统计
 *
 */

namespace application\task;

use framework\base\Logger;
use framework\base\Request;
use framework\base\Response;
use framework\task\DownloadTask;
use framework\TSpider;

class MonitorDownloadTask extends DownloadTask{
    protected $beforeDownloadLog = null;
    protected $afterDownloadLog = null;
    protected $proxyLog = null;
    protected $errorDownloadLog = null;


    public static $speedSecond = 60;
    //统计
    public static $statisticsAllRequestNum = 0; //所有请求次数
    public static $statisticsAllDownloadNum = 0; //所有下载次数
    public static $statisticsErrorNum = 0;  //出错次数

    public static $statisticsDownloadSpeed = '0'; //下载速度

    public function __construct($taskName=''){
        $this->beforeDownloadLog = Logger::factory('beforeDownload');
        $this->afterDownloadLog = Logger::factory('afterDownload');
        $this->errorDownloadLog = Logger::factory('errorDownload');
//        $this->proxyLog = Logger::factory('proxy');
        parent::__construct($taskName);
    }

    public function countSpeed(){
        static $lastTime;
        static $downloadNum;

        $intLastTime = (int)$lastTime;
        $downloadNum = (int)$downloadNum;
        $downloadNum ++;
        if(time()-$intLastTime>= self::$speedSecond){
            self::$statisticsDownloadSpeed = "{$downloadNum}/m";
            $downloadNum = 0;
            $lastTime =time();
        }
    }


    public function beforeDownload(Request $request){
        self::$statisticsAllRequestNum ++;
        $this->beforeDownloadLog->log((string)$request);
        return $request;
    }

    public function afterDownload(Request $request,Response $response){
        self::$statisticsAllDownloadNum ++;
        $this->countSpeed();
        if($response->code == 200 || $response->code == 404){
            $this->afterDownloadLog->log($request->url.'|'.$response->code);
        }else{
            self::$statisticsErrorNum ++;
            $this->afterDownloadLog->error($request->url.'|'.$response->code.'|'.$request->getRepeatNum().'|'.$response->getError());

            // 重新下载
            if(!$request->redownload()){
                // 最终下载失败的
                $url = substr($request->url,-35);
                TSpider::$component->sms->sendMessage("download error:$url");
                $this->errorDownloadLog->error($request->url.'|'.$response->code.'|'.$request->getRepeatNum().'|'.$response->getError());
            }
            return false;
        }
        return null;
    }
}