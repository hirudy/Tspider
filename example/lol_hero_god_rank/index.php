<?php
/**
 * User: rudy
 * Date: 2016/02/29 17:03
 *
 *  程序执行入口
 *
 */

include dirname(__FILE__).DIRECTORY_SEPARATOR.'framework/TSpider.php';


\framework\TSpider::init('application');

\framework\TSpider::run();
