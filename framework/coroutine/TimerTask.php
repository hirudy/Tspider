<?php
/**
 * User: rudy
 * Date: 2016/03/15 15:01
 *
 *  定时器父类
 *
 */

namespace framework\coroutine;


abstract class TimerTask extends Task{

    protected $intervalTime = 60;
    protected $expireTime = 0;

    public function __construct($intervalTime=0,$taskName=''){
        $intervalTime = (int)$intervalTime;
        if($intervalTime > 0){
            $this->intervalTime = $intervalTime;
        }
        if(empty($taskName)){
            $taskName = get_class($this);
        }
        $this->expireTime = time()+$this->intervalTime;
        parent::__construct($taskName);
    }

    public function coroutine(){
        while(true){
            if($this->expireTime <= time()){
                $rel = $this->execute();
                $this->expireTime = time()+$this->intervalTime;
                if($rel === false){
                    break;
                }
            }
            yield true;
        }
    }

    public abstract function execute();
}