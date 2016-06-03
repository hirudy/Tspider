<?php
/**
 * User: rudy
 * Date: 2016/03/15 14:36
 *
 *  任务调度器
 *
 */

namespace framework\coroutine;


class TaskScheduler{
    protected $maxTaskId = 0;
    protected $taskMap = []; // taskId => task
    protected $taskQueue = null;

    protected static $is_closed = false;

    public function __construct() {
        $this->taskQueue = new \SplQueue();
    }

    public static function closeAllTask(){
        self::$is_closed = true;
    }

    public static function isAllTaskClosed(){
        return self::$is_closed;
    }

    public function addTask(Task $task){
        $taskId = $task->getTaskId();
        $this->taskMap[$taskId] = $task;
        $this->schedule($task);
        return $taskId;
    }

    public function schedule(Task $task) {
        $this->taskQueue->enqueue($task);
    }

    public function run() {
        while (!$this->taskQueue->isEmpty()) {
            $task = $this->taskQueue->dequeue();
            $task->run();

            if ($task->isFinished()) {
                unset($this->taskMap[$task->getTaskId()]);
            } else {
                $this->schedule($task);
            }
            if(TaskScheduler::isAllTaskClosed()){
                break;
            }
        }
    }
}