<?php
/**
 *
 * logger tool
 *
 * @author: rudy
 * @date: 2016/09/12
 */


class TLoggerException extends Exception{
}

/**
 * 操作类必要的函数
 * Interface ILoggerHandle
 */
interface ILoggerHandle{
    // 解析配置文件,看配置文件是否满足需求
    public static function parseConfig($rawConfig);

    // 不同级别日志记录函数
    public function fatal($message);
    public function error($message);
    public function warn($message);
    public function info($message);
    public function debug($message);
}

/**
 * 具体操作基类
 * Class LoggerHandle
 */
abstract class LoggerHandle implements ILoggerHandle{
    protected $isLogging;       // 当前日志开关
    protected $name;            // 名称
    protected $config;          // 配置信息

    protected $mode;            // 记录模式
    protected $level;           // 日志等级

    private static $logLevelMap = array(  // 日志等级与文字的映射表
        TLogger::LOG_LEVEL_FATAL => 'fatal',
        TLogger::LOG_LEVEL_ERROR => 'error',
        TLogger::LOG_LEVEL_WARN  => 'warn',
        TLogger::LOG_LEVEL_INFO  => 'info',
        TLogger::LOG_LEVEL_DEBUG => 'debug'
    );

    // 具体的写类
    protected abstract function write($message);

    protected function log($message, $level){
        // 是否需要记录
        if(!TLogger::$g_isLogging || !$this->isLogging || $level > $this->level){
            return false;
        }

        // 序列化消息
        if(!is_string($message)){
            if(is_array($message)){
                $message = json_encode($message,JSON_UNESCAPED_UNICODE);
            }else if (is_object($message)){
                $message = serialize($message);
            }else{
                $message = (string)$message;
            }
        }

        // 格式化消息输出
        $levelInfo = TLogger::getArrayValue(self::$logLevelMap, $level, TLogger::LOG_LEVEL_INFO);
        $message = sprintf('[%s %s] %s',@date('Y-m-d H:i:s'), $levelInfo, $message);

        return $this->write($message);
    }

    public function fatal($message){
        return $this->log($message, TLogger::LOG_LEVEL_FATAL);
    }

    public function error($message){
        return $this->log($message, TLogger::LOG_LEVEL_ERROR);
    }

    public function warn($message){
        return $this->log($message, TLogger::LOG_LEVEL_WARN);
    }

    public function info($message){
        return $this->log($message, TLogger::LOG_LEVEL_INFO);
    }

    public function debug($message){
        return $this->log($message, TLogger::LOG_LEVEL_DEBUG);
    }
}

/**
 * 控制台操作类
 * Class ConsoleHandle
 */
class ConsoleHandle extends LoggerHandle{

    public static function parseConfig($rawConfig){
        // 控制台模式日志配置格式
        $result = array(
            'name' => TLogger::getArrayValue($rawConfig, 'name', 'default', 'string'),                              // 日志名称
            'isLogging' => TLogger::getArrayValue($rawConfig, 'isLogging', TLogger::$g_isLogging, 'boolean'),       // 当前日志是否记录
            'mode' => TLogger::LOG_MODE_CONSOLE,                                                                    // 记录模式
            'level' => TLogger::getArrayValue($rawConfig, 'level', TLogger::LOG_LEVEL_DEBUG,'integer'),             // 日志等级
        );

        // 判断记录等级是否合法
        if ($result['level'] > TLogger::LOG_LEVEL_DEBUG || $result['level'] < TLogger::LOG_LEVEL_FATAL){
            throw new TLoggerException("({$result['name']}) config level set error");
        }
        return $result;
    }

    public function __construct($config){
        $this->isLogging = $config['isLogging'];
        $this->name = $config['name'];
        $this->config = $config;
        $this->mode = $config['mode'];
        $this->level = $config['level'];
    }

    protected function write($message){
        echo $message, PHP_EOL;
        return true;
    }
}

/**
 * 文件操作类
 * Class FileHandle
 */
class FileHandle extends LoggerHandle{
    protected $basePath;        // 存储路径
    protected $frequency;       // 切割日志方式
    protected $suffix = '.log';  // 日志文件后缀

    private $logFilePath;       // 完整的日志路径
    private $timeLength;        // 存储时间长度

