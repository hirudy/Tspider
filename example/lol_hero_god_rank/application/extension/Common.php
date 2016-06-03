<?php
/**
 * User: rudy
 * Date: 2016/03/16 17:55
 *
 *  常用功能
 *
 */
namespace application\extension;

class Common {

    /**
     * 安全地获取数组的值
     * @param $arr array 数组
     * @param $key String 键名
     * @param string $default 默认值
     * @return string
     */
    public static function getArrayValue($arr,$key,$default=''){
        if(isset($arr[$key])){
            return $arr[$key];
        }
        return $default;
    }

    /**
     * 获取可阅读的内存使用情况
     * @return string
     */
    public static function getMemoryUsedSizeShow(){
        $memory_byte = memory_get_usage();
        if($memory_byte < 1024){
            return '0K';
        }else if($memory_byte < 1048576){
            $temp = round($memory_byte/1024,2);
            return "{$temp}K";
        }else{
            $temp = round($memory_byte/1048576,2);
            return "{$temp}M";
        }
    }
}