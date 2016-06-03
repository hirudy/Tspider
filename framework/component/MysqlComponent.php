<?php
/**
 *
 * 封装mysql操作类组件
 *
 * @author: rudy
 * @date: 2016/05/09
 */

namespace framework\component;

use \TMysql;

class MysqlComponent extends Component{
    protected $isLoadConfig = false;
    
    public function getInstance(){
        if(!$this->isLoadConfig){
            $this->oneConfig['connectionName'] = $this->componentName;
            TMysql::loadOneConfig($this->oneConfig);
            $this->isLoadConfig = true;
        }
        return TMysql::getConnection($this->componentName);
    }
}