    public static function parseConfig($rawConfig){
        // 文件模式日志配置格式化
        $result = array(
            'name' => TLogger::getArrayValue($rawConfig, 'name', 'default', 'string'),                              // 日志名称
            'isLogging' => TLogger::getArrayValue($rawConfig, 'isLogging', TLogger::$g_isLogging, 'boolean'),       // 当前日志是否记录
            'mode' => TLogger::LOG_MODE_FILE,                                                                       // 记录模式
            'level' => TLogger::getArrayValue($rawConfig, 'level', TLogger::LOG_LEVEL_DEBUG,'integer'),             // 日志等级

            'basePath' => TLogger::getArrayValue($rawConfig, 'basePath', TLogger::$g_basePath, 'string'),           // 当前日志的记录文件根目录
            'frequency' => TLogger::getArrayValue($rawConfig, 'frequency', TLogger::LOG_FREQUENCY_NONE, 'integer')  // 切割日志方式
        );

        // 判断记录等级是否合法
        if ($result['level'] > TLogger::LOG_LEVEL_DEBUG || $result['level'] < TLogger::LOG_LEVEL_FATAL){
            throw new TLoggerException("({$result['name']}) config level set error");
        }

        // 判断记录日志切割是否合法
        if ($result['frequency'] > TLogger::LOG_FREQUENCY_MONTH || $result['frequency'] < TLogger::LOG_FREQUENCY_NONE){
            throw new TLoggerException("({$result['name']}) config frequency set error");
        }

        // 初始化日志记录根目录
        $result['basePath'] = rtrim($result['basePath'], DIRECTORY_SEPARATOR);
        if(!is_dir($result['basePath'])){
            if( !mkdir($result['basePath'], 0775, true)){
                throw new TLoggerException("({$result['name']}) config create directory fail:".$result['basePath']);
            }
        }

        return $result;
    }

    public function __construct($config){
        $this->isLogging = $config['isLogging'];
        $this->name = $config['name'];
        $this->config = $config;
        $this->mode = $config['mode'];
        $this->level = $config['level'];
        $this->frequency = $config['frequency'];
        $this->basePath = $config['basePath'];

        $this->logFilePath = $this->basePath.DIRECTORY_SEPARATOR.$this->name;
        switch($this->frequency){
            case TLogger::LOG_FREQUENCY_MINUTE: $this->timeLength = 12;break;
            case TLogger::LOG_FREQUENCY_HOUR: $this->timeLength = 10;break;
            case TLogger::LOG_FREQUENCY_DAY: $this->timeLength = 8;break;
            case TLogger::LOG_FREQUENCY_MONTH: $this->timeLength = 6;break;
            default:
                $this->timeLength = -1;
        }
    }

    protected function write($message){
        $logTime = time();
        // 检测是否需要对日志文件进行重命名
        if($this->timeLength > 0){
            $fileCreateTime = @filectime($this->logFilePath.$this->suffix);
            if($fileCreateTime){
                $logTimeFormat = substr(@date('YmdHis',$logTime),0,$this->timeLength);
                $createTimeFormat = substr(@date('YmdHis',$fileCreateTime),0,$this->timeLength);
                if(strcmp($logTimeFormat,$createTimeFormat) !== 0){
                    $newLogFilePath = $this->logFilePath.'_'.$createTimeFormat.$this->suffix;
                    rename($this->logFilePath.$this->suffix,$newLogFilePath);
                }
            }
        }
        $message .= PHP_EOL;
        // 写入日志文件中
        return file_put_contents($this->logFilePath.$this->suffix,$message,LOCK_EX | FILE_APPEND);
    }
}


/**
 * 日志管理类
 * Class TLogger
 */
class TLogger{
    const VERSION = '1.0.1';            // 日志组件版本号

    const LOG_MODE_CONSOLE = 'Console'; // 记录模式,记录到控制台
    const LOG_MODE_FILE    = 'File';    // 记录模式,记录到文件
    const LOG_MODE_TCP     = 'Tcp';     // 记录模式,记录到TCP
    const LOG_MODE_UDP     = 'Udp';     // 记录模式,记录到UDP

    const LOG_FREQUENCY_NONE   = 0; // 切割日志方式,存放的日志文件始终只有一个文件,形如 default.log
    const LOG_FREQUENCY_MINUTE = 1; // 切割日志方式,存放日志每隔一分钟换一个，形如 default_201601192357.log
    const LOG_FREQUENCY_HOUR   = 2; // 切割日志方式,存放日志每隔一小时换一个，形如 default_2016011923.log
    const LOG_FREQUENCY_DAY    = 3; // 切割日志方式,存放日志每隔一天换一个，形如 default_20160119.log
    const LOG_FREQUENCY_MONTH  = 4; // 切割日志方式,存放日志每隔一月换一个，形如 default_201601.log

