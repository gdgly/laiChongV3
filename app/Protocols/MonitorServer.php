<?php
/**
 * Created by PhpStorm.
 * User: lingf
 * Date: 2017-01-09
 * Time: 18:18
 */

namespace Wormhole\Protocols;

use Illuminate\Support\Facades\Config;
use Kozz\Laravel\Facades\Guzzle;
use Illuminate\Support\Facades\Log;

class MonitorServer
{

    //签到
    public static function deviceOnline($code, $num, $portArr, $evse_type, $version){
        //return [0,1,2,3,4,5,6,7,8,9];
        $api = Config::get('monitor.signAPI');
        $server =Config::get('monitor.host');

        $protocolIp =Config::get('gateway.protocol_ip');
        $protocolPort =Config::get('gateway.protocol_port');
        $platformName = Config::get('gateway.platform_name');

        $url = $server.$api;
        $params =[
            "self_evse_no"=>$code,
            "send_cmd_server_address"=>$protocolIp.':'.$protocolPort,
            "total_gun_num"=>$num,
            "evse_type"=>$evse_type,
            "protocol_name"=>$platformName,
            "protocol_ver"=>$version,
            "gun_list"=>$portArr



        ];

        $data = self::request($url,$params);

        if(FALSE == $data){
            return FALSE;
        }


        return $data;


    }

    //断线
    public static function offline($code){

        $api = Config::get('monitor.offlineApi');
        $server =Config::get('monitor.host');

        $platformName = Config::get('gateway.platform_name');

        $url = $server.$api;
        $params =[
            "self_evse_no"=>$code,
            "protocol_name"=>$platformName
        ];

        $data = self::request($url,$params);

        if(FALSE == $data){
            return FALSE;
        }


        return $data;

    }




    //心跳
    public static function hearbeat($status){

        $api = Config::get('monitor.hearbeatApi');
        $server =Config::get('monitor.host');

        $url = $server.$api;
        $params =[
            "evse_status_list"=>$status
        ];

        $data = self::request($url,$params);

        if(FALSE == $data){
            return FALSE;
        }


        return $data;

    }


    //启动充电成功
    public static function start_charge_success_response($monitorOrderId){

        $api = Config::get('monitor.startChargeSuccessApi');
        $server =Config::get('monitor.host');

        $url = $server.$api;
        $params =[
            "order_id"=>$monitorOrderId
        ];

        $data = self::request($url,$params);

        if(FALSE == $data){
            return FALSE;
        }


        return $data;


    }

    //启动充电失败
    public static function start_charge_failed_response($monitorOrderId){

        $api = Config::get('monitor.startChargeFailApi');
        $server =Config::get('monitor.host');

        $url = $server.$api;
        $params =[
            "order_id"=>$monitorOrderId
        ];

        $data = self::request($url,$params);

        if(FALSE == $data){
            return FALSE;
        }


        return $data;


    }

    //停止充电成功
    public static function stop_charge_success_response($monitorOrderId, $left_time, $chargeArgs){

        $api = Config::get('monitor.stopChargeSuccessApi');
        $server =Config::get('monitor.host');

        $url = $server.$api;
        $params =[
            "order_id"=>$monitorOrderId,
            "left_duration"=>$left_time,
            "already_charge_time"=>$chargeArgs
        ];

        $data = self::request($url,$params);

        if(FALSE == $data){
            return FALSE;
        }


        return $data;


    }


    //停止失败
    public static function stop_charge_failed_response($monitorOrderId){

        $api = Config::get('monitor.stopChargeFailApi');
        $server =Config::get('monitor.host');

        $url = $server.$api;
        $params =[
            "order_id"=>$monitorOrderId
        ];

        $data = self::request($url,$params);

        if(FALSE == $data){
            return FALSE;
        }


        return $data;

    }

    
    //续费成功
    public static function continue_charge_success_response($monitorOrderId){

        $api = Config::get('monitor.continueChargeSuccessApi');
        $server =Config::get('monitor.host');

        $url = $server.$api;
        $params =[
            "order_id"=>$monitorOrderId
        ];

        $data = self::request($url,$params);

        if(FALSE == $data){
            return FALSE;
        }


        return $data;
    

    }

