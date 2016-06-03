<?php

/**
 * User: rudy
 * Date: 2016/04/14 18:38
 *
 *  具体的业务处理，请求处理
 *
 */
namespace application\worker;

use \Common;
use framework\base\BaseWorker;
use framework\base\Logger;
use framework\base\Request;
use framework\base\Response;
use framework\TSpider;

class ExternalStatisticsWorker extends BaseWorker{

    const CHART_PATCH_WIN = 'patch_win'; // 版本胜率图表
    const CHART_PATCH_PLAY = 'patch_play'; // 版本使用率图表
    const CHART_GAME_LENGTH_WIN = 'game_length_win'; // 游戏时长胜率图表
    const CHART_GAME_PLAY_WIN = 'game_play_win'; // 游戏局数胜率图表

    const ITEM_FREQUENT = 'item_frequent'; // 物品,使用频繁推荐
    const ITEM_WIN = 'item_win'; // 物品,使用胜率推荐

    protected $errorLog = null;
    protected $log = null;

    protected  $lastError = '';

    public function __construct(){
        $this->errorLog = Logger::factory('errorParse');
        $this->log = Logger::factory('worker');
    }


    /**
     * 重写解析方法
     * @param Request $request
     * @param Response $response
     */
    public function parse(Request $request,Response $response){
        if($response->code != 200){
            $this->errorLog->log($request->url."| ".$response->code." | ".$response->getError().' | '.json_encode($request->extData['championInfo']));
            return null;
        }

        $extractResult = $this->extract($response->getData(),$request);
        if($extractResult == false){
            // 重新下载
            if(!$request->redownload()){
                // 最终下载失败的
                $url = substr($request->url,-35);
                TSpider::$component->sms->sendMessage("parse error:$url");
                $this->errorLog->error('parse error:'.$request->url.'|'.$response->code.'|'.$response->getError().'|'.$this->lastError);
            }
            return;
        }
        
        $this->log->log($request->url.' | '.$request->getRepeatNum());
        $this->save($extractResult,$request);
    }

    /**
     * 提取数据
     * @param $data
     * @return array|bool
     */
    public function extract($data,$rawRequest){
        $response = array();
        $dom = str_get_html($data);
        if(!$this->extractItemUse($dom,$response,$rawRequest)){
            return false;
        }
        
        if(!$this->extractStatisticsChart($dom,$response,$rawRequest)){
            return false;
        }

        return $response;
    }

    /**
     * 获取物品的数据,结果写入response中
     * @param $dom
     * @param $response
     * @return bool
     */
    protected function extractItemUse($dom,&$response,$rawRequest){
        do{
            // 解析装备使用推荐
            $nodeList = $dom->find('div.col-md-7 div.build-wrapper');
            if(!is_array($nodeList) || count($nodeList) != 2){
                $this->lastError ='wrapper parse error';
                break;
            }
            $itemData = array();
            $itemUseData = array();
            foreach ($nodeList as $node){
                // 获取itemID
                $img_node_list = $node->find('a img');
                $tempImg = array();
                foreach ($img_node_list as $img_node){
                    $src = $img_node->src;
                    if (preg_match('#(\d+)\.png#',$src,$match)){
                        $itemId = (int)$match[1];
                        if($itemId > 0){
                            $tempImg[] = $itemId;
                        }
                    }
                }
                if(count($tempImg) == 6){
                    $itemData[] = $tempImg;
                }

                // 获取item使用情况
                $use_node_list = $node->find('div.build-text strong');
                $tempUse = array();
                foreach ($use_node_list as $use_node){
                    $text = (float)$use_node->innertext;
                    if($text > 0){
                        $tempUse[] = $text;
                    }
                }

                if(count($tempUse) == 2){
                    $itemUseData[] = $tempUse;
                }
            }
            if(count($itemData) != 2 && count($itemUseData) != 2){
                $this->lastError = 'item win rate num error';
                $this->errorLog->error($this->lastError.'|'.$rawRequest->url);
//                break;
            }

            $totalData = array();
            foreach ($itemData as $key=>$value){
                if($key == 0){
                    $name = self::ITEM_FREQUENT;
                }elseif($key == 1){
                    $name = self::ITEM_WIN;
                }else{
                    break;
                }
                $totalData[$name]['item'] = $value;
                $totalData[$name]['win_rate'] = $itemUseData[$key][0];
                $totalData[$name]['game_count'] = $itemUseData[$key][1];
            }

            // 结果写入返回
            $response['item_data'] = $totalData;
            return true;
        }while(false);
        return false;
    }

