<?php
namespace Wormhole\Protocols\LcV3;
    /**
     * This file is part of workerman.
     *
     * Licensed under The MIT License
     * For full copyright and license information, please see the MIT-LICENSE.txt
     * Redistributions of files must retain the above copyright notice.
     *
     * @author walkor<walkor@workerman.net>
     * @copyright walkor<walkor@workerman.net>
     * @link http://www.workerman.net/
     * @license http://www.opensource.org/licenses/mit-license.php MIT License
     */

    /**
     * 用于检测业务代码死循环或者长时间阻塞等问题
     * 如果发现业务卡死，可以将下面declare打开（去掉//注释），并执行php start.php reload
     * 然后观察一段时间workerman.log看是否有process_timeout异常
     */
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Wormhole\Protocols\BaseEvents;
use Wormhole\Protocols\LcV3\Protocol\Evse\CardInfo;
use Wormhole\Protocols\Tools;
use Wormhole\Protocols\Library\Common;

use Wormhole\Protocols\LcV3\Protocol\Frame;

use Wormhole\Protocols\LcV3\Controllers\ProtocolController;
use Wormhole\Protocols\LcV3\Models\Evse;
use Wormhole\Protocols\LcV3\Models\Port;

use Wormhole\Protocols\MonitorServer;

//签到
use Wormhole\Protocols\LcV3\Protocol\Evse\Sign as EvseSign;
use Wormhole\Protocols\LcV3\Protocol\Server\Sign as ServerSign;

//心跳
use Wormhole\Protocols\LcV3\Protocol\Evse\Heartbeat as EvseHeartbeat;
use Wormhole\Protocols\LcV3\Protocol\Server\Heartbeat as ServerHeartbeat;

//桩自动停止
use Wormhole\Protocols\LcV3\Protocol\Evse\AutomaticStop as EvseAutomaticStop;
use Wormhole\Protocols\LcV3\Protocol\Server\AutomaticStop as ServerAutomaticStop;

//状态上报
use Wormhole\Protocols\LcV3\Protocol\Evse\Statusreport as EvseStatusreport;

//日结
use Wormhole\Protocols\LcV3\Protocol\Evse\Report as EvseReport;
use Wormhole\Protocols\LcV3\Protocol\Server\Report as ServerReport;

//线下启动
use Wormhole\Protocols\LcV3\Protocol\Evse\OfflineStart as EvseOfflineStart;
//卡信息
use Wormhole\Protocols\LcV3\Protocol\Evse\CardInfo as EvseCardInfo;


//启动充电
use Wormhole\Protocols\LcV3\Protocol\Server\StartCharge as ServerStartCharge;
use Wormhole\Protocols\LcV3\Protocol\Evse\StartCharge as EvseStartCharge;

//续费
use Wormhole\Protocols\LcV3\Protocol\Server\Renew as ServerRenew;
use Wormhole\Protocols\LcV3\Protocol\Evse\Renew as EvseRenew;

//停止充电
use Wormhole\Protocols\LcV3\Protocol\Server\StopCharge as ServerStopCharge;
use Wormhole\Protocols\LcV3\Protocol\Evse\StopCharge as EvseStopCharge;

//心跳设置
use Wormhole\Protocols\LcV3\Protocol\Server\SetHearbeat as ServerSetHearbeat;
use Wormhole\Protocols\LcV3\Protocol\Evse\SetHearbeat as EvseHearbeat;

//服务器信息设置
use Wormhole\Protocols\LcV3\Protocol\Server\ServerInfo as ServerInfo;
use Wormhole\Protocols\LcV3\Protocol\Evse\ServerInfo as EvseServerInfo;

//清空营业额
use Wormhole\Protocols\LcV3\Protocol\Server\EmptyTurnover as ServerEmptyTurnover;
use Wormhole\Protocols\LcV3\Protocol\Evse\EmptyTurnover as EvseEmptyTurnover;

//设置参数
use Wormhole\Protocols\LcV3\Protocol\Server\SetParameter as ServerSetParameter;
use Wormhole\Protocols\LcV3\Protocol\Evse\SetParameter as EvseSetParameter;

//设置ID
use Wormhole\Protocols\LcV3\Protocol\Server\SetId as ServerSetId;
use Wormhole\Protocols\LcV3\Protocol\Evse\SetId as EvseSetId;

//心跳查询
use Wormhole\Protocols\LcV3\Protocol\Server\GetHearbeat as ServerGetHeartbeat;
use Wormhole\Protocols\LcV3\Protocol\Evse\GetHearbeat as EvseGetHeartbeat;

//电表抄表
use Wormhole\Protocols\LcV3\Protocol\Server\ReadMeter as ServerReadMeter;
use Wormhole\Protocols\LcV3\Protocol\Evse\ReadMeter as EvseReadMeter;

//营业额查询
use Wormhole\Protocols\LcV3\Protocol\Server\GetTurnover as ServerGetTurnover;
use Wormhole\Protocols\LcV3\Protocol\Evse\GetTurnover as EvseGetTurnover;

//通道查询
use Wormhole\Protocols\LcV3\Protocol\Server\GetChannelStatus as ServerGetChannelStatus;
use Wormhole\Protocols\LcV3\Protocol\Evse\GetChannelStatus as EvseGetChannelStatus;

//查询参数
use Wormhole\Protocols\LcV3\Protocol\Server\GetParameter as ServerGetParameter;
use Wormhole\Protocols\LcV3\Protocol\Evse\GetParameter as EvseGetParameter;

