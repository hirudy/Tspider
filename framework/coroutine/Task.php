<?php
/**
 * User: rudy
 * Date: 2016/03/15 13:03
 *
 *  任务对象抽象类
 *
 */
namespace framework\coroutine;

use \Exception;

abstract class Task {
    public static $maxTaskId = 0;
    protected $taskId;
    protected $taskName;
    protected $taskContent;

    protected $sendValue = null;
    protected $beforeFirstYield = true;

    public function __construct($taskName='') {
        $this->taskId = ++self::$maxTaskId;

        $this->taskName = $taskName;
        if(empty($taskName)){
            $this->taskName = get_class();
        }
        $taskContent = $this->coroutine();
        if($taskContent instanceof \Generator){
            $this->taskContent = $taskContent;
        }else{
            throw new Exception('Task is not a coroutine',-1);
        }
    }

    public function getTaskId() {
        return $this->taskId;
    }

    public function getTaskName() {
        return $this->taskName;
    }

    public function setSendValue($sendValue) {
        $this->sendValue = $sendValue;
    }

    abstract public function coroutine();

    public function run() {
        if ($this->beforeFirstYield) {
            $this->beforeFirstYield = false;
            return $this->taskContent->current();
        } else {
            $retval = $this->taskContent->send($this->sendValue);
            $this->sendValue = null;
            return $retval;
        }
    }

    public function isFinished() {
        return !$this->taskContent->valid();
    }
}