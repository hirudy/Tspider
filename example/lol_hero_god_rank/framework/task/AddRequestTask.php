<?php
/**
 * User: rudy
 * Date: 2016/03/15 19:08
 *
 * 从请求队列中获取请求添加到下载器下载队列中
 *
 */

namespace framework\task;


use framework\base\Request;
use framework\coroutine\Task;
use framework\TSpider;

class AddRequestTask extends Task{
    protected $downloader = null;

    public function __construct(DownloadTask $downloader,$taskName=''){
        $this->downloader = $downloader;
        parent::__construct($taskName);
    }

    public function coroutine(){
        do{
            if (!TSpider::$requestQueue->isEmpty()){
                do{
                    $request = TSpider::$requestQueue->get();
                    $rel = $this->downloader->addRequest($request);
                    
                    // 如果下载队列添加失败,将请求重新放回请求队列中
                    if($request instanceof Request && !$rel){
                        TSpider::$requestQueue->add($request);
                    }
                }while($rel);
            }
            yield true;
        }while(true);
    }
}