    /**
     * 提取4个统计图表数据,结果写入response中
     * @param $dom
     * @param $response
     * @return bool
     */
    protected function extractStatisticsChart($dom,&$response,$rawRequest){
        $script_dom_list = $dom->find('script');
        $data_text = '';
        foreach ($script_dom_list as $script_dom){
            $text = $script_dom->innertext;
            if(stripos($text,'matchupData.championData') !== false) {
                $data_text = $text;
                break;
            }
        }

        do{
            if(empty($data_text)){
                $this->lastError = 'no js data';
                break;
            }
            // 解析general_role
            if(!preg_match('#matchupData\.generalRole *= *(\{.+?\});#',$data_text,$match)){
                $this->lastError = 'preg_match error:generalRole';
                break;
            }
            $data_general_role = json_decode($match[1],true);
            if(!is_array($data_general_role)){
                $this->lastError = 'json_decode error:generalRole';
                break;
            }
            
            // 解析champion_data
            if(!preg_match('#matchupData\.championData *= *(\{.+?\});#',$data_text,$match)){
                $this->lastError = 'preg_match error:championData';
                break;
            }
            $data_champion_data = json_decode($match[1],true);
            if(!is_array($data_general_role)){
                $this->lastError = 'json_decode error:championData';
                break;
            }
            
            // 解析patch_history
            if(!preg_match('#matchupData\.patchHistory *= *(\[.+?\]);#',$data_text,$match)){
                $this->lastError = 'preg_match error:patchHistory';
                break;
            }
            $data_patch_history = json_decode($match[1],true);
            if(!is_array($data_general_role)){
                $this->lastError = 'json_decode error:patchHistory';
                break;
            }

            $result = array();

            $championName = $rawRequest->extData['championInfo']['name'];
            // 添加patch_win
            $temp_arr = array('target'=>array('y_prefix'=>'','y_suffix'=>'%'),'series'=>array());
            $x_show = $data_patch_history;
            $serie1 = array('name'=>'英雄平均胜率','data'=>array());
            $serie2 = array('name'=>$championName,'data'=>array());
            $y_2 = isset($data_champion_data['patchWin'])?$data_champion_data['patchWin']:array();
            if(count($x_show) !== count($y_2)){
                $this->lastError = 'patch_win:y2 num error';
                break;
            }
            foreach ($x_show as $key=>$value){
                $serie1['data'][] = array($value,50);
                $serie2['data'][] = array($value,(float)$y_2[$key]);
            }
            $temp_arr['series'][] = $serie1;
            $temp_arr['series'][] = $serie2;
            $result[self::CHART_PATCH_WIN] = $temp_arr;

            // 添加patch_play
            $temp_arr = array('target'=>array('y_prefix'=>'','y_suffix'=>'%'),'series'=>array());
            $x_show = $data_patch_history;
            $serie1 = array('name'=>'英雄平均出场率','data'=>array());
            $serie2 = array('name'=>$championName,'data'=>array());
            $y_1 = isset($data_general_role['patchPlay'])?$data_general_role['patchPlay']:array();
            $y_2 = isset($data_champion_data['patchPlay'])?$data_champion_data['patchPlay']:array();
            if(count($x_show) !== count($y_2) || count($x_show) !== count($y_1)){
                $this->lastError = 'patch_play:y1/y2 num error';
                break;
            }
            foreach ($x_show as $key=>$value){
                $serie1['data'][] = array($value,(float)$y_1[$key]);
                $serie2['data'][] = array($value,(float)$y_2[$key]);
            }
            $temp_arr['series'][] = $serie1;
            $temp_arr['series'][] = $serie2;
            $result[self::CHART_PATCH_PLAY] = $temp_arr;
            
            // 添加game_length_win
            $temp_arr = array('target'=>array('y_prefix'=>'','y_suffix'=>'%'),'series'=>array());
            $x_show = array('0-25','25-30','30-35','35-40','40+');
            $serie1 = array('name'=>'英雄平均胜率','data'=>array());
            $serie2 = array('name'=>$championName,'data'=>array());
            $y_2 = isset($data_champion_data['gameLength'])?$data_champion_data['gameLength']:array();
            if(count($x_show) !== count($y_2)){
                $this->lastError = 'game_length_win:y2 num error';
                break;
            }
            foreach ($x_show as $key=>$value){
                $serie1['data'][] = array($value,50);
                $serie2['data'][] = array($value,(float)$y_2[$key]);
            }
            $temp_arr['series'][] = $serie1;
            $temp_arr['series'][] = $serie2;
            $result[self::CHART_GAME_LENGTH_WIN] = $temp_arr;

            // 添加game_play_win
            $temp_arr = array('target'=>array('y_prefix'=>'','y_suffix'=>'%'),'series'=>array());
            $x_show = array('1-5','5-15','15-50','50-125','125+');
            $serie1 = array('name'=>'英雄平均胜率','data'=>array());
            $serie2 = array('name'=>$championName,'data'=>array());
            $y_1 = array_fill(0,count($x_show),end($data_champion_data['patchWin']));
            $y_2 = isset($data_champion_data['experienceRate'])?$data_champion_data['experienceRate']:array();
            if(count($x_show) !== count($y_2)){
                $this->lastError = 'game_length_win:y2 num error';
                break;
            }
            foreach ($x_show as $key=>$value){
                $serie1['data'][] = array($value,(float)$y_1[$key]);
                $serie2['data'][] = array($value,(float)$y_2[$key]);
            }
            $temp_arr['series'][] = $serie1;
            $temp_arr['series'][] = $serie2;
            $result[self::CHART_GAME_PLAY_WIN] = $temp_arr;

            $response['statistics_chart'] = $result;
            return true;
        }while(false);

        return false;
    }