    //续费失败
    public static function continue_charge_failed_response($monitorOrderId){

        $api = Config::get('monitor.continueChargeFailApi');
        $server =Config::get('monitor.host');

        $url = $server.$api;
        $params =[
            "order_id"=>$monitorOrderId
        ];

        $data = self::request($url,$params);

        if(FALSE == $data){
            return FALSE;
        }


        return $data;

    }


    //线下启动
    public static function offline_start_charge($code, $channel_num, $balance, $duration, $start_type, $order_id=''){

        $api = Config::get('monitor.evseLineChargeReport');
        $server =Config::get('monitor.host');

        $platformName = Config::get('gateway.platform_name');

        $url = $server.$api;
        $params =[
            "self_evse_no"=>$code,
            "gun_num"=>$channel_num,
            "order_sum"=>$balance,
            "order_charge_duration"=>$duration,
            "protocol_name"=>$platformName,
            "start_from"=>$start_type,
            "order_id"=>$order_id




        ];

        $data = self::request($url,$params);

        if(FALSE == $data){
            return FALSE;
        }


        return $data;



    }




    //日结
    public static function evse_report_day_data($evse_code, $coin_number, $same_day_coins_number, $card_money, $charge_power, $total_charge_power, $stat_date){
        
        $api = Config::get('monitor.turnoverApi');
        $server =Config::get('monitor.host');

        $platformName = Config::get('gateway.platform_name');

        $url = $server.$api;
        $params =[
            "self_evse_no" => $evse_code,
            "protocol_name"=>$platformName,
            "coin_number" => $same_day_coins_number,
            "total_coin_number"=>$coin_number,
            "charge_power"=>$charge_power,
            "ammeter_reading"=>$total_charge_power,
            "start_date"=>$stat_date,
            "card_money" => $card_money
        ];




        $data = self::request($url,$params);

        if(FALSE == $data){
            return FALSE;
        }


        return $data;
        
        
    }



    //错误日志
    public static function add_device_error_log($code, $gun_num, $device_status){

        $api = Config::get('monitor.deviceErrorApi');
        $server =Config::get('monitor.host');

        $platformName = Config::get('gateway.platform_name');

        $url = $server.$api;
        $params =[
            "self_evse_no"=>$code,
            "protocol_name"=>$platformName,
            "gun_num"=>$gun_num,
            "gun_status"=>$device_status
        ];

        $data = self::request($url,$params);

        if(FALSE == $data){
            return FALSE;
        }


        return $data;

    }




    private static function request($url,$data,$method="POST"){
        $data = ["params"=>$data];

        Log::debug(__NAMESPACE__ .  "/" . __CLASS__."/" . __FUNCTION__ . "@" . __LINE__ . " request monitor url:$url data:".json_encode($data));

        $request = [
            "headers"=>[
                'Content-Type'=>'application/json'
            ],
            "body"=> json_encode($data)

        ];


        $reponse = Guzzle::post($url,$request);

        if($reponse->getStatusCode() == 200){
            $data = json_decode($reponse->getBody(),TRUE);
            Log::debug( __NAMESPACE__ .  "/" . __CLASS__."/" . __FUNCTION__ . "@" . __LINE__ . " ". $reponse->getBody());
            if(TRUE == $data['status']){
                if(empty($data['data'])){
                    return TRUE;
                }
                return $data['data'];
            }
        }

        return FALSE;

    }



    public static function post($url){


        Log::debug(__NAMESPACE__ .  "/" . __CLASS__."/" . __FUNCTION__ . "@" . __LINE__ . " request monitor url:$url");


        $reponse = Guzzle::get($url);

        if($reponse->getStatusCode() == 200){
            $data = json_decode($reponse->getBody(),TRUE);
            Log::debug( __NAMESPACE__ .  "/" . __CLASS__."/" . __FUNCTION__ . "@" . __LINE__ . " ". $reponse->getBody());
            if(TRUE == $data['status']){
                if(empty($data['data'])){
                    return TRUE;
                }
                return $data['data'];
            }
        }

        return FALSE;

    }





}