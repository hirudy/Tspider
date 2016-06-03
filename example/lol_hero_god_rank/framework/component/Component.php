<?php
/**
 *
 * 组件基类
 *
 * @author: rudy
 * @date: 2016/05/09
 */

namespace framework\component;


interface IComponent{
    public function getInstance();
}

class Component implements IComponent{
    
    public static $config = array();
    public static $instanceMap = array();
    
    public $componentName = '';
    public $oneConfig = '';

    public function __get($name){
        if(!isset(self::$config[$name])){
            throw new \Exception("have no component:{$name}\n",-1);
        }
        if(isset(self::$instanceMap[$name]) && self::$instanceMap[$name] != null){
            return self::$instanceMap[$name]->getInstance();
        }

        $className = isset(self::$config[$name]['className'])?self::$config[$name]['className']:'';
        if(!class_exists($className)){
            throw new \Exception("have no component className:{$className}\n",-1);
        }

        $component = new $className($name,self::$config[$name]);
        if(!($component instanceof Component)){
            throw new \Exception("className:{$className} is not Component\n",-1);
        }

        self::$instanceMap[$name] = $component;
        
        return self::$instanceMap[$name]->getInstance();
    }
    
    public function __construct($componentName='',$config=array()){
        $this->componentName = $componentName;
        $this->oneConfig = $config;
    }

    
    
    public function getInstance(){
        return $this;
    }
    
}