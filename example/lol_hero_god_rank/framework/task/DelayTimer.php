<?php

/**
 * User: rudy
 * Date: 2016/03/15 15:11
 *
 *  延迟timer
 *
 */
namespace framework\task;

use framework\coroutine\TimerTask;

class DelayTimer extends TimerTask{
    protected $intervalTime = 1;

    function execute(){
       sleep(1);
    }
}