    /**
     * 存储经过提取后的数据
     * @param $extractData
     * @throws \Exception
     */
    public function save($extractData,$request){
        try{
            $fields = array(
                'version' => TSpider::$startCrawlTime,
                'champion_id' => (int)$request->extData['championInfo']['tgp_id'],
                'item_frequent' => json_encode(Common::getArrayValue($extractData['item_data'],self::ITEM_FREQUENT,array())),
                'item_win' => json_encode(Common::getArrayValue($extractData['item_data'],self::ITEM_WIN,array())),
                'chart_patch_win' => json_encode(Common::getArrayValue($extractData['statistics_chart'],self::CHART_PATCH_WIN,array())),
                'chart_patch_play' => json_encode(Common::getArrayValue($extractData['statistics_chart'],self::CHART_PATCH_PLAY,array())),
                'chart_game_length_win' => json_encode(Common::getArrayValue($extractData['statistics_chart'],self::CHART_GAME_LENGTH_WIN,array())),
                'chart_game_play_win' => json_encode(Common::getArrayValue($extractData['statistics_chart'],self::CHART_GAME_PLAY_WIN,array())),
            );
            $rel = TSpider::$component->dbSnatchLol->insert('hero_external_statistics',$fields);
            if($rel <= 0){
                throw new \Exception('insert error:',-1);
            }
            self::$statisticsSaveNum ++;
        }catch(\Exception $e){
            $temp = substr($e->getMessage(),-35);
            TSpider::$component->sms->sendMessage("save error:{$temp}");
            $this->errorLog->error('save error:'.$e->getMessage().'|'.json_encode($extractData));
        }
    }
}