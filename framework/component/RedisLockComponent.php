<?php
/**
 *
 * 封装mysql操作类组件
 *
 * @author: rudy
 * @date: 2016/05/09
 */

namespace framework\component;


use \TLock;
use \TRedis;

class RedisLockComponent extends Component{
    protected $isLoadConfig = false;
    
    public function getInstance(){
        if(!$this->isLoadConfig){
            $this->oneConfig['connectionName'] = $this->componentName;
            TRedis::loadOneConfig($this->oneConfig);
            $this->isLoadConfig = true;
        }
        
        return new TLock($this->componentName,$this->getArrayValue($this->oneConfig,'prefix','lock_'),$this->getArrayValue($this->oneConfig,'expire',3600));
    }


    /**
     * 安全地获取数组的值
     * @param $arr array 数组
     * @param $key String 键名
     * @param string $default 默认值
     * @return string
     */
    public static function getArrayValue($arr,$key,$default=''){
        if(isset($arr[$key])){
            return $arr[$key];
        }
        return $default;
    }
}