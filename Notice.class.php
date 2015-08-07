<?php
/**
 * 消息提示服务
 * User: dongdong
 * Date: 2015/6/26
 * Time: 16:30
 */

namespace WangDong;


class Notice {

    protected static $method = ['success','error','notice'];

    public static function clear(){
        foreach(static::$method as $name){
            session($name,null);
        }
    }

    public static function message($name){
        return session($name);
    }

    public static function __callStatic($name, $arguments){
        if(in_array($name,static::$method)){
            $url = empty($arguments[1]) ? $_SERVER['HTTP_REFERER'] : $arguments[1];
            if(IS_AJAX){
                exit(json_encode(['code'=>$name,'message'=>$arguments[0],'url'=>$url]));
            }else{
                session($name,$arguments[0]);
                redirect($url);
            }
        }
    }
} 