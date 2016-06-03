<?php
/**
 *
 * 本地的请求队列/非远程的(如通过redis/beanstalk等等的)
 *
 * @author: rudy
 * @date: 2016/05/09
 */

namespace framework\queue;


use framework\base\Request;

class LocalRequestQueue extends RequestQueue{
    
    public function __construct(){
        $this->queue = new \SplQueue();
    }
    
    public function add(Request $request){
        $this->queue->enqueue($request);
    }

    public function get(){
        if($this->isEmpty()){
            return null;
        }
        return $this->queue->dequeue();
    }

    public function isEmpty(){
        return $this->queue->isEmpty();
    }

    public function count(){
        return $this->queue->count();
    }

    public function isFull(){
        if($this->count() >= $this->maxCount){
            return true;
        }
        return false;
    }
    
    public function __destruct(){
        unset($this->queue);
    }
}