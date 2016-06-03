<?php

/**
 * User: rudy
 * Date: 2016/03/15 14:09
 *
 *  爬虫入口
 *
 */
namespace framework;

use \Common;
use \Exception;
use framework\base\Logger;
use framework\base\Request;
use framework\component\Component;
use framework\coroutine\TaskScheduler;
use framework\queue\RequestQueue;
use framework\task\AddRequestTask;

class TSpider{

    const VERSION = '1.0.3';

    public static $spiderName = ''; //爬虫名称

    public static $basePath;   // 全局path
    public static $frameworkBasePath; // 框架path
    public static $applicationPath; // application path
    public static $protectedName; // 受保护的文件夹名称

    public static $isDebug = false; // 是否是调试模式

    public static $config = array(); // 所有的配置项

    public static $log ; // 日志
    public static $taskScheduler = null; // 调度器
    public static $downloader = null; // 下载器
    public static $requestQueue = null; // 请求队列对象
    public static $component = null; // 组件对象

    public static $startCrawlTime = 0; // 开始爬取时间

    public static $taskGroup = ''; // 运行中的任务分组名称

    public function __construct($protectedName='protected'){
        $this->init($protectedName);
    }

    public static function init($protectedName){
        self::$startCrawlTime = time();
        self::$protectedName  = $protectedName;
        self::$frameworkBasePath = dirname(__FILE__);
        self::$basePath = dirname(self::$frameworkBasePath);
        self::$applicationPath = self::$basePath.DIRECTORY_SEPARATOR.self::$protectedName;

        ini_set('date.timezone','Asia/Shanghai');
        spl_autoload_register('\framework\TSpider::autoLoadFile');

        self::checkEnvironment();

        // 解析命令
        self::parseCommand();

        // 导入引用的三方扩展
        self::searchAndInclude(self::$frameworkBasePath.'/extension');
        self::searchAndInclude(self::$applicationPath.'/extension');
        
        // 加载配置文件
        $default_config = include self::$frameworkBasePath.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'default.php';
        $user_config_path = self::$applicationPath.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'main.php';
        if(!is_file($user_config_path)){
            throw new \Exception("config file not found:{$user_config_path}",-1);
        }
        $user_config = include $user_config_path;
        if(!is_array($user_config)){
            throw new \Exception("config file not load error:{$user_config_path}",-1);
        }

        self::$config = array_merge($default_config,$user_config);

        self::$spiderName = Common::getArrayValue(self::$config,'name','defaultSpider');
        self::$isDebug = Common::getArrayValue(self::$config,'debug',false);

        if(self::$isDebug){
            ini_set("display_errors", "on");
            error_reporting(E_ALL^E_STRICT);
        }

        // 加载logger
        Logger::loadOneConfig(array('logName'=>'system'));
        Logger::$g_basePath = self::$config['logPath'];
        Logger::loadConfig(self::$config['logs']);
        self::$log = Logger::factory('system');

        // 设置request相关
        Request::$maxRepeat = self::$config['request']['maxRepeat'];
        Request::$timeOut = self::$config['request']['timeOut'];
        self::$requestQueue = new self::$config['request']['requestQueue']();
        if(!(self::$requestQueue instanceof RequestQueue)){
            $temp = self::$config['request']['requestQueue'];
            throw new \Exception("requestQueue error:{$temp}",-1);
        }

        // 加载必须任务,初始化任务调度器,添加下载器任务
        self::$taskScheduler = new TaskScheduler();
        if(!isset(self::$config['downloader'])){
            throw new \Exception('there is no download class config');
        }
        $downloaderClass = self::$config['downloader']['className'];
        self::$downloader = new $downloaderClass();
        self::$downloader->windowSize = self::$config['downloader']['windowSize'];
        self::$taskScheduler->addTask(self::$downloader);

        // 添加请求队列读取任务,以及自定义任务,all分组表示运行所有
        $addRequestTask = new AddRequestTask(self::$downloader);
        self::$taskScheduler->addTask($addRequestTask);
        if(self::$taskGroup == 'all'){
            foreach(self::$config['tasks'] as $key=>$value){
                if(is_array($value)){
                    foreach ($value as $className=>$params){
                        $tempTask = new $className();
                        self::$taskScheduler->addTask($tempTask);
                    }
                }
            }
        }else{
            if(isset(self::$config['tasks']['common'])){
                foreach(self::$config['tasks']['common'] as $key=>$value){
                    $tempTask = new $key();
                    self::$taskScheduler->addTask($tempTask);
                }
            }

            if(isset(self::$config['tasks'][self::$taskGroup]) && self::$taskGroup != 'common'){
                foreach(self::$config['tasks'][self::$taskGroup] as $className=>$params){
                    $tempTask = new $className();
                    self::$taskScheduler->addTask($tempTask);
                }
            }else{
                $taskGroup = self::$taskGroup;
                exit("taskGroup not found:{$taskGroup}\n");
            }
        }
        
        // 初始化组件配置文件
        if(isset(self::$config['component']) && is_array(self::$config['component'])){
            Component::$config = self::$config['component'];
        }
        self::$component = new Component();

        // 设置进程名称
        $title = self::$spiderName;
        $taskGroup = self::$taskGroup;
        $startTime = date('Y-m-d H:i:s',self::$startCrawlTime);
        self::setProcessTitle("TSpider:{$title}-{$taskGroup} startTime:{$startTime}");
    }