//设置时间
use Wormhole\Protocols\LcV3\Protocol\Evse\SetDateTime as EvseSetDateTime;

//查询时间
use Wormhole\Protocols\LcV3\Protocol\Evse\GetDateTime as EvseGetDateTime;

//查询ID
use Wormhole\Protocols\LcV3\Protocol\Evse\GetId as EvseGetId;

//查询设备识别号
use Wormhole\Protocols\LcV3\Protocol\Evse\GetDeviceIdentification as EvseGetDeviceIdentification;

use Illuminate\Support\Facades\Redis;

use Wormhole\Protocols\Library\Log as Logger;



//签到
use Wormhole\Protocols\LcV3\Jobs\SignReport;
//自动停止
use Wormhole\Protocols\LcV3\Jobs\AutoStopReport;
//日结
use Wormhole\Protocols\LcV3\Jobs\TurnoverReport;
//心跳
use Wormhole\Protocols\LcV3\Jobs\HeartBeatReport;
//线下启动
//use Wormhole\Protocols\QuChong\Jobs\HeartBeatReport;
//卡信息
use Wormhole\Protocols\LcV3\Jobs\CardInfo as CardInfoJob;
//线下启动
use Wormhole\Protocols\LcV3\Jobs\OfflineStart as OfflineStartjob;

//启动充电响应
use Wormhole\Protocols\LcV3\Jobs\StartChargeResponse;
//续费响应
use Wormhole\Protocols\LcV3\Jobs\RenewResponse;
//停止充电响应
use Wormhole\Protocols\LcV3\Jobs\StopChargeResponse;




/**
 * 主逻辑
 * 主要是处理 onConnect onMessage onClose 三个方法
 * onConnect 和 onClose 如果不需要可以不用实现并删除
 */
