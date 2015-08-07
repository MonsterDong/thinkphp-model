<?php
/**
 * 助手类
 * User: dongdong
 * Date: 2015/7/13
 * Time: 12:06
 */

namespace WangDong;


class Helper {

    public static function array_column($data,$field){
        foreach($data as $val){
            $tmp[] = $val[$field];
        }
        return $tmp;
    }

} 