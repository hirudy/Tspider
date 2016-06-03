<?php
/**
 *
 * 封装mysql操作类组件
 *
 * @author: rudy
 * @date: 2016/05/09
 */

namespace framework\component;

use \TRedis;

class RedisComponent extends Component{
    protected $isLoadConfig = false;
    
    public function getInstance(){
        if(!$this->isLoadConfig){
            $this->oneConfig['connectionName'] = $this->componentName;
            TRedis::loadOneConfig($this->oneConfig);
            $this->isLoadConfig = true;
        }
        return TRedis::getConnection($this->componentName);
    }
}