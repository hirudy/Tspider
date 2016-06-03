<?php
/**
 * User: rudy
 * Date: 2016/02/29 20:10
 *
 *  解析器基类
 *
 */

namespace framework\base;

interface Iworker{
    public function beforeParse();
    public function parse(Request $request,Response $response);
    public function afterParse();
}

class BaseWorker implements Iworker{
    public static $statisticsParserNum = 0;
    public static $statisticsSaveNum = 0;
    public static $statisticsParserErrorNum = 0;
    public static $statisticsSaveErrorNum = 0;
    
    public function parse(Request $request,Response $response){
        echo "baseWorker parse {$response->code} : {$request->url} \n";
    }

    /**
     * 解析前hook
     * @return bool
     */
    public function beforeParse(){
        self::$statisticsParserNum ++;
        return true;
    }

    /**
     * 解析后hook
     */
    public function afterParse(){
        // TODO: Implement afterParse() method.
    }
}