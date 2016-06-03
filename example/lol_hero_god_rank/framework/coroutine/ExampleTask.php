<?php
/**
 * User: rudy
 * Date: 2016/03/15 14:07
 *
 *  任务例子
 *
 */

namespace framework\coroutine;


class ExampleTask extends Task{

    public function coroutine(){
        for($i=0;$i<5;$i++){
            $y = (yield $i);
            echo "hello world {$y} \n";
        }
    }
}