<?php
/**
 * User: rudy
 * Date: 2016/03/15 14:15
 *
 *  默认配置文件
 *
 */

return array(
    'name' => 'defaultSpider',
    'debug' => false,
    'logPath' => dirname(dirname(dirname(__FILE__))).DIRECTORY_SEPARATOR.'log'.DIRECTORY_SEPARATOR,
    'logs' => array(
        'system' => array(
            'logName' => 'system',
        )
    ),
    'downloader' => array(
        'className' => 'framework\task\DownloadTask',
        'windowSize' => 50,
    ),
    'request' => array(
        'maxRepeat' => 5,
        'timeOut' => 300,
        'requestQueue' => 'framework\queue\LocalRequestQueue'
    ),
    'tasks' => array(
        'common' => array(
            'framework\task\DelayTimer'=>array()
        )
    ),
    'component' => array(
        // 填写相应组件
    ),
);