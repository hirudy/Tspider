<?php
/**
 * User: rudy
 * Date: 2016/03/15 15:39
 *
 *  下载器任务
 *
 */
namespace framework\task;

use framework\base\Request;
use framework\base\Response;
use framework\coroutine\Task;

class DownloadTask extends Task{
    public $windowSize = 0;
    protected $currentRequestMap = array();

    protected $multiDownloader = null; // 多线程下载器句柄

    protected $spareTime = 0;  // 下载器空闲的开始时间

    /**
     * 分发到具体的解析器
     * @param Request $request
     * @param Response $response
     */
    protected function dispatch(Request $request,Response $response){
        $rel = $this->afterDownload($request,$response);
        if($rel !== false){
            $worker = new $request->workerName();
            
            if($worker->beforeParse() !== false){
                $worker->parse($request,$response);
            }
            $worker->afterParse();
        }
    }

    /**
     * 初始化下载器
     * DownloadTask constructor.
     * @param string $taskName
     */
    public function __construct($taskName=''){
        $this->multiDownloader = curl_multi_init();
        parent::__construct($taskName);
    }

    /**
     *
     * @param $request
     * @return bool
     */
    public function addRequest($request){
        $response = false;
        do{
            if($this->multiDownloader == null){
                break;
            }

            if(!($request instanceof Request)){
                break;
            }

            $currentRequestNum = count($this->currentRequestMap);
            if($currentRequestNum >= $this->windowSize){
                break;
            }

            $rel = $this->beforeDownload($request);
            if($rel === false){
                break;
            }

            if($rel instanceof Request){
                $request = $rel;
            }
            $ch = $request->createCurlObject();
            $key = (string)$ch;
            $this->currentRequestMap[$key] = $request;
            curl_multi_add_handle($this->multiDownloader, $ch);

            $this->spareTime = 0;
            $response = true;
        }while(false);

        return $response;
    }

    public function beforeDownload(Request $request){
        return null;
    }

    public function afterDownload(Request $request,Response $response){
        return null;
    }

    // 获取下载器闲置时间
    public function getSpareTime(){
        if($this->spareTime == 0){
            return 0;
        }else{
            return time()- $this->spareTime;
        }
    }

    public function coroutine()
    {
        do {
            while (($execrun = curl_multi_exec($this->multiDownloader, $running)) == CURLM_CALL_MULTI_PERFORM) ;
            if ($execrun != CURLM_OK) {
                if($this->spareTime == 0){
                    $this->spareTime = time();
                }
            }

            // 一旦有一个请求完成，找出来，因为curl底层是select，所以最大受限于1024
            while ($done = curl_multi_info_read($this->multiDownloader))
            {
                // 从请求中获取信息、内容、错误
                $info = curl_getinfo($done['handle']);
                $output = curl_multi_getcontent($done['handle']);
                $error = curl_error($done['handle']);
                $response = new Response($info,$output,$error);

                $key = (string)$done['handle'];
                $request = $this->currentRequestMap[$key];
                $this->dispatch($request,$response);

                // 把请求已经完成了得 curl handle 删除
                unset($this->currentRequestMap[$key]);
                curl_multi_remove_handle($this->multiDownloader, $done['handle']);
            }

            // 当没有数据的时候进行堵塞，把 CPU 使用权交出来，避免上面 do 死循环空跑数据导致 CPU 100%
            if ($running) {
                $rel = curl_multi_select($this->multiDownloader, 1);
                if($rel == -1){
                    usleep(1000);
                }
            }

            if( $running == false){
                if($this->spareTime == 0){
                    $this->spareTime = time();
                }
            }
            yield true;
        } while (true);

        // 关闭任务
        curl_multi_close($this->multiDownloader);
        unset($this->multiDownloader);
    }
}