    public static function autoLoadFile($rawName){
        $name = str_replace('\\', DIRECTORY_SEPARATOR ,$rawName);
        $classFile = self::$basePath . DIRECTORY_SEPARATOR . $name . '.php';

        if(is_file($classFile)){
            if(!class_exists($rawName,true)){
                $rel = require $classFile;
                return $rel;
            }
        }else{
            throw new Exception("auto load File error:{$classFile}",-1);
        }
        return false;
    }

    /**
     * 解析接收的命令
     */
    public static function parseCommand(){
        if(isset($_SERVER['argv']) && count($_SERVER['argv']) >=2){
            $command = $_SERVER['argv'][1];
            if(strlen($command)){
                self::$taskGroup = $command;
            }else{
                self::$taskGroup = '';
            }
        }else{
            self::$taskGroup = 'all';
        }
        echo 'taskGroup:',self::$taskGroup,"\n";
    }


    /**
     * 检测运行环境
     */
    public static function checkEnvironment(){
        echo 'PHP-version:',PHP_VERSION,' TSpider-version:',self::VERSION,' start-time:',date('Y-m-d H:i:s',self::$startCrawlTime),"\n";

        if(version_compare(PHP_VERSION,'5.5.0','<=')){
            exit('php version must greater than 5.5.0');
        }

        if(substr(php_sapi_name(), 0, 3) != 'cli'){
            exit('this program must be running in cli mode ');
        }

        if(extension_loaded('posix')){
            $userInfo = posix_getpwuid(posix_getuid());
            echo 'pid:',posix_getpid()," ",'running-user:',$userInfo['name'],"\n";
        }
    }

    /**
     * 设置进程名称
     * @param $title
     */
    public static function setProcessTitle($title)
    {
        if (function_exists('cli_set_process_title')) {
            @cli_set_process_title($title);
        } elseif (extension_loaded('proctitle') && function_exists('setproctitle')) {
            @setproctitle($title);
        }
    }

    /**
     * 导入某个文件夹中的所有文件
     * @param $rootPath
     */
    public static function searchAndInclude($rootPath){
        if(is_dir($rootPath)){
            $rootPath = rtrim($rootPath,'/');
            $files = glob($rootPath.'/*');
            foreach ($files  as $file){
                if(is_dir($file)){
                    self::searchAndInclude($file);
                }else{
                    include_once $file;
                }
            }
        }
    }

    public static function run(){
        self::$taskScheduler->run();
    }
}