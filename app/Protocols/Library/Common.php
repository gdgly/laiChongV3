<?php
/**
 * Created by PhpStorm.
 * User: lingf
 * Date: 2017-03-02
 * Time: 18:14
 */

namespace Wormhole\Protocols\Library;

use Illuminate\Support\Facades\Log;
use Wormhole\Protocols\Library\Log as Logger;
use Illuminate\Support\Facades\Redis;

class Common{

    //记录log,包括帧名称,协议名称,版本号,参数,原始帧,时间. 记录到自身桩编号文件日志中和redis中
    public static  function record_log($code, $fiel_data, $redis_data){
        //记录到自身桩编号文件日志中
        Logger::log($code, __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . $fiel_data );

        $prefix = env('FRAME_PREFIX'); //前缀
        $term_validity = env('FRAME_TERM_VALIDITY');//有效期
        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " prefix:$prefix, term_validity:$term_validity ");
        //追加到redis中
        $str_len = Redis::append($prefix.$code,$redis_data);//,'EX',$term_validity "'$prefix.$code'"
        Redis::expire($prefix.$code, $term_validity);

        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 帧存入redis str_len:$str_len ");

    }


}