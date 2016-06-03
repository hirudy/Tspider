<?php
/**
 * @author: rudy
 * @time: 2016/05/05
 *
 * redis操作类封装
 *
 */

class RedisOperation extends \Redis{
    
    public function __destruct(){
        @$this->close();
    }
}

class RedisOperationException extends \RedisException{
    
}

class TRedis {
    public static $maxConnectionTime = 5;   // 最大连接时间
    protected static $connectionPool = array(); // 连接池对象

    protected static $configs = array(); // 配置项

    /**
     * 获取数组值
     * @param array $arr
     * @param string $key
     * @param null $default
     * @return mixed|null
     */
    public static function getArrayValue($arr=array(), $key='',$default=null){
        $response = null;
        if(is_array($arr) && isset($arr[$key])){
            $response =  $arr[$key];
        }
        if($response == null){
            $response = $default;
        }
        return $response;
    }
    
    /**
     * 加载单个配置文件
     * @param array $configArr
     */
    public static function loadOneConfig($configArr= array()){
        $tempConfig = array();
        $tempConfig['connectionName'] = self::getArrayValue($configArr,'connectionName'); // 连接名称
        $tempConfig['host'] = self::getArrayValue($configArr,'host');  // 连接host
        $tempConfig['port'] = self::getArrayValue($configArr,'port',6379);   // 端口号
        $tempConfig['database'] = self::getArrayValue($configArr,'database',0); // 使用数据库索引
        $tempConfig['password'] = self::getArrayValue($configArr,'password'); // 使用密码
        $tempConfig['prefix'] = self::getArrayValue($configArr,'prefix',''); // 数据库查询中的key前缀
        $tempConfig['checkConnection'] = self::getArrayValue($configArr,'checkConnection',false);
        if(empty($tempConfig['connectionName']) || !is_string($tempConfig['connectionName'])){
            $tempConfig['name'] = 'default';
        }
        self::$configs[$tempConfig['connectionName']] = $tempConfig;
    }

    /**
     * 获取一个连接对象
     * @param string $connectionName
     * @return mixed
     * @throws \Exception
     * @throws \RedisException
     */
    public static function getConnection($connectionName ='default'){
        if (!is_string($connectionName) || !isset(self::$configs[$connectionName])){
            throw new RedisOperationException("redis config : {$connectionName} is't loaded !",-1);
        }

        // 检测连接是否断开,断开重连
        if(isset(self::$connectionPool[$connectionName]) && self::$configs[$connectionName]['checkConnection']){
            try{
                $redisObject = self::$connectionPool[$connectionName];
                if($redisObject->ping() != '+PONG'){
                    throw new RedisOperationException("redis ping error",-1);
                }
            }catch (RedisOperationException $e){
                @self::$connectionPool[$connectionName]->close();
                unset(self::$connectionPool[$connectionName]);
            }
        }

        // 创建连接
        if(!isset(self::$connectionPool[$connectionName])){
            $redisObject = new RedisOperation();
            $rel = $redisObject->connect(self::$configs[$connectionName]['host'],self::$configs[$connectionName]['port'],self::$maxConnectionTime);
            if(!$rel){
                throw new RedisOperationException("redis connection error:{$connectionName}",-1);
            }
            if(!empty(self::$configs[$connectionName]['password'])){
                $rel = $redisObject->auth(self::$configs[$connectionName]['password']);
                if(!$rel){
                    $redisObject->close();
                    throw new RedisOperationException("redis connection auth error:{$connectionName}",-1);
                }
            }
            $db = (int)self::$configs[$connectionName]['database'];
            $rel = $redisObject->select($db);
            if(!$rel){
                $redisObject->close();
                throw new RedisOperationException("redis select db error:name-{$connectionName},db-{$db}",-1);
            }
            if(is_string(self::$configs[$connectionName]['prefix']) && !empty(self::$configs[$connectionName]['prefix'])){
                $redisObject->setOption(\Redis::OPT_PREFIX,self::$configs[$connectionName]['prefix']);
            }


            self::$connectionPool[$connectionName] = $redisObject;
        }
        return self::$connectionPool[$connectionName];
    }

    /**
     * 清理连接
     */
    public static function clearAllConnection(){
        self::$connectionPool = array();
    }
}


//测试
if(strtolower(PHP_SAPI) == 'cli' && isset($argv) && basename(__FILE__) == basename($argv[0])){
    $config = array(
        'connectionName' => 'cacheRd',
        'host'=> '*.*.*.*',
//        'password' => '*****',
        'database' => 1,
        'port' => 6379,
        'prefix' => 'redis_test_',
        'checkConnection' => true
    );

    TRedis::loadOneConfig($config);
    $redis = TRedis::getConnection('cacheRd');
//    $redis->hSet('h', 'key1', 'hello');
//    $redis->expire('h',10);
    echo $redis->dbSize();
    print_r($redis->keys('*'));
//    print_r($testString);
}