    const LOG_LEVEL_FATAL = 0;  // 日志等级,严重错误
    const LOG_LEVEL_ERROR = 1;  // 日志等级,错误
    const LOG_LEVEL_WARN  = 2;  // 日志等级,警告
    const LOG_LEVEL_INFO  = 3;  // 日志等级,信息记录
    const LOG_LEVEL_DEBUG = 4;  // 日志等级,调试

    public static $g_isLogging = true;  // 总开关，是否记录日志
    public static $g_basePath = '/data/log'; // 默认存储路径

    protected static $g_config_arr = array();   // 日志配置文件数组
    protected static $logPool = array();        // 日志对象池子


    /**
     * 获取数组中某个值
     * @param array  $arr 数组
     * @param string $key key值
     * @param string $default 默认值
     * @param string $valueType 不为空,检查变量数据类型
     * @return mixed
     */
    public static function getArrayValue(Array $arr, $key, $default='', $valueType=''){
        $result = $default;
        if (isset($arr[$key])){
            $result = $arr[$key];
        }

        if (!empty($valueType) && gettype($result)!= $valueType){
            $result = $default;
        }

        return $result;
    }

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
     * @param array $arr
     * @param string $name
     * @return bool
     * @throws TLoggerException
     */
    public static function loadOneConfig(Array $arr,$name=''){
        $name = self::getArrayValue($arr,'name',$name,"string");
        if (empty($name)){
            throw new TLoggerException("no config name");
        }
        $arr['name'] = $name;

        $handle = self::getArrayValue($arr,'mode',self::LOG_MODE_FILE,'string');
        $handle = $handle.'Handle';
        if (!class_exists($handle,false) || get_parent_class($handle) != 'LoggerHandle'){
            throw new TLoggerException("({$name}) logger handle '{$handle}' not defined or is not sub-class of LoggerHandle");
        }

        $arr = call_user_func(array($handle,'parseConfig'),$arr);
        $arr['handleClass'] = $handle;
        if (empty($arr)){
            throw new TLoggerException("({$name}) logger handle {$$handle} config parse error");
        }

        self::$g_config_arr[$name] = $arr;
        if (isset(self::$logPool[$name])){
            unset(self::$logPool[$name]);
        }
        return true;
    }


    /**
     * 根据日志名称，获取一个日志实例
     * @param string $name 配置名称
     * @param bool $isNew 是否生成一个新的日志对象
     * @return object logger
     * @throws Exception
     */
    public static function getLogger($name='default',$isNew=false){
        if(!isset(self::$logPool[$name]) || $isNew == true){
            // 是否加载了默认配置文件
            if (!isset(self::$g_config_arr['default'])){
                self::loadOneConfig(array(
                    'name' => 'default',                        // 日志名称
                    'isLogging' => true,                        // 当前日志是否记录
                    'basePath' => self::$g_basePath,            // 当前日志的记录根目录,没有,默认全局目录:g_basePath
                    'mode' => self::LOG_MODE_FILE,              // 记录模式
                    'level' => self::LOG_LEVEL_DEBUG,           // 日志等级
                    'frequency' => self::LOG_FREQUENCY_NONE,    // 切割日志方式
                ));
            }

            // 删除已有的logger对象
            if(isset(self::$logPool[$name])){
                unset(self::$logPool[$name]);
            }

            // 检测配置文件是否加载
            if(!isset(self::$g_config_arr[$name])){
                throw new TLoggerException("Make sure that the log configuration which name is '{$name}' is loaded successfully");
            }

            // 根据配置文件实例化logger,并保存到logger单例数组中
            $config = self::$g_config_arr[$name];
            $reflector = new ReflectionClass($config['handleClass']);
            $logger = $reflector->newInstance($config);
            self::$logPool[$name] = $logger;
        }

        return self::$logPool[$name];
    }
}

//测试
if(strtolower(PHP_SAPI) == 'cli' && isset($argv) && basename(__FILE__) == basename($argv[0])){
    $config = array(  // 日志配置文件数组,default是默认配置项
        'name' => 'test',
        'level' => TLogger::LOG_LEVEL_INFO,
        'frequency' => TLogger::LOG_FREQUENCY_MINUTE
    );
    TLogger::$g_basePath = __DIR__.DIRECTORY_SEPARATOR.'log';
    TLogger::loadOneConfig($config);

    $logger = TLogger::getLogger("test");
    $logger->debug("this is debug info ");
    $logger->info(array("is","info","recode"));
    $logger->warn(21);
    $logger->error("error info ");
    $logger->fatal($logger);
}