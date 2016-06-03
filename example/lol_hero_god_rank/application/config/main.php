<?php
/**
 * User: rudy
 * Date: 2016/03/15 14:14
 *
 *  爬取配置文件
 *
 */

return array(
    'name' => 'lolHeroGodRankSpider',
    'logs' => array(
        'beforeDownload' => array(
            'logName' => 'beforeDownload',
            'frequency' => \framework\base\Logger::LOG_FREQUENCY_DAY,
        ),
        'afterDownload' => array(
            'logName' => 'afterDownload',
            'frequency' => \framework\base\Logger::LOG_FREQUENCY_DAY,
        ),
        'worker' => array(
            'logName' => 'worker',
        ),
        'errorDownload' => array(
            'logName' => 'errorDownload',
            'mode' => \framework\base\Logger::LOG_MODE_BOTH
        ),
        'errorParse' => array(
            'logName' => 'errorParse',
            'mode' => \framework\base\Logger::LOG_MODE_BOTH
        ),
        'common' => array(
            'logName' => 'common',
            'mode' => \framework\base\Logger::LOG_MODE_BOTH,
        ),
        'monitor' => array(
            'logName' => 'monitor',
            'mode' => \framework\base\Logger::LOG_MODE_BOTH,
            'frequency' => \framework\base\Logger::LOG_FREQUENCY_DAY,
        ),
    ),
    'request' => array(
        'maxRepeat' => 3,
        'timeOut' => 300,
        'requestQueue' => 'framework\queue\LocalRequestQueue'
    ),
    'downloader' => array(
        'className' => 'application\task\MonitorDownloadTask',
        'windowSize' => 100,
    ),
    'tasks' => array(
        'common' => array(
            'application\task\MonitorTimer' => array(),
        ),
        'hero_god' => array(
            'application\task\HeroGodRankRequestTimer' => array(),
        ),
        'external' => array(
            'application\task\ExternalStatisticsRequestTimer' => array(),
        )
    ),

    'component' => array(
        'dbSnatchLol' => array(
            'className' => 'framework\component\MysqlComponent',
            'host'=> '*.*.*.*',
            'userName' => '****',
            'password' => '*****',
            'dbName' => 'snatch_lol',
            'port' => '3306',
            'checkConnection' => true
        ),
        'sms' => array(
            'className' => 'framework\component\SmsWoquComponent',
            'serverUrl' => 'http://*.*.*.*:8080/send_sms',
            'Module' => 'spider',
            'To' => '',
            'MsgType' => '0',
        )
    ),
);
