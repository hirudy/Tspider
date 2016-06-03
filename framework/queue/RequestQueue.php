<?php

/**
 * User: rudy
 * Date: 2016/03/15 18:56
 *
 *  请求队列抽象类
 *
 */
namespace framework\queue;


use framework\base\Request;

abstract class RequestQueue{
    protected $maxCount = 200; // 队列最大容纳数.有可能超过这个数-重试时候,会直接添加到该队列中
    protected $queue = null;   // 具体的队列

    /**
     * 添加一个请求到队列中
     * @param Request $request
     * @return mixed
     */
    public abstract function add(Request $request);

    /**
     * 从队列中取出一个请求
     * @return mixed
     */
    public abstract function get();

    /**
     * 队列是否为空
     * @return mixed
     */
    public abstract function isEmpty();

    /**
     * 队列是否已满
     * @return mixed
     */
    public abstract function isFull();

    /**
     * 返回当前队列中的等待处理的数量
     * @return mixed
     */
    public abstract function count();
}