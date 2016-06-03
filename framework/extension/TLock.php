<?php
/**
 *
 * 基于TRedis实现的依赖于redis的分布式锁
 *
 * @author: rudy
 * @date: 2016/05/16
 */

class TLock {
    protected $connectionName = '';  // redis连接名称
    protected $uniqueId = null; // 当前对象唯一id
    protected $prefix = ''; // 键值对前缀
    
    protected $expire = 0; // 锁自动过期时间,防止获得锁而不释放锁的情况而出现的资源永远不能释放的情况

    private $resource_name = ''; // 资源名称

    public function __construct($connectionName='',$prefix='',$expire=3600){
        $this->connectionName = $connectionName;
        $this->uniqueId = uniqid();
        $this->prefix = $prefix;
        $this->expire = $expire;
    }


    /**
     * 获取一个非阻塞锁 true-获取到,false-没有获取到
     * @param $resource_name
     * @return bool
     * @throws RedisOperationException
     */
    public function lock($resource_name){
        if(!empty($this->resource_name)){
            return false;
        }
        $this->resource_name = $resource_name;
        
        $redis = TRedis::getConnection($this->connectionName);
        if($redis->setNx($this->resource_name,$this->uniqueId)){
            $redis->expire($this->resource_name,$this->expire);
            return true;
        }
        return false;
    }

    /**
     * 获得一个阻塞锁
     * @param $resource_name
     * @return bool
     * @throws RedisOperationException
     */
    public function lockWait($resource_name){
        if(!empty($this->resource_name)){
            return false;
        }
        $this->resource_name = $resource_name;
        
        do{
            $redis = TRedis::getConnection($this->connectionName);
            if($redis->setNx($this->resource_name,$this->uniqueId)){
                $redis->expire($this->resource_name,$this->expire);
                break;
            }
            
            sleep(1);
        }while(true);
        return true;
    }
    
    /**
     * 解锁,只能解锁自己对象的说
     * @throws RedisOperationException
     */
    public function unlock(){
        $redis = TRedis::getConnection($this->connectionName);
        $rel = $redis->get($this->resource_name);
        if($rel == $this->uniqueId){
            $redis->delete($this->resource_name);
        }
    }
}