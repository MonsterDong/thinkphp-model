<?php
/**
 * 一个简单的事件服务
 * User: dongdong
 * Date: 2015/6/29
 * Time: 14:53
 */

namespace WangDong;


class Event {

    protected $listen;

    protected static $instance = null;

    private function __construct(){}

    public static function getInstance(){
        if(empty(static::$instance)){
            static::$instance = new self();
        }
        return static::$instance;
    }

    protected function parseClassCallable($listener)
    {
        $segments = explode('@', $listener);

        return [$segments[0], count($segments) == 2 ? $segments[1] : 'handle'];
    }

    public function createClassListener($listener)
    {
        list($class, $method) = $this->parseClassCallable($listener);

        return function() use ($class, $method)
        {
            return call_user_func_array(
                [$class, $method], func_get_args()
            );
        };
    }

    public static function boot($config){
        include_once($config);
    }

    public static function listen($event,$callback){
        if(!is_array($callback)){
            $callback = [$callback];
        }
        $self = static::getInstance();

        foreach($callback as $item){
            if(is_callable($item)){
                $self->listen[$event][] = $item;
            }
            if(is_string($item)){
                $self->listen[$event][] = $self->createClassListener($item);
            }
        }
    }

    public static function fire(){
        $args = func_get_args();
        $event = array_shift($args);
        $args[] = $event;
        $self = static::getInstance();
        foreach($self->listen[$event] as $callback){
            if(is_callable($callback)){
                $result = call_user_func_array($callback,$args);
                if(false === $result){ //如果事件处理报错则终止向下传递
                    return false;
                }
            }
        }
    }

} 