class EventsApi extends BaseEvents
{
    public static $client_id = '';
    public static $controller;
    public static $protocol_name;
    /**
     * @param string $client_id 连接id
     * @param mixed $message 具体消息
     * @return bool
     */
    public static function message($client_id, $message)
    {
        self::$client_id = $client_id;
        self::$controller = new ProtocolController($client_id);
        self::$protocol_name = env('PROTOCOL_NAME');

        Log::debug(__NAMESPACE__ . "\\" . __CLASS__ . "\\" . __FUNCTION__ . "@" . __LINE__ . "  client_id:$client_id, message:" . bin2hex($message));


        //帧解析
        $frame = new Frame();
        $frame = $frame($message);
        //判断帧是否正确
        if(empty($frame)){
            Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " " . " 帧格式不正确 ");
            return false;
        }
        //指令
        $operator = $frame->operator->getValue();
        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " " . " operator:,".$operator." isValid:$frame->isValid");
        if (!empty($frame)) {
            switch ($operator) {

                /*****************************************桩主动上报****************************************************/
                case (0x1101):
                    Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 签到 " );
                    self::sign($message);
                    break;
                case (0x1102):
                    Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 心跳 " );
                    self::hearbeat($message);
                    break;
                case (0x1103):
                    Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 桩自动停止 " );
                    self::auto_stop($message);
                    break;
                case (0x1104):
                    Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 状态上报 " );
                    self::hearbeat($message);
                    break;
                case (0x1105):
                    Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 日结 " );
                    self::report($message);
                    break;
                case (0x1106):
                    Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 线下启动(投币或刷卡) " );
                    self::offline_start($message);
                    break;
                case (0x1107):
                    Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 卡片信息 " );
                    self::card_info($message);
                    break;


                /*****************************************控制类上报****************************************************/
                case (0x1201):
                    Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 启动充电 " );
                    self::start_charge_response($message);
                    break;
                case (0x1202):
                    Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 续费 " );
                    self::renew_response($message);
                    break;
                case (0x1203):
                    Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 停止充电 " );
                    self::stop_charge_response($message);
                    break;

                /*****************************************设置类上报****************************************************/
                case (0x1301):
                    Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 心跳设置 " );
                    self::set_hearbeat_response($message);
                    break;
                case (0x1302):
                    Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 服务器信息设置 " );
                    self::set_server_info_response($message);
                    break;
                case (0x1303):
                    Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 清空营业额 " );
                    self::empty_turnover_response($message);
                    break;
                case (0x1304):
                    Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 设置参数 " );
                    self::set_parameter_response($message);
                    break;
                case (0x1305):
                    Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 设置ID " );
                    self::set_id_response($message);
                    break;
                case (0x1306):
                    Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 修改时间 " );
                    self::set_date_time_response($message);
                    break;


                /*****************************************查询类上报****************************************************/
                case (0x1401):
                    Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 心跳查询 " );
                    self::get_hearbeat_response($message);
                    break;
                case (0x1402):
                    Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 电表抄表查询 " );
                    self::get_meter_response($message);
                    break;
                case (0x1403):
                    Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 营业额查询 " );
                    self::get_turnover_response($message);
                    break;
                case (0x1404):
                    Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 通道查询 " );
                    self::get_channel_response($message);
                    break;
                case (0x1405):
                    Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 查询参数 " );
                    self::get_parameter_response($message);
                    break;
                case (0x1406):
                    Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 查询ID " );
                    self::get_id_response($message);
                    break;
                case (0x1407):
                    Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 查询设备识别号 " );
                    self::get_identification_response($message);
                    break;
                case (0x1408):
                    Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 查询信号强度 " );
                    self::get_signal_response($message);
                    break;
                case (0x1409):
                    Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 查询时间 " );
                    self::get_date_time_response($message);
                    break;



            }
        }


    }


    /*****************************************桩主动上报****************************************************/

    //签到
    private static function sign($message)
    {
        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 签到上报11 protocol_name".self::$protocol_name);
        $sign = new EvseSign();
        $sign($message);
        //接收数据
        $version = $sign->version->getValue();//版本号
        $code = $sign->code->getValue(); //桩编号
        $num = $sign->num->getValue();   //枪口数量
        $heabeatCycle = $sign->heabeat_cycle->getValue();
        $device_identification = $sign->device_identification->getValue(); //设备编号

        //判断接收数据是否正确
        if(empty($code) || !is_numeric($num) || empty($device_identification) || empty($version)){
            Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 签到上报,数据不正确, code:$code, num:$num, device_identification:$device_identification, version:$version ");
            return false;
        }

        //每次签到重置一下流水号
        Redis::set($code.':serial_number',1);

        //记录对应某个桩的log
        $file_data = " 签到,桩上报时间 date: ".Carbon::now().PHP_EOL." 签到,桩上报参数 code:$code, num:$num, device_identification:$device_identification, heabeatCycle:$heabeatCycle, version:$version ".PHP_EOL." 签到,桩上报帧 frame: ".bin2hex($message);
        $redis_data = json_encode(['frame_name'=>'签到,桩上报', 'protocol_name'=>self::$protocol_name, 'version'=>$version, 'parameter'=>array('code'=>$code, 'num'=>$num, 'heabeatCycle'=>$heabeatCycle, 'device_identification'=>$device_identification, 'version'=>$version), 'frame'=>bin2hex($message), 'date'=>strval(Carbon::now())]).'+';

        Common::record_log($code, $file_data, $redis_data);

        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . ", num21:$num, code:$code, device_identification:$device_identification, heabeatCycle:$heabeatCycle, version:$version " . Carbon::now());


        //处理签到上报数据并应答桩SignReport
        $job = (new SignReport($code, $num, $device_identification, $heabeatCycle, self::$client_id, $version))
            ->onQueue(env("APP_KEY"));
        dispatch($job);




    }


    //心跳
    private static function hearbeat($message){

        
        $data = [];
        $version = 1;
        $hearbeat = new EvseHeartbeat();
        $frame_load = [$hearbeat($message)];
        $clientId = self::$client_id;
        //如果是个数组,表示多个心跳，否则一个心跳
        $evse_num = count($frame_load); //上报心跳数量
        foreach ($frame_load as $k=>$frame_obj){

            $version = $frame_obj->version->getValue();//版本号
            $data[$k]['code'] = $frame_obj->code->getValue();
            $data[$k]['num'] = $frame_obj->info->num; //枪口数量
            $data[$k]['signal'] = $frame_obj->info->signal;
            for($i=0;$i<$data[$k]['num'];$i++){
                $data[$k]['current'][$i] = $frame_obj->info->data[$i]['current']->getValue() / 1000;      //电流
                $data[$k]['left_time'][$i] = $frame_obj->info->data[$i]['left_time']->getValue();  //剩余时间
                $status = $frame_obj->info->data[$i]['status']->getValue();                        //设备状态
                $data[$k]['status']['worke_state'][$i] = $status['worke_state'];                   //工作状态
                $data[$k]['status']['fuses'][$i] = $status['fuses'];                                 //熔断
                $data[$k]['status']['overcurrent'][$i] = $status['overcurrent'];                    //过流
                $data[$k]['status']['connect'][$i] = $status['connect'];                             //连接
                $data[$k]['status']['full'][$i] = $status['full'];                                    //充满

                $data[$k]['status']['start_up'][$i] = $status['start_up'];                            //启动
                $data[$k]['status']['pull_out'][$i] = $status['pull_out'];                            //拔出

                Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . ", evse_num:$evse_num, signal:".$data[$k]['signal']."
            num:".$data[$k]['num'].", code:".$data[$k]['code'].", current:".$data[$k]['current'][$i].", left_time:".$data[$k]['left_time'][$i].", worke_state:".$data[$k]['status']['worke_state'][$i]."
             fuses:".$data[$k]['status']['fuses'][$i].", overcurrent:".$data[$k]['status']['overcurrent'][$i].", connect:".$data[$k]['status']['connect'][$i].
                    ", full:".$data[$k]['status']['full'][$i]."start_up:".$data[$k]['status']['start_up'][$i]."pull_out".$data[$k]['status']['pull_out'][$i]."" . date('Y-m-d H:i:s', time()));
            }


        }

        $file_data = " 心跳,桩上报时间 date: ".Carbon::now().PHP_EOL." 心跳,桩上报参数  ".json_encode($data).PHP_EOL." 心跳,桩上报帧 frame: ".bin2hex($message);
        $redis_data = json_encode(['frame_name'=>'心跳,桩上报', 'protocol_name'=>self::$protocol_name, 'version'=>$version, 'parameter'=>$data, 'frame'=>bin2hex($message), 'date'=>strval(Carbon::now())]).'+';
        Common::record_log($data[0]['code'], $file_data, $redis_data);

        //处理签到上报数据并应答桩HeartBeatReport
        $job = (new HeartBeatReport($data, $evse_num, $clientId))
            ->onQueue(env("APP_KEY"));
        dispatch($job);



    }

    //桩自动停止
    private static function auto_stop($message){

        $automatic = new EvseAutomaticStop();
        $frame_load = $automatic($message);
        $code = $frame_load->code->getValue();

        //接收数据 订单号 剩余时间 停止原因
        $version = $frame_load->version->getValue();//版本号
        $order_number = $frame_load->order_number->getValue(); //订单号
        $startup_type = $frame_load->startup_type->getValue(); //启动类型
        $left_time = $frame_load->left_time->getValue(); //剩余时间
        $stop_reason = $frame_load->stop_reason->getValue(); //停止原因

        //判断接收参数是否正确
        if(!is_numeric($order_number) || !is_numeric($left_time) || !is_numeric($stop_reason)){
            Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 桩自动停止参数不正确 ");
            return false;
        }

        //记录log
        $fiel_data = " 桩自动停止,桩上报时间 date: ".Carbon::now().PHP_EOL." 桩自动停止,桩上报参数 code:$code, order_number:$order_number, left_time:$left_time, stop_reason:$stop_reason, startup_type:$startup_type ".PHP_EOL." 桩自动停止,桩上报帧 frame: ".bin2hex($message);
        $redis_data = json_encode(['frame_name'=>'桩自动停止上报', 'protocol_name'=>self::$protocol_name, 'version'=>$version, 'parameter'=>array('code'=>$code, 'order_number'=>$order_number, 'left_time'=>$left_time, 'stop_reason'=>$stop_reason, 'startup_type'=>$startup_type), 'frame'=>bin2hex($message), 'date'=>strval(Carbon::now())]).'+';
        Common::record_log($code, $fiel_data, $redis_data);

        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . ", 
            code:$code, order_number:$order_number, left_tine:$left_time, stop_reason:$stop_reason, startup_type:$startup_type " . Carbon::now());

        //处理接受自动停止数据,并响应桩
        $job = (new AutoStopReport($code, $order_number, $startup_type, $left_time, $stop_reason))
            ->onQueue(env("APP_KEY"));
        dispatch($job);


    }



    //日结
    private static function report($message){

        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . ", 
            收到日结start " . date('Y-m-d H:i:s', time()));
        $report = new EvseReport();
        $report($message);
        $code = $report->code->getValue();
        $version = $report->version->getValue();                           //版本号
        $meter_number = $report->meter_number->getValue();                 //电表编号
        $date = $report->date->getValue();                                 //时间
        $electricity = $report->electricity->getValue();                   //电量
        $total_electricity = $report->total_electricity->getValue() / 100; //总电量
        $coins_number = $report->coins_number->getValue();                 //投币次数
        $card_amount = $report->card_amount->getValue();                   //刷卡金额
        $card_time = $report->card_time->getValue();                       //刷卡时长

        //换算单位
        $electricity_con = [];
        foreach ($electricity as $k=>$v){
            $electricity_con[$k] = $v / 100;
        }


        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . ", 
                code:$code, meter_number:$meter_number, date:$date, electricity:".json_encode($electricity_con).", total_electricity:$total_electricity
                 coins_number:$coins_number, card_amount:$card_amount, card_time:$card_time " . date('Y-m-d H:i:s', time()));

        //记录log
        $fiel_data = " 日结,桩上报时间 date: ".Carbon::now()." 日结,桩上报参数 code:$code, meter_number:$meter_number, date:$date, electricity:".json_encode($electricity).", total_electricity:$total_electricity, coins_number:$coins_number, card_amount:$card_amount, card_time:$card_time "." 日结,桩上报帧 frame: ".bin2hex($message);
        $redis_data = json_encode(['frame_name'=>'日结上报上报', 'protocol_name'=>self::$protocol_name, 'version'=>$version, 'parameter'=>array('code'=>$code, 'meter_number'=>$meter_number, 'date'=>$date, 'electricity'=>$electricity_con, 'total_electricity'=>$total_electricity, 'coins_number'=>$coins_number, 'card_amount'=>$card_amount, 'card_time'=>$card_time), 'frame'=>bin2hex($message), 'date'=>strval(Carbon::now())]).'+';
        Common::record_log($code, $fiel_data, $redis_data);

        //日结进入队列 由于桩上报的时间为晚上12点,monitor统计要12点5分左右,所以队列十分钟后执行
        $job = (new TurnoverReport($code, $meter_number, $date, $electricity_con, $total_electricity, $coins_number, $card_amount, $card_time))
            ->onQueue(env("APP_KEY"))
            ->delay(Carbon::now()->addSeconds(600));
        dispatch($job);



    }


    //卡片信息
    private static function card_info($message){

        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . ", 
            收到卡信息上报 start " . date('Y-m-d H:i:s', time()));

        $card_info = new EvseCardInfo();
        $card_info($message);
        $code = $card_info->code->getValue();      //桩编号
        $version = $card_info->version->getValue();//版本号

        $card_num = $card_info->card_num->getValue();
        $card_type = $card_info->card_type->getValue();
        $retain = $card_info->retain->getValue();
        $balance = $card_info->balance->getValue();
        $user_password = $card_info->user_password->getValue();
        $start_card_date = $card_info->start_card_date->getValue();
        $effective_month = $card_info->effective_month->getValue();
        $crc16 = $card_info->crc16->getValue();


        //卡片信息入队列
        $job = (new CardInfoJob($code, $card_num, $card_type, $retain, $balance, $user_password, $start_card_date, $effective_month, $crc16))
            ->onQueue(env("APP_KEY"));
        dispatch($job);


        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 卡片信息数据: code:$code, version:$version,
         card_num:$card_num, card_type:$card_type, retain:$retain, balance:$balance, user_password:$user_password, start_card_date:$start_card_date, 
         effective_month:$effective_month, crc16:$crc16  " . date('Y-m-d H:i:s', time()));


    }


    //线下启动
    private static function offline_start($message){

        $offline_start = new EvseOfflineStart();
        $offline_start($message);
        $code = $offline_start->code->getValue();      //桩编号
        $version = $offline_start->version->getValue();//版本号

        $odd_num = $offline_start->odd_num->getValue();
        $channel_num = $offline_start->channel_num->getValue();
        $start_type = $offline_start->start_type->getValue();
        $balance = $offline_start->balance->getValue() / 100;
        $duration = $offline_start->duration->getValue();
        $card_num = $offline_start->card_num->getValue();

        //线下启动入队列
        $job = (new OfflineStartjob($code, $odd_num, $channel_num, $start_type, $balance, $duration, $card_num, self::$client_id))
            ->onQueue(env("APP_KEY"));
        dispatch($job);


        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ .
        " 线下启动: code:$code, version:$version, odd_num:$odd_num,
          channel_num:$channel_num, start_type:$start_type, balance:$balance ,
          duration:$duration, card_num:$card_num" . date('Y-m-d H:i:s', time()));


    }









    /*****************************************控制类****************************************************/

    //启动充电响应
    private static function start_charge_response($message){

        $startChrge = new EvseStartCharge();
        $startChrge($message);
        $code = $startChrge->code->getValue();
        $version = $startChrge->version->getValue();//版本号
        $order_number = $startChrge->order_number->getValue();
        $result = $startChrge->result->getValue();

        //判断接受参数是否正确
        if(empty($code) || empty($order_number) || !is_numeric($result)){
            Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 启动充电响应接受参数错误 code:$code, order_number:$order_number, result:$result ");
            return false;
        }

        //记录对应相应桩的log
        Logger::log($code, __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 启动充电响应: order_number:$order_number, result:$result ".Carbon::now() );

        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . ", 
            code:$code, order_number:$order_number, result:$result " . Carbon::now());

        //记录log
        $fiel_data = " 启动充电响应时间 date: ".Carbon::now()." 启动充电响应参数 code:$code, order_number:$order_number, result:$result "." 启动充电响应帧 frame: ".bin2hex($message);
        $redis_data = json_encode(['frame_name'=>'启动充电响应', 'protocol_name'=>self::$protocol_name, 'version'=>$version, 'parameter'=>array('code'=>$code, 'order_number'=>$order_number, 'result'=>$result), 'frame'=>bin2hex($message), 'date'=>strval(Carbon::now())]).'+';
        Common::record_log($code, $fiel_data, $redis_data);


        //启动充电
        $job = (new StartChargeResponse($code, $order_number, $result))
            ->onQueue(env("APP_KEY"));
        dispatch($job);



    }


    //续费响应
    private static function renew_response($message){

        $renew = new EvseRenew();
        $frame_load = $renew($message);
        $code = $frame_load->code->getValue();
        $version = $frame_load->version->getValue();//版本号
        $order_number = $frame_load->order_number->getValue();
        $result = $frame_load->result->getValue();

        //判断接受参数是否正确
        if(empty($code) || empty($order_number) || !is_numeric($result)){
            Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 续费响应接受参数错误, code:$code, order_number:$order_number, result:$result ");
            return false;
        }

        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . ", 
            code:$code, order_number:$order_number, result:$result " . Carbon::now());


        //记录log
        $fiel_data = " 续费响应时间 date: ".Carbon::now()." 续费响应参数 code:$code, order_number:$order_number, result:$result "." 续费响应帧 frame: ".bin2hex($message);
        $redis_data = json_encode(['frame_name'=>'续费响应', 'protocol_name'=>self::$protocol_name, 'version'=>$version, 'parameter'=>array('code'=>$code, 'order_number'=>$order_number, 'result'=>$result), 'frame'=>bin2hex($message), 'date'=>strval(Carbon::now())]).'+';
        Common::record_log($code, $fiel_data, $redis_data);


        //处理续费响应数据
        $job = (new RenewResponse($code, $order_number, $result))
            ->onQueue(env("APP_KEY"));
        dispatch($job);

        //处理数据
        //$result = self::$controller->renew($code, $order_number, $result);


    }


    //停止充电响应
    private static function stop_charge_response($message){

        $stop = new EvseStopCharge();
        $frame_load = $stop($message);
        $code = $frame_load->code->getValue();
        $version = $frame_load->version->getValue();//版本号
        $order_number = $frame_load->order_number->getValue();
        $result = $frame_load->result->getValue();
        $left_time = $frame_load->left_time->getValue();
        $stop_time = $frame_load->stop_time->getValue();

        //判断接受参数是否正确
        if(empty($code) || empty($order_number) || !is_numeric($result) || !is_numeric($left_time) || !is_numeric($stop_time)){
            Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 续费响应接受参数错误 code:$code, order_number:$order_number, result:$result, left_time:$left_time, stop_time:$stop_time ");
            return false;
        }

        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . ", 
            code:$code, order_number:$order_number, result:$result, left_time:$left_time, stop_time:$stop_time " . Carbon::now());


        //记录log
        $fiel_data = " 停止充电响应时间 date: ".Carbon::now()." 停止充电响应参数 code:$code, order_number:$order_number, result:$result, left_time:$left_time, stop_time:$stop_time "." 停止充电响应帧 frame: ".bin2hex($message);
        $redis_data = json_encode(['frame_name'=>'停止充电响应', 'protocol_name'=>self::$protocol_name, 'version'=>$version, 'parameter'=>array('code'=>$code, 'order_number'=>$order_number, 'result'=>$result, 'left_time'=>$left_time, 'stop_time'=>$stop_time), 'frame'=>bin2hex($message), 'date'=>strval(Carbon::now())]).'+';
        Common::record_log($code, $fiel_data, $redis_data);


        //处理停止充电响应数据
        $job = (new StopChargeResponse($code, $order_number, $result, $left_time, $stop_time))
            ->onQueue(env("APP_KEY"));
        dispatch($job);



    }


    /*****************************************设置类****************************************************/


    //心跳设置响应
    private static function set_hearbeat_response($message){

        $hearbeat = new EvseHearbeat();
        $frame_load = $hearbeat($message);
        $code = $frame_load->code->getValue();
        $version = $frame_load->version->getValue();//版本号
        $result = $frame_load->result->getValue();
        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . ", 设置心跳周期收到响应：
            code:$code, result:$result " . Carbon::now());


        $fiel_data = " 设置心跳周期响应: code:$code, result:$result".PHP_EOL."frame".bin2hex($message).'时间:'.Carbon::now();
        $redis_data = json_encode(['frame_name'=>'设置心跳周期响应', 'protocol_name'=>self::$protocol_name, 'version'=>$version, 'parameter'=>array('result'=>$result), 'frame'=>bin2hex($message), 'date'=>strval(Carbon::now())]).'+';
        Common::record_log($code, $fiel_data, $redis_data);

        //记录log
        //$content = "心跳设置响应: code:$code, result:$result ".'-'.bin2hex($message).'-'.date('Y-m-d H:i:s', time());
        //self::redis_log($code, $content);


        //处理数据
        $result = self::$controller->setHearbeatCycle($code, $result);


    }


    //服务器信息设置响应
    private static function set_server_info_response($message){

        $info = new EvseServerInfo();
        $frame_load = $info($message);
        $code = $frame_load->code->getValue();
        $version = $frame_load->version->getValue();//版本号
        $result = $frame_load->result->getValue();
        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . ", 设置服务器信息收到响应：
            code:$code, result:$result " . Carbon::now());



        //记录log
        $fiel_data = " 设置服务器信息响应: code:$code, result:$result".PHP_EOL."frame".bin2hex($message).'时间:'.Carbon::now();
        $redis_data = json_encode(['frame_name'=>'设置服务器信息响应', 'protocol_name'=>self::$protocol_name, 'version'=>$version, 'parameter'=>array('result'=>$result), 'frame'=>bin2hex($message), 'date'=>strval(Carbon::now())]).'+';
        Common::record_log($code, $fiel_data, $redis_data);



        //处理数据
        $result = self::$controller->setServerInfo($code, $result);

    }



    //清空营业额响应
    private static function empty_turnover_response($message){

        $turnover = new EvseEmptyTurnover();
        $frame_load = $turnover($message);
        $code = $frame_load->code->getValue();
        $version = $frame_load->version->getValue();//版本号
        $coin_num = $frame_load->coin_num->getValue();
        $card_cost = $frame_load->card_cost->getValue();
        $card_time = $frame_load->card_time->getValue();
        $result = $frame_load->result->getValue();

        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . ", 清空营业额响应：
            code:$code, coin_num:$coin_num, card_cost:$card_cost, card_time:$card_time result:$result " . Carbon::now());

        //记录log
        $fiel_data = " 清空营业额响应: code:$code, result:$result".PHP_EOL."frame".bin2hex($message).'时间:'.Carbon::now();
        $redis_data = json_encode(['frame_name'=>'清空营业额响应', 'protocol_name'=>self::$protocol_name, 'version'=>$version, 'parameter'=>array('code'=>$code, 'result'=>$result), 'frame'=>bin2hex($message), 'date'=>strval(Carbon::now())]).'+';
        Common::record_log($code, $fiel_data, $redis_data);

        //处理数据
        $result = self::$controller->emptyTurnover($code, $coin_num, $card_cost, $card_time, $result);

    }


    //设置参数响应
    private static function set_parameter_response($message){

        $parameter = new EvseSetParameter();
        $frame_load = $parameter($message);
        $code = $frame_load->code->getValue();
        $version = $frame_load->version->getValue();//版本号
        $result = $frame_load->result->getValue();

        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . ", 设置参数响应：
            code:$code, result:$result " . Carbon::now());


        //记录log
        $fiel_data = " 设置参数响应: code:$code, result:$result".PHP_EOL."frame".bin2hex($message).'时间:'.Carbon::now();
        $redis_data = json_encode(['frame_name'=>'设置参数响应', 'protocol_name'=>self::$protocol_name, 'version'=>$version, 'parameter'=>array('code'=>$code, 'result'=>$result), 'frame'=>bin2hex($message), 'date'=>strval(Carbon::now())]).'+';
        Common::record_log($code, $fiel_data, $redis_data);

        //处理数据
        $result = self::$controller->setParament($code, $result);



    }



    //设置ID响应
    private static function set_id_response($message){

        $setId = new EvseSetId();
        $frame_load = $setId($message);
        $code = $frame_load->code->getValue();
        $result = $frame_load->result->getValue();

        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . ", 设置ID响应：
            code:$code, result:$result " . Carbon::now());


    }

    //修改时间
    private static function set_date_time_response($message){

        $dateTime = new EvseSetDateTime();
        $frame_load = $dateTime($message);
        $code = $frame_load->code->getValue();
        $version = $frame_load->version->getValue();//版本号
        $result = $frame_load->result->getValue();

        //记录log
        $fiel_data = " 修改时间响应: code:$code, result:$result".PHP_EOL."frame".bin2hex($message).'时间:'.Carbon::now();
        $redis_data = json_encode(['frame_name'=>'修改时间响应', 'protocol_name'=>self::$protocol_name, 'version'=>$version, 'parameter'=>array('code'=>$code, 'result'=>$result), 'frame'=>bin2hex($message), 'date'=>strval(Carbon::now())]).'+';
        Common::record_log($code, $fiel_data, $redis_data);

        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . ", 设置时间响应：
            code:$code, result:$result " . Carbon::now());
    }





    /*****************************************查询类****************************************************/

    //心跳查询响应
    private static function get_hearbeat_response($message){

        $hearbeat = new EvseGetHeartbeat();
        $frame_load = $hearbeat($message);
        $code = $frame_load->code->getValue();
        $version = $frame_load->version->getValue();//版本号
        $heartbeat_cycle = $frame_load->heartbeat_cycle->getValue();

        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . ", 心跳查询响应：
            code:$code, result:$heartbeat_cycle " . Carbon::now());


        //记录log
        $fiel_data = " 心跳查询响应: code:$code, heartbeat_cycle:$heartbeat_cycle".PHP_EOL."frame".bin2hex($message).'时间:'.Carbon::now();
        $redis_data = json_encode(['frame_name'=>'心跳查询响应', 'protocol_name'=>self::$protocol_name, 'version'=>$version, 'parameter'=>array('code'=>$code, 'heartbeat_cycle'=>$heartbeat_cycle), 'frame'=>bin2hex($message), 'date'=>strval(Carbon::now())]).'+';
        Common::record_log($code, $fiel_data, $redis_data);

        //处理数据
        $result = self::$controller->getHearbeat($code, $heartbeat_cycle);

    }


    //电表查表查询响应
    private static function get_meter_response($message){

        $readMeter = new EvseReadMeter();
        $frame_load = $readMeter($message);
        $code = $frame_load->code->getValue();
        $version = $frame_load->version->getValue();//版本号
        $result = $frame_load->result->getValue(); //结果
        $number = $frame_load->number->getValue(); //电表编号
        $meterDegree = $frame_load->meter_degree->getValue() / 100; //电表度数 kwh

        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . ", 电表查询响应：
            code:$code, number:$number, meterDegree:$meterDegree " . Carbon::now());


        //记录log
        $fiel_data = " 电表查表查询响应: code:$code, result:$result, number:$number, meterDegree:$meterDegree".PHP_EOL."frame".bin2hex($message).'时间:'.Carbon::now();
        $redis_data = json_encode(['frame_name'=>'电表查表查询响应', 'protocol_name'=>self::$protocol_name, 'version'=>$version, 'parameter'=>array('code'=>$code, 'number'=>$number, 'meterDegree'=>$meterDegree, 'result'=>$result), 'frame'=>bin2hex($message), 'date'=>strval(Carbon::now())]).'+';
        Common::record_log($code, $fiel_data, $redis_data);

        //处理数据
        $result = self::$controller->getMeter($code, $number, $meterDegree);



    }


    //营业额查询响应
    private static function get_turnover_response($message){

        $turnover = new EvseGetTurnover();
        $frame_load = $turnover($message);
        $code = $frame_load->code->getValue();
        $version = $frame_load->version->getValue();//版本号
        $coin_num = $frame_load->coin_num->getValue();
        $card_cost = $frame_load->card_cost->getValue();
        $card_time = $frame_load->card_time->getValue();

        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . ", 营业额查询响应：
            code:$code, coin_num:$coin_num, card_cost:$card_cost, card_time:$card_time " . Carbon::now());


        //记录log
        $fiel_data = " 营业额查询响应: code:$code, coin_num:$coin_num, card_cost:$card_cost, card_time:$card_time".PHP_EOL."frame".bin2hex($message).'时间:'.Carbon::now();
        $redis_data = json_encode(['frame_name'=>'营业额查询响应', 'protocol_name'=>self::$protocol_name, 'version'=>$version, 'parameter'=>array('code'=>$code, 'card_cost'=>$card_cost, 'card_time'=>$card_time), 'frame'=>bin2hex($message), 'date'=>strval(Carbon::now())]).'+';
        Common::record_log($code, $fiel_data, $redis_data);

        //处理数据
        $result = self::$controller->getTurnover($code, $coin_num, $card_cost, $card_time);


    }



    //通道查询响应
    private static function get_channel_response($message){

        $status = new EvseGetChannelStatus();
        $frame_load = $status($message);
        $code = $frame_load->code->getValue();
        $version = $frame_load->version->getValue();//版本号

        $order_number = $frame_load->order_number->getValue();
        $channel_num = $frame_load->channel_num->getValue();
        $current_average = $frame_load->current_average->getValue();
        $max_current = $frame_load->max_current->getValue();
        $current_base = $frame_load->current_base->getValue();
        $run_time = $frame_load->run_time->getValue();
        $left_time = $frame_load->left_time->getValue();
        $full_time = $frame_load->full_time->getValue();
        $payment_mode = $frame_load->payment_mode->getValue();
        $equipment_status = $frame_load->equipment_status->getValue();

        $data = ['current_average'=>$current_average, 'max_current'=>$max_current, 'current_base'=>$current_base, 'run_time'=>$run_time, 'left_time'=>$left_time,
            'full_time'=>$full_time, 'payment_mode'=>$payment_mode];

        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . ", 查询通道状态：
            code:$code, channel_num:$channel_num, order_number:$order_number, current_average:$current_average, max_current:$max_current
             current_base：$current_base, run_time：$run_time, left_time：$left_time, full_time：$full_time,
             payment_mode:$payment_mode, equipment_status:$equipment_status
             " . Carbon::now());

        $date = Carbon::now();
        Logger::log($code, __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 通道查询响应时间 date: $date" );
        //Logger::log($code, __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 营业额查询响应参数 code:$code, coin_num:$coin_num,card_cost:$card_cost,card_time:$card_time " );
        Logger::log($code, __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 通道查询响应帧 frame: ".bin2hex($message) );


        //处理数据
        $result = self::$controller->channelStatus($code, $channel_num, $order_number, $data, $equipment_status);



    }


    //查询参数响应
    private static function get_parameter_response($message){

        $parameter = new EvseGetParameter();
        $frame_load = $parameter($message);

        $code = $frame_load->code->getValue();
        $version = $frame_load->version->getValue();//版本号
        $channel_maximum_current = $frame_load->channel_maximum_current->getValue();
        $full_judge = $frame_load->full_judge->getValue();
        $clock = $frame_load->clock->getValue();
        $disconnect = $frame_load->disconnect->getValue();
        $power_base = $frame_load->power_base->getValue();
        $coin_rate = $frame_load->coin_rate->getValue();
        $card_rate = $frame_load->card_rate->getValue();

        $clock = '20'.$clock;
        $clock = substr($clock, 0,4).'-'.substr($clock, 4,2).'-'.substr($clock, 6,2).' '.substr($clock, 8,2).':'.substr($clock, 10,2).':'.substr($clock, 12,2);

        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . ", 查询参数响应：
            code:$code, channel_maximum_current:$channel_maximum_current, power_base:$power_base, coin_rate:$coin_rate, card_rate:$card_rate, 
            full_judge:$full_judge, disconnect:$disconnect, clock:$clock, 
                 " . Carbon::now());

        Logger::log($code, __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 查询参数响应时间 date: ".Carbon::now() );
        //Logger::log($code, __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 查询参数响应参数 code:$code, coin_num:$coin_num,card_cost:$card_cost,card_time:$card_time " );
        Logger::log($code, __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 查询参数响应帧 frame: ".bin2hex($message) );

        //记录log
        $fiel_data = " 查询参数响应: code:$code, channel_maximum_current:$channel_maximum_current, full_judge:$full_judge, clock:$clock, disconnect:$disconnect, power_base:$power_base, coin_rate:$coin_rate, card_rate:$card_rate".PHP_EOL."frame".bin2hex($message).'时间:'.Carbon::now();
        $redis_data = json_encode(['frame_name'=>'查询参数响应', 'protocol_name'=>self::$protocol_name, 'version'=>$version, 'parameter'=>array('code'=>$code, 'channel_maximum_current'=>$channel_maximum_current, 'full_judge'=>$full_judge, 'clock'=>$clock, 'disconnect'=>$disconnect, 'power_base'=>$power_base, 'coin_rate'=>$coin_rate, 'card_rate'=>$card_rate), 'frame'=>bin2hex($message), 'date'=>strval(Carbon::now())]).'+';
        Common::record_log($code, $fiel_data, $redis_data);


        //处理数据
        $result = self::$controller->getParameter($code, $channel_maximum_current,$full_judge,$clock,$disconnect,$power_base,$coin_rate,$card_rate);


    }


    //查询ID响应
    private static function get_id_response($message){

        $getId = new EvseGetId();
        $frame_load = $getId($message);
        $code = $frame_load->code->getValue();
        $deviceId = $frame_load->device->getValue();


        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . ", 查询ID响应：
            code:$code, deviceId:$deviceId " . Carbon::now());


    }


    //查询设备编号响应
    private static function get_identification_response($message){

        $getDeviceIdentification = new EvseGetDeviceIdentification();
        $frame_load = $getDeviceIdentification($message);
        $code = $frame_load->code->getValue();
        $deviceIdentification = $frame_load->deviceIdentification->getValue();


        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . ", 查询设备编号响应：
            code:$code, deviceIdentification:$deviceIdentification " . Carbon::now());


    }



    //查询时间
    private static function get_date_time_response($message){

        $getDateTime = new EvseGetDateTime();
        $frame_load = $getDateTime($message);

        $code = $frame_load->code->getValue();
        $version = $frame_load->version->getValue();//版本号
        $dateTime = $frame_load->date_time->getValue();


        //记录log
        $fiel_data = " 查询时间响应: code:$code".PHP_EOL."frame".bin2hex($message).'时间:'.Carbon::now();
        $redis_data = json_encode(['frame_name'=>'查询时间响应', 'protocol_name'=>self::$protocol_name, 'version'=>$version, 'parameter'=>array('code'=>$code, 'dateTime'=>$dateTime), 'frame'=>bin2hex($message), 'date'=>strval(Carbon::now())]).'+';

        Common::record_log($code, $fiel_data, $redis_data);

        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . ", 查询时间：
            code:$code, dateTime:$dateTime," . Carbon::now());

    }





    //记录log,包括参数,帧,时间
    private static function record_log($code, $fiel_data, $redis_data){

        //保存到对应的桩log中
        Logger::log($code, __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . $fiel_data );

        $prefix = env('FRAME_PREFIX'); //前缀
        $term_validity = env('FRAME_TERM_VALIDITY');//有效期
        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " prefix:$prefix, term_validity:$term_validity ");
        //追加log
        $str_len = Redis::append($prefix.$code,$redis_data);//,'EX',$term_validity "'$prefix.$code'"
        Redis::expire($prefix.$code, $term_validity);

        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 帧存入redis str_len:$str_len ");

    }
















}