<?php

/**
 * User: rudy
 * Date: 2016/01/19 19:39
 *
 * 日志记录工具
 *
 */
namespace framework\base;

use \Exception;

class Logger{
    public static $g_isLogging = true;  // 总开关，是否记录日志

    const LOG_MODE_NORMAL = 1; // 生产模式,正常记录到文件中
    const LOG_MODE_PRINT  = 2; // 调试模式,打印到屏幕上
    const LOG_MODE_BOTH   = 3; // 文件与屏幕上都有

    const LOG_FREQUENCY_NONE   = 0; // 存放的日志文件始终只有一个文件,形如 default.log
    const LOG_FREQUENCY_MINUTE = 1; // 存放日志每隔一分钟换一个，形如 default_201601192357.log
    const LOG_FREQUENCY_HOUR   = 2; // 存放日志每隔一小时换一个，形如 default_2016011923.log
    const LOG_FREQUENCY_DAY    = 3; // 存放日志每隔一天换一个，形如   default_20160119.log
    const LOG_FREQUENCY_MONTH  = 4; // 存放日志每隔一月换一个，形如   default_201601.log

    const LOG_LEVEL_ERROR = 1;  //日志等级,错误日志
    const LOG_LEVEL_WARN  = 2;  //日志等级,警告日志
    const LOG_LEVEL_INFO  = 3;  //日志等级,信息记录日志

    public static $g_basePath = '/data/logs/'; //默认存储路径

    protected static $allowFrequencyList = array(   //允许修改日志频率
        self::LOG_FREQUENCY_MINUTE,
        self::LOG_FREQUENCY_HOUR,
        self::LOG_FREQUENCY_DAY,
        self::LOG_FREQUENCY_MONTH
    );

    protected static $g_config_arr = array(  // 日志配置文件数组,default是默认配置项(不允许修改)
        'default' => array(
            'isLogging' => true,
            'basePath' => '',
            'suffix' => 'log',
            'level' => array(self::LOG_LEVEL_ERROR,self::LOG_LEVEL_WARN,self::LOG_LEVEL_INFO),
            'mode' => self::LOG_MODE_NORMAL,
            'frequency' => self::LOG_FREQUENCY_NONE,
        ),
    );

    protected static $logPool = array();    // 日志对象池子

    protected $isLoging;      //当前日志,是否记录
    protected $logName;       //当前日志,日志名称
    protected $basePath;      //当前日志,存储路径
    protected $suffix;        //当前日志,日志文件后缀
    protected $level;         //当前日志,运行记录的日志等级
    protected $mode;          //当前日志,记录方式
    protected $frequency;     //当前日志,日志记录每隔一（分钟/小时/天/月）换一个文件记录

    private $logFilePath;     //完整的日志路径
    private $timeLength;      //存储时间长度

    /**
     * 以二维数组的形式加载多个日志配置文件
     * @param $arr array 二维数组
     */
    public static function loadConfig($arr){
        if(!empty($arr) && is_array($arr)){
            foreach($arr as $name =>$config){
                self::loadOneConfig($config,$name);
            }
        }
    }

    /**
     * 加载一个日志配置文件
     * @param $arr
     * @param string $name
     * @return bool
     */
    public static function loadOneConfig($arr,$name=''){
        if(!is_array($arr)){
            $arr = array();
        }
        $name = isset($arr['logName'])?$arr['logName']:$name;
        $name = str_replace(array('\\','/'),'_',$name);
        if(!is_string($name) || empty($name) || $name == 'default'){
            return false;
        }
        unset($arr['logName']);
        self::$g_config_arr[$name] = $arr;

        return true;
    }


    /**
     * 根据日志名称，获取一个日志实例
     * @param string $logName 配置名称
     * @param bool $isNew 是否生成一个新的日志对象
     * @return Logger
     * @throws Exception
     */
    public static function factory($logName='default',$isNew=false){
        $logName = str_replace(array('\\','/'),'_',$logName);
        if(!isset(self::$logPool[$logName]) || $isNew == true){
            if(isset(self::$logPool[$logName])){
                unset(self::$logPool[$logName]);
            }

            if(empty($logName) || !is_string($logName) || !isset(self::$g_config_arr[$logName])){
                throw new Exception("Make sure that the log configuration which name is '{$logName}' is loaded successfully");
            }
            self::$logPool[$logName] = new self(self::$g_config_arr[$logName],$logName);
        }
        return self::$logPool[$logName];
    }

