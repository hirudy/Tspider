<?php
/**
 * User: rudy
 * Date: 2016/02/29 18:59
 *
 *  功能描述
 *
 */

namespace framework\base;


class Response{
    protected $info;
    protected $data;
    protected $error;

    public $code = 0;

    public function __construct($info, $data='', $error=''){
        $this->info = $info;
        $this->data = $data;
        $this->error = $error;
        if(isset($this->info['http_code'])){
            $this->code = $this->info['http_code'];
        }
    }

    public function getData(){
        return $this->data;
    }

    public function getError(){
        return $this->error;
    }
}