    /**
     * Logger 构造函数，不能直接new Logger()
     * @param string $logName
     * @param array $config
     * @throws Exception
     */
    protected function __construct($config = array(),$logName='default'){
        $this->isLoging = (isset($config['isLogging']))?$config['isLogging']:self::$g_config_arr['default']['isLogging'];
        $this->logName = (empty($logName) || !is_string($logName))?'default':$logName;
        $this->basePath = (isset($config['basePath']) && !empty($config['basePath']))?$config['basePath']:self::$g_basePath;
        $this->suffix = isset($config['suffix'])?$config['suffix']:self::$g_config_arr['default']['suffix'];
        $this->level = (isset($config['level']) && is_array($config['level']))?$config['level']:self::$g_config_arr['default']['level'];
        $this->mode = isset($config['mode'])?$config['mode']:self::$g_config_arr['default']['mode'];
        $this->frequency = isset($config['frequency'])?$config['frequency']:self::$g_config_arr['default']['frequency'];

        $this->basePath = rtrim($this->basePath,"\\/");
        if(!is_dir($this->basePath)){
            if( !mkdir($this->basePath,0755,true)){
                throw new Exception("create directory fail:".$this->basePath);
            }
        }
        $this->logFilePath = $this->basePath.DIRECTORY_SEPARATOR.$this->logName.'.'.$this->suffix;
        switch($this->frequency){
            case self::LOG_FREQUENCY_MINUTE: $this->timeLength = 12;break;
            case self::LOG_FREQUENCY_HOUR: $this->timeLength = 10;break;
            case self::LOG_FREQUENCY_DAY: $this->timeLength = 8;break;
            case self::LOG_FREQUENCY_MONTH: $this->timeLength = 6;break;
            default:
                $this->timeLength = -1;
        }
    }


    /**
     * 受保护的写日志方法
     * @param $filePath
     * @param $content
     * @return bool|int
     */
    protected function write($filePath,$content){
        $return_value = false;
        $content = $content."\n";
        switch($this->mode){
            case self::LOG_MODE_NORMAL:{
                $return_value = file_put_contents($filePath,$content,LOCK_EX | FILE_APPEND);
                $return_value = (int)$return_value > 0 ?true:false;
            }break;
            case self::LOG_MODE_PRINT:{
                echo $this->logName,':',$content;
                $return_value = true;
            }break;
            case self::LOG_MODE_BOTH:{
                echo $this->logName,':',$content;
                file_put_contents($filePath,$content,LOCK_EX | FILE_APPEND);
                $return_value = (int)$return_value > 0 ?true:false;
            }break;
        }
        return $return_value;
    }


    /**
     * 原始记录日志函数
     * @param $content
     * @param int $level
     * @return bool|int
     */
    public function log($content,$level = self::LOG_LEVEL_INFO){
        if(!self::$g_isLogging || !$this->isLoging || !in_array($level,$this->level)){
            return false;
        }

        if(!is_string($content)){
            if(is_array($content)){
                $content = json_encode($content,JSON_UNESCAPED_UNICODE);
            }else{
                $content = serialize($content);
            }
        }

        $logTime = time();        //记录日志时间

        // 检测是否需要对日志文件进行重命名
        if($this->timeLength > 0){
            $fileCreateTime = @filectime($this->logFilePath);
            if($fileCreateTime){
                $logTimeFormat = substr(@date('YmdHis',$logTime),0,$this->timeLength);
                $createTimeFormat = substr(@date('YmdHis',$fileCreateTime),0,$this->timeLength);
                if(strcmp($logTimeFormat,$createTimeFormat) !== 0){
                    $newLogFilePath = $this->basePath.DIRECTORY_SEPARATOR.$this->logName.'_'.$createTimeFormat.'.'.$this->suffix;
                    rename($this->logFilePath,$newLogFilePath);
                }
            }
        }

        //构造日志记录格式
        switch($level){
            case self::LOG_LEVEL_ERROR:{
                $content = sprintf('[%s %s] %s',@date('Y-m-d H:i:s',$logTime),'error',$content);
            }break;
            case self::LOG_LEVEL_WARN:{
                $content = sprintf('[%s %s] %s',@date('Y-m-d H:i:s',$logTime),'warn',$content);
            }break;
            default:
                $content = sprintf('[%s %s] %s',@date('Y-m-d H:i:s',$logTime),'info',$content);
        }

        //记录日志
        return $this->write($this->logFilePath,$content);
    }

    /**
     * 记录错误信息
     * @param $content
     * @return bool|int
     */
    public function error($content){
        return $this->log($content,self::LOG_LEVEL_ERROR);
    }

    /**
     * 记录警告信息
     * @param $content
     * @return bool|int
     */
    public function warn($content){
        return $this->log($content,self::LOG_LEVEL_WARN);
    }
}

//测试
if(strtolower(PHP_SAPI) == 'cli' && isset($argv) && basename(__FILE__) == basename($argv[0])){
    $config = array(  // 日志配置文件数组,default是默认配置项
        'test1/a' => array(
            'logName' => 'bb'
        ),
    );

    Logger::loadConfig($config);
    $log = Logger::factory('bb');
    $data =  $log->error('hello 哈哈1中文');
    echo $data,"\n";
}