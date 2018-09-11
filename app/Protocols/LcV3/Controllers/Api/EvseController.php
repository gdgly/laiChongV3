<?php
namespace Wormhole\Protocols\LcV3\Controllers\Api;
/**
 * Created by PhpStorm.
 * User: lingf
 * Date: 2016-11-29
 * Time: 15:52
 */
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Wormhole\Http\Controllers\Api\BaseController;
use Wormhole\Protocols\Library\Tools;
use Illuminate\Support\Facades\Config;
use Illuminate\Http\Request;
use Wormhole\Protocols\Library;
use Wormhole\Protocols\LcV3\Protocol\Evse\Heartbeat;
use Wormhole\Protocols\LcV3\Protocol\PortInfo;
use Wormhole\Protocols\LcV3\Protocol\Server\Renew;
use Wormhole\Protocols\Library\Common;

//设置参数队列
use Wormhole\Protocols\LcV3\Jobs\CheckSetParameter;
//检测启动续费停止是否收到桩响应
use Wormhole\Protocols\LcV3\Jobs\CheckResponse;
//启动充电队列
use Wormhole\Protocols\LcV3\Jobs\StartChargeSend;
//续费下发
use Wormhole\Protocols\LcV3\Jobs\RenewSend;
//停止充电下发
use Wormhole\Protocols\LcV3\Jobs\StopChargeSend;

use Wormhole\Validators\StartChargeValidator;
use Wormhole\Validators\RenewValidator;
use Wormhole\Validators\StopChargeValidator;

use Wormhole\Protocols\LcV3\Models\Evse;
use Wormhole\Protocols\LcV3\Models\Port;
use Wormhole\Protocols\LcV3\Models\ChargeOrderMapping;
use Wormhole\Protocols\LcV3\Models\Turnover;
use Wormhole\Protocols\LcV3\Models\ChargeRecords;


use Wormhole\Protocols\LcV3\Protocol\Frame;
use Wormhole\Protocols\LcV3\EventsApi;
//启动充电
use Wormhole\Protocols\LcV3\Protocol\Server\StartCharge as ServerStartCharge;

//续费
use Wormhole\Protocols\LcV3\Protocol\Server\Renew as ServerRenew;

//停止充电
use Wormhole\Protocols\LcV3\Protocol\Server\StopCharge as ServerStopCharge;

//心跳设置
use Wormhole\Protocols\LcV3\Protocol\Server\SetHearbeat as ServerSetHearbeat;

//服务器信息设置
use Wormhole\Protocols\LcV3\Protocol\Server\ServerInfo as ServerInfo;

//清空营业额
use Wormhole\Protocols\LcV3\Protocol\Server\EmptyTurnover as ServerEmptyTurnover;

//设置参数
use Wormhole\Protocols\LcV3\Protocol\Server\SetParameter as ServerSetParameter;

//设置ID
use Wormhole\Protocols\LcV3\Protocol\Server\SetId as ServerSetId;

//查询ID
use Wormhole\Protocols\LcV3\Protocol\Server\GetId as ServerGetId;

//心跳查询
use Wormhole\Protocols\LcV3\Protocol\Server\GetHearbeat as ServerGetHeartbeat;

//电表抄表
use Wormhole\Protocols\LcV3\Protocol\Server\ReadMeter as ServerReadMeter;

//营业额查询
use Wormhole\Protocols\LcV3\Protocol\Server\GetTurnover as ServerGetTurnover;

//通道查询
use Wormhole\Protocols\LcV3\Protocol\Server\GetChannelStatus as ServerGetChannelStatus;

//查询参数
use Wormhole\Protocols\LcV3\Protocol\Server\GetParameter as ServerGetParameter;

//信号强度查询
use Wormhole\Protocols\LcV3\Protocol\Server\Signal as ServerSignal;

//修改时间
use Wormhole\Protocols\LcV3\Protocol\Server\SetTime as ServerSetTime;

//获取时间
use Wormhole\Protocols\LcV3\Protocol\Server\GetDateTime as ServerGetDateTime;

//获取设备识别号
use Wormhole\Protocols\LcV3\Protocol\Server\GetDeviceIdentification as ServerGetDeviceIdentification;

use Wormhole\Protocols\LcV3\Protocol\Evse\Sign as EvseSign;
use Ramsey\Uuid\Uuid;
use Wormhole\Protocols\LcV3\Protocol\Server\Sign as ServerSign;
use Wormhole\Protocols\LcV3\Protocol\Evse\Report;
use Illuminate\Support\Facades\Redis;
use Wormhole\Protocols\MonitorServer;

use Wormhole\Protocols\Library\Log as Logger;



class EvseController extends BaseController
{

    public function test(){
        var_dump(5555);
    }


    /*****************************************控制类****************************************************/

    //启动充电
    public function startCharge(StartChargeValidator $chargeValidator){
        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " startCharge start");
        $params = $this->request->all();
        $params = $params['params'];

        $validator = $chargeValidator->make($params);
        if ($validator->fails()) {
            return $this->errorBadRequest($validator->messages());
        }

        $monitorOrderId = $params['order_id'];
        $monitorCode = $params['code'];//monitorCode
        $chargeType = $params['charge_type'];
        $chargeArgs = $params['charge_args'];
        $order_id = $this->getOrderId();


        $port = Port::where('monitor_code',$monitorCode)->first();//firstOrFail
        if(empty($port)){
            Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 启动充电,未找到数据 monitorCode: $monitorCode");
            return $this->response->array(
                [
                    'status' => false,
                    'message' => "command send failed"
                ]
            );
        }
        $code = $port->code;//桩编号
        //判断桩是否在线或则是否在空闲中
        $onlineStatus = $port->evse->online_status;
        $workStatus = $port->work_status;
        if($onlineStatus != 1 || $workStatus != 0){
            Logger::log($code, __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 启动充电,桩不在线或者不在空闲状态,online_status: ".$onlineStatus.' monitorCode:'.$monitorCode );
            Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . "启动充电,桩不在线或者不在空闲状态 online_status:$onlineStatus, workStatus:$workStatus ");
            return $this->response->array(
                [
                    'status' => false,
                    'message' => "command send failed"
                ]
            );
        }

        //启动充电
        $job = (new StartChargeSend($monitorOrderId, $monitorCode, $chargeType, $chargeArgs, $order_id))
            ->onQueue(env("APP_KEY"));
        dispatch($job);

        //返回下发结果
        return $this->response->array(
            [
                'status' => true,
                'message' => "command send sucesss"
            ]
        );





    }


    //续费
    public function renew(RenewValidator $renewValidator){

        //获取信息
        $params = $this->request->all();
        $params = $params['params'];
        $validator = $renewValidator->make($params);
        if ($validator->fails()) {
            return $this->errorBadRequest($validator->messages());
        }

        $monitorCode = $params['code'];       //桩编号
        $monitorOrderId = $params['order_id'];//monitor订单号
        $chargeType = $params['charge_type'];//续费模式
        $chargeArgs = $params['charge_args'];//续费参数

        $port = Port::where('monitor_code',$monitorCode)->first();//firstOrFail
        if(empty($port)){
            Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 续费,未找到数据 monitorCode:$monitorCode ");
            return $this->response->array(
                [
                    'status' => false,
                    'message' => "command send failed"
                ]
            );
        }
        $code = $port->code;//桩编号
        //判断桩是否在线或则是否在充电中
        $onlineStatus = $port->evse->online_status;
        $workStatus = $port->work_status;
        $orderId = $port->order_id;//协议订单号
        if($onlineStatus != 1 || $workStatus != 2){
            Logger::log($code, __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 续费,桩不在线或者不在充电状态,online_status:$onlineStatus, workStatus:$workStatus " );
            Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . "续费,桩不在线或者不在充电状态 online_status:$onlineStatus, workStatus:$workStatus");
            return $this->response->array(
                [
                    'status' => true,
                    'message' => "command send failed"
                ]
            );
        }


        //续费下发
        $job = (new RenewSend($monitorOrderId, $monitorCode, $chargeType, $chargeArgs, $orderId))
            ->onQueue(env("APP_KEY"));
        dispatch($job);



        return $this->response->array(
            [
                'status' => true,
                'message' => "command send sucesss"
            ]
        );





    }


    //停止充电
    public function stopCharge(StopChargeValidator $stopChargeValidator){

        $params = $this->request->all();
        $params = $params['params'];
        $validator = $stopChargeValidator->make($params);
        if ($validator->fails()) {
            return $this->errorBadRequest($validator->messages());
        }
        //$code = $params['code'];
        //$channelNumber = $params['channel_number'];
        $monitorOrderId = $params['order_id'];


        $port = Port::where('monitor_order_id',$monitorOrderId)->first();//firstOrFail
        $code = $port->code;//桩编号
        //判断桩是否在线或则是否在充电中
        $onlineStatus = $port->evse->online_status;
        $workStatus = $port->work_status;
        if($onlineStatus != 1 || $workStatus != 2){
            Logger::log($code, __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 停止充电,桩不在线或者不在充电状态,online_status:$onlineStatus, workStatus:$workStatus " );
            Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . "启动充电,桩不在线或者不在充电状态 online_status:$onlineStatus, workStatus:$workStatus");
            return $this->response->array(
                [
                    'status' => false,
                    'message' => "command send failed"
                ]
            );
        }

        //下发停止充电帧
        $job = (new StopChargeSend($monitorOrderId))
            ->onQueue(env("APP_KEY"));
        dispatch($job);


        return $this->response->array(
            [
                'status' => true,
                'message' => "command send sucesss"
            ]
        );







    }


    //实时充电数据
    public function chargeRealtime(){

        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 实时数据查询start ");
        $params = $this->request->all();
        $params = $params['params'];
        $monitorOrderId = $params['order_id'];
        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 实时数据查询 monitorOrderId:$monitorOrderId ");
        //取出枪口信息
        $port = Port::where('monitor_order_id', $monitorOrderId)->first();//firstOrFail
        //订单不存在返回false
        if(empty($port)){
            Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 实时数据查询,未找到相应数据 monitorOrderId:$monitorOrderId ");
            return $this->response->array(
                [
                    'status' =>false,
                    'message' => "command send sucesss",
                    'data'=>[]
                ]
            );
        }
        $code = $port->code;

        Logger::log($code, __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 实时数据,时间 date: ".Carbon::now()." 实时数据参数: monitorOrderId:$monitorOrderId" );

        $data = [];
        //判断充电模式是否为按时间充
        $chargeType = $port->charge_type; //充电模式
        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 实时数据查询,chargeType:$chargeType");
        //1为按时长充电
        if($chargeType == 1){
            //如果当前时间减去启动时间小于心跳周期

            //$data['order_id'] = $monitorOrderId;
            $startTime = $port->start_time; //启动时间
            $data['evse_id'] = $port->code;
            $data['channel_number'] = $port->port_number;
            $data['left_time'] = $port->left_time;   //剩余时间
            $data['already_charge_time'] = $port->charge_args - $data['left_time']; //已充时间
            //$data['sufficient_time'] = $data['duration'] - $data['left_time']; //已充时间 already_charge_time
            //刚启动,如果剩余时间是0,当前时间减去启动时间小于10分钟,剩余时间为充电时长
            if($port->left_time == 0 && time() - strtotime($startTime) < 600){
                $data['left_time'] = $port->charge_args;
                $data['already_charge_time'] = 0;
            }
        }
        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 实时数据查询 monitorOrderId:$monitorOrderId 
        already_charge_time:".$data['already_charge_time']." leftTime:".$data['left_time']);//." sufficient_time:".$data['sufficient_time']
        //返回数据
        return $this->response->array(
            [
                'status' =>true,
                'message' => "command send sucesss",
                'data'=>$data
            ]
        );


    }



    //查找充电记录
    public function chargeRecords(){

        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 查找充电记录start ");
        $params = $this->request->all();
        $params = $params['params'];
        $code = $params['evse_code'];
        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 查找充电记录 code:$code ");

        $data = [];
        $start_name = ['0'=>1, '1'=>5, '2'=>6];
        $evse = ChargeRecords::where("code",$code)->orderBy('charge_records_time', 'desc')->get();


        if(empty($evse)){
            Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 查找充电记录,未找到此桩充电信息 code:$code ");
            return $this->response->array(
                [
                    'status' =>true,
                    'message' => "command send sucesss",
                    'data'=>$data
                ]
            );

        }
        //Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 查找充电记录,未找到此桩充电信息 ".json_encode($evse));
        foreach ($evse as $k=>$v){
            //$v->startup_type = empty($v->startup_type) || $v->startup_type == 'null' ? 0 : $v->startup_type > 3 ? 0 : $v->startup_type;
            $v->startup_type = $v->startup_type == 0 || $v->startup_type == 1 || $v->startup_type == 2 ? $v->startup_type : 0;
            //Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 查找充电记录,未找到此桩充电信息 ".$v->startup_type.'-'.$v->monitor_order_id);
            $data[$k]['order_id'] = $v->monitor_order_id;
            $data[$k]['code'] = $v->code;
            $data[$k]['port_number'] = $v->port_number;
            $data[$k]['charge_type'] = $v->charge_type;
            $data[$k]['charge_args'] = $v->charge_args;
            $data[$k]['start_time'] = $v->start_time;
            $data[$k]['end_time'] = $v->end_time;
            $data[$k]['stop_reason'] = $v->stop_reason;
            $data[$k]['startup_type'] = $start_name[$v->startup_type];
            $data[$k]['left_time'] = $v->left_time;
            $data[$k]['actual_charge_time'] = $v->charge_args - $v->left_time; //实际充电时间

        }



        return $this->response->array(
            [
                'status' =>true,
                'message' => "command send sucesss",
                'data'=>$data
            ]
        );


    }







    /*****************************************设置类****************************************************/

    //心跳设置
    public function setHearbeat($code, $hearbeatCycle){


//        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 设置充电周期start ");
        if(empty($code) || empty($hearbeatCycle)){
            Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 设置心跳周期,monitor给参数为空 ");
            return false;
        }

        Logger::log($code, __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 心跳设置,时间 date: ".Carbon::now()." 心跳设置参数 code: $code, hearbeatCycle:$hearbeatCycle" );

        //获取workeId
        $evse = Evse::where("code",$code)->first(); //firstOrFail
        $workeId = $evse->worker_id;
        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 获取workeId:$workeId ");
        if(empty($workeId)){
            Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 设置心跳周期,workeId为空 ");
            //返回数据
            return false;
        }

        //从redis中取出流水号,如果没有设置为1
        $serialNumber = $this->getSerialNumber($code);

        //组装帧+
        $hearbeat = new ServerSetHearbeat();
        $hearbeat->code(intval($code));
        $hearbeat->serial_number(intval($serialNumber));
        $hearbeat->heartbeat_cycle(intval($hearbeatCycle));
        $frame = strval($hearbeat);
        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . "frame：$frame");
        $sendResult  = EventsApi::sendMsg($workeId,$frame);
        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . ",
            frame:".bin2hex($frame)."
            设置心跳周期:$sendResult " . Carbon::now());


        //记录log
        //$fiel_data = " 续费下发参数,monitorCode:$monitorCode, monitorOrderId:$monitorOrderId, chargeType:$chargeType, chargeArgs:$chargeArgs".PHP_EOL."frame: ".bin2hex($frame).'时间:'.Carbon::now();
        //$redis_data = " 续费下发".'-'.json_encode(array('monitorOrderId'=>$monitorOrderId, 'monitorCode'=>$monitorCode, 'chargeType'=>$chargeType, 'chargeArgs'=>$chargeArgs, 'orderId'=>$orderId)).'-'.bin2hex($frame).'-'.Carbon::now().'+';
        $fiel_data = " 心跳周期下发参数: hearbeatCycle:$hearbeatCycle".PHP_EOL."frame".bin2hex($frame).'时间:'.Carbon::now();
        $redis_data = json_encode(['frame_name'=>'心跳设置周期设置', 'protocol_name'=>env("PROTOCOL_NAME"), 'version'=>env("VERSION"), 'parameter'=>array('hearbeatCycle'=>$hearbeatCycle), 'frame'=>bin2hex($frame), 'date'=>strval(Carbon::now())]).'+';
        Common::record_log($code, $fiel_data, $redis_data);

        //$redisInfo = Redis::get('uni_qc_'.$code);
        //Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . "redisInfo:$redisInfo");

        //使用队列,检查心跳周期是否设置成功
        $job = (new CheckSetParameter('heartbeat_cycle', $hearbeatCycle, $code))
            ->onQueue(env("APP_KEY"))
            ->delay(Carbon::now()->addSeconds(5));
        dispatch($job);

        //返回下发结果
        if($sendResult){
            return true;
        }else{
            return false;
        }


    }

    //服务器信息设置
    public function setServerInfo($code, $domainName, $portNumber){

        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 服务器信息设置start ");

        if(empty($code) || empty($domainName) || empty($portNumber)){

            Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 服务器信息设置,monitor给的值为空 ");
            return false;

        }


        $date = Carbon::now();
        Logger::log($code, __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 服务器信息设置,时间 date: $date" );
        Logger::log($code, __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 服务器信息设置参数 code: $code, domainName:$domainName, portNumber:$portNumber" );

        //获取workeId
        $evse = Evse::where("code",$code)->first(); //firstOrFail
        $workeId = $evse->worker_id;
        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 获取workeId:$workeId ");
        if(empty($workeId)){
            Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 设置服务器信息,workeId为空 ");
            return false;
        }

        //从redis中取出流水号,如果没有设置为1
        $serialNumber = Redis::get($code.':serial_number');
        if(empty($serialNumber)){
            $serialNumber = 1;
            Redis::set($code.':serial_number',$serialNumber);
        }else{
            Redis::set($code.':serial_number',++$serialNumber);
        }
        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 服务器信息设置,流水号serialNumber：$serialNumber ");

        //组装帧
        $info = new ServerInfo();
        $info->code(intval($code));
        $info->serial_number(intval($serialNumber));
        $info->domain_name($domainName);
        $info->result($portNumber);
        $frame = strval($info);
        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . "frame：$frame");
        $sendResult  = EventsApi::sendMsg($workeId,$frame);
        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . ",
            frame:".bin2hex($frame)."
            服务器信息设置:$sendResult " . date('Y-m-d H:i:s', time()));

        Logger::log($code, __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 服务器信息设置帧frame: ".bin2hex($frame) );

        //记录log
        $fiel_data = " 服务器信息设置下发: code:$code,domainName:$domainName,portNumber:$portNumber".PHP_EOL."frame".bin2hex($frame).'时间:'.Carbon::now();
        $redis_data = json_encode(['frame_name'=>'服务器信息设置下发', 'protocol_name'=>env("PROTOCOL_NAME"), 'version'=>env("VERSION"), 'parameter'=>array('code'=>$code, 'portNumber'=>$portNumber), 'frame'=>bin2hex($frame), 'date'=>strval(Carbon::now())]).'+';

        Common::record_log($code, $fiel_data, $redis_data);


        //组装数组
        $name = ['domain_name', 'port_number'];
        $data = [$domainName, $portNumber];
        //使用队列,检查心跳周期是否设置成功
        $job = (new CheckSetParameter($name, $data, $code))
            ->onQueue(env("APP_KEY"))
            ->delay(Carbon::now()->addSeconds(5));
        dispatch($job);


        //返回下发结果
        if($sendResult){
            return true;
        }else{
            return false;
        }

    }

    //清空营业额
    public function emptyTurnover($code, $is_empty){

        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 清空营业额start ");

        if(empty($code) || $is_empty != 1){
            Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 清空营业额monitor给数据为空 ");
            return false;
        }


        $date = Carbon::now();
        Logger::log($code, __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 清空营业额,时间 date: $date" );
        Logger::log($code, __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 清空营业额参数 code: $code " );

        //获取workeId
        $evse = Evse::where("code",$code)->first(); //firstOrFail
        $workeId = $evse->worker_id;
        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 获取workeId:$workeId ");
        if(empty($workeId)){
            Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 清空营业额,workeId为空 ");
            return false;
        }

        //从redis中取出流水号,如果没有设置为1
        $serialNumber = Redis::get($code.':serial_number');
        if(empty($serialNumber)){
            $serialNumber = 1;
            Redis::set($code.':serial_number',$serialNumber);
        }else{
            Redis::set($code.':serial_number',++$serialNumber);
        }
        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 清空营业额,流水号serialNumber：$serialNumber ");

        //组装帧
        $turnover = new ServerEmptyTurnover();
        $turnover->code(intval($code));
        $turnover->serial_number(intval($serialNumber));
        $frame = strval($turnover);
        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . "frame：$frame");
        $sendResult  = EventsApi::sendMsg($workeId,$frame);
        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . ",
            frame:".bin2hex($frame)."
            清空营业额:$sendResult " . date('Y-m-d H:i:s', time()));

        Logger::log($code, __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 清空营业额参数下发帧 frame:  ".bin2hex($frame) );

        //记录log
        $fiel_data = " 清空营业额下发: code:$code".PHP_EOL."frame".bin2hex($frame).'时间:'.Carbon::now();
        $redis_data = json_encode(['frame_name'=>'清空营业额下发', 'protocol_name'=>env("PROTOCOL_NAME"), 'version'=>env("VERSION"), 'parameter'=>array('code'=>$code), 'frame'=>bin2hex($frame), 'date'=>strval(Carbon::now())]).'+';

        Common::record_log($code, $fiel_data, $redis_data);

        //返回下发结果
        if($sendResult){
            return true;
        }else{
            return false;
        }

    }






    //设置参数
    public function setParameter($code, $params){


        if(empty($params)){
            Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 设置参数,monitor给数据为空 ");
            return false;
        }
        $channelMaximumCurrent = $params['channel_maximum_current'];
        $powerBase = $params['power_base'];
        $coinRate = $params['coin_rate'];
        $cardRate = $params['card_rate'];
        $fullJudge = $params['full_judge'];
        $disconnect = $params['disconnect'];

        if(!is_numeric($channelMaximumCurrent) || !is_numeric($powerBase) || !is_numeric($coinRate) || !is_numeric($cardRate) || !is_numeric($fullJudge) || !is_numeric($disconnect)){
            Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 设置参数,monitor给数据为空 ");
            return false;
        }

        $date = Carbon::now();
        Logger::log($code, __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 设置参数,时间 date: $date" );
        Logger::log($code, __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 设置参数参数 code: $code " );

        //获取workeId
        $evse = Evse::where("code",$code)->first(); //firstOrFail
        $workeId = $evse->worker_id;
        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 获取workeId:$workeId ");
        if(empty($workeId)){
            Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 设置参数,workeId为空 ");
            return false;
        }

        //从redis中取出流水号,如果没有设置为1
        $serialNumber = Redis::get($code.':serial_number');
        if(empty($serialNumber)){
            $serialNumber = 1;
            Redis::set($code.':serial_number',$serialNumber);
        }else{
            Redis::set($code.':serial_number',++$serialNumber);
        }
        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 设置参数,流水号serialNumber：$serialNumber ");

        //组装帧
        $parameter = new ServerSetParameter();
        $parameter->code(intval($code));
        $parameter->serial_number(intval($serialNumber));
        $parameter->channel_maximum_current(intval($channelMaximumCurrent));
        $parameter->full_judge(intval($fullJudge));
        $parameter->disconnect(intval($disconnect));
        $parameter->power_base(intval($powerBase));
        $parameter->coin_rate(intval($coinRate));
        $parameter->card_rate(intval($cardRate));
        $frame = strval($parameter);
        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . "frame：$frame");
        $sendResult  = EventsApi::sendMsg($workeId,$frame);
        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . ",
            frame:".bin2hex($frame)."
            设置参数:$sendResult " . date('Y-m-d H:i:s', time()));

        Logger::log($code, __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 设置参数参数帧: frame: ".bin2hex($frame) );

        //记录log
        $fiel_data = " 设置参数下发: code:$code,channelMaximumCurrent:$channelMaximumCurrent,powerBase:$powerBase,coinRate:$coinRate,cardRate:$cardRate,fullJudge:$fullJudge,disconnect:$disconnect, clock=>$date".PHP_EOL."frame".bin2hex($frame).'时间:'.Carbon::now();
        $redis_data = json_encode(['frame_name'=>'设置参数下发', 'protocol_name'=>env("PROTOCOL_NAME"), 'version'=>env("VERSION"), 'parameter'=>$params, 'frame'=>bin2hex($frame), 'date'=>strval(Carbon::now())]).'+';

        Common::record_log($code, $fiel_data, $redis_data);

        $data = ['channel_maximum_current'=>$channelMaximumCurrent, "power_base"=>$powerBase, "coin_rate"=>$coinRate, "card_rate"=>$cardRate, "full_judge"=>$fullJudge, "disconnect"=>$disconnect, "clock"=>date('Y-m-d H:i:s', time()) ];
        //使用队列,检查设置参数是否设置成功
        $job = (new CheckSetParameter('parameter', $data, $code))
            ->onQueue(env("APP_KEY"))
            ->delay(Carbon::now()->addSeconds(6));
        dispatch($job);


        //返回下发结果
        if($sendResult){
            return true;
        }else{
            return false;
        }

    }



    //修改时间
    public function setDateTime($code, $dateTime){


        if(empty($code) || strtotime($dateTime) == false){
            Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 修改时间,monitor给数据为空 ");
            return false;
        }

        $date = Carbon::now();
        Logger::log($code, __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 修改时间,时间 date: $date" );
        Logger::log($code, __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 修改时间参数 code: $code " );

        //获取workeId
        $evse = Evse::where("code",$code)->first(); //firstOrFail
        $workeId = $evse->worker_id;
        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 获取workeId:$workeId ");
        if(empty($workeId)){
            Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 修改时间,workeId为空 ");
            return false;
        }

        $dateTime = date('YmdHis', strtotime($dateTime));
        $dateTime = substr($dateTime, 2);

        $setTime = new ServerSetTime();
        $setTime->code(intval($code));
        $setTime->date(intval($dateTime));
        $frame = strval($setTime);
        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . "frame：$frame");
        $sendResult  = EventsApi::sendMsg($workeId,$frame);
        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . ",
            frame:".bin2hex($frame)."
            修改时间:$sendResult " . date('Y-m-d H:i:s', time()));

        Logger::log($code, __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 修改时间下发帧 frame: ".bin2hex($frame) );

        //记录log
        $fiel_data = " 修改时间下发: code:$code, date:$dateTime".PHP_EOL."frame".bin2hex($frame).'时间:'.Carbon::now();
        $redis_data = json_encode(['frame_name'=>'修改时间下发', 'protocol_name'=>env("PROTOCOL_NAME"), 'version'=>env("VERSION"), 'parameter'=>array('code'=>$code, 'dateTime'=>$dateTime), 'frame'=>bin2hex($frame), 'date'=>strval(Carbon::now())]).'+';

        Common::record_log($code, $fiel_data, $redis_data);

        //返回下发结果
        if($sendResult){
            return $this->response->array(
                [
                    'status' => 201,
                    'message' => "command send sucesss"
                ]
            );
        }else{
            return $this->response->array(
                [
                    'status' => 500,
                    'message' => "command send failed"
                ]
            );
        }

    }



    /*****************************************查询类****************************************************/

    //心跳查询
    public function getHearbeat($code){

//        $params = $this->request->all();
//        $params = $params['params'];
//        $code = $params['code'];
        $hearbeatCycle = 0;
        if(empty($code)){
            Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 心跳查询,monitor给桩编号为空 ");
            Logger::log($code, __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 心跳查询,monitor给桩编号为空 " );
            return $hearbeatCycle;
        }



        //如果有心跳周期直接取出来
        //获取workeId
        $evse = Evse::where("code",$code)->first(); //firstOrFail
        if(!empty($evse)){
            //如果心跳有,则直接返回
            $hearbeatCycle = $evse->heartbeat_cycle;
            if(!empty($hearbeatCycle)){
                Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 查询心跳周期, hearbeatCycle:$hearbeatCycle ");
                return $hearbeatCycle;
            }
        }

        //没有,直接下发查找
        $workeId = $evse->worker_id;
        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 获取workeId:$workeId ");
        if(empty($workeId)){
            Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 心跳查询,workeId为空 ");
            return $hearbeatCycle;
        }

        //从redis中取出流水号,如果没有设置为1
        $serialNumber = Redis::get($code.':serial_number');
        if(empty($serialNumber)){
            $serialNumber = 1;
            Redis::set($code.':serial_number',$serialNumber);
        }else{
            Redis::set($code.':serial_number',++$serialNumber);
        }
        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 心跳查询,流水号serialNumber：$serialNumber ");

        //组装帧
        $hearbeat = new ServerGetHeartbeat();
        $hearbeat->code(intval($code));
        $hearbeat->serial_number($serialNumber);
        $frame = strval($hearbeat);
        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . "frame：$frame");
        $sendResult  = EventsApi::sendMsg($workeId,$frame);
        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . ",
            frame:".bin2hex($frame)."
            心跳查询:$sendResult " . date('Y-m-d H:i:s', time()));

        Logger::log($code, __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 心跳查询下发帧 frame: ".bin2hex($frame) );

        //记录log
        $fiel_data = " 心跳查询下发: code:$code".PHP_EOL."frame".bin2hex($frame).'时间:'.Carbon::now();
        $redis_data = json_encode(['frame_name'=>'心跳查询下发', 'protocol_name'=>env("PROTOCOL_NAME"), 'version'=>env("VERSION"), 'parameter'=>array('code'=>$code), 'frame'=>bin2hex($frame), 'date'=>strval(Carbon::now())]).'+';

        Common::record_log($code, $fiel_data, $redis_data);

        //返回下发结果
        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . "心跳周期下发结果 sendResult:$sendResult");
        Logger::log($code, __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 心跳查询下发帧 frame: 心跳周期下发结果 sendResult:$sendResult ");
        return $hearbeatCycle;



    }


    //电表抄表查询
    public function getMeter($code){

//        $params = $this->request->all();
//        $params = $params['params'];
//        $code = $params['code'];
        $chargedPower = 0;
        if(empty($code)){
            Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 电表抄表查询,monitor给桩编号为空 ");
            Logger::log($code, __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 电表抄表查询,monitor给桩编号为空 " );
            return $chargedPower;
        }



        //获取前一天电表总电量
        $frontDate = date("Y-m-d",strtotime("-1 day")); //TODO
        $condition = [
            ['code', '=', $code],
            ['stat_date', '=', $frontDate]
        ];
        $turnover = Turnover::where($condition)->first();
        if(!empty($turnover)){
            $chargedPower = $turnover->charged_power; //电表总电量
            if(!empty($chargedPower)){
                Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 查询电表读数, chargedPower: ".$chargedPower);
                //返回数据
                return $chargedPower;
            }
        }

        //获取workeId
        $evse = Evse::where("code",$code)->first(); //firstOrFail
        $workeId = $evse->worker_id;
        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 获取workeId:$workeId ");
        if(empty($workeId)){
            Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 电表查询,workeId为空 ");
            return $chargedPower;
        }

        //从redis中取出流水号,如果没有设置为1
        $serialNumber = Redis::get($code.':serial_number');
        if(empty($serialNumber)){
            $serialNumber = 1;
            Redis::set($code.':serial_number',$serialNumber);
        }else{
            Redis::set($code.':serial_number',++$serialNumber);
        }
        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 电表抄表查询,流水号serialNumber：$serialNumber ");

        //组装帧
        $readMeter = new ServerReadMeter();
        $readMeter->code(intval($code));
        $readMeter->serial_number($serialNumber);
        $frame = strval($readMeter);
        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . "frame：$frame");
        $sendResult  = EventsApi::sendMsg($workeId,$frame);
        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . ",
            frame:".bin2hex($frame)."
            电表查询:$sendResult " . date('Y-m-d H:i:s', time()));

        //记录log
        $fiel_data = " 电表抄表查询下发: code:$code".PHP_EOL."frame".bin2hex($frame).'时间:'.Carbon::now();
        $redis_data = json_encode(['frame_name'=>'电表抄表查询下发', 'protocol_name'=>env("PROTOCOL_NAME"), 'version'=>env("VERSION"), 'parameter'=>array('code'=>$code), 'frame'=>bin2hex($frame), 'date'=>strval(Carbon::now())]).'+';

        Common::record_log($code, $fiel_data, $redis_data);

        //返回结果
        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . "电表抄表查询下发结果:sendResult:$sendResult");
        Logger::log($code, __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 电表抄表查询下发结果:sendResult:$sendResult " );
        return $chargedPower;


    }


    //营业额查询
    public function getTurnover($code, $date=''){


        //初始化参数
        $data = array('date'=>'', 'coin_number'=>0, 'card_free'=>0, 'card_time'=>'', 'charged_power'=>0);
        //判断
        if(empty($code)){
            Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 电表抄表查询,monitor给桩编号为空 ");
            Logger::log($code, __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 电表抄表查询,monitor给桩编号为空 " );
            return $data;
        }

        //如果日期是空,则给前一天的数据
        if(empty($date)){
            $date = date("Y-m-d",strtotime("-1 day"));
        }
        $dateTime = Carbon::now();
        Logger::log($code, __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 营业额查询,时间 date: $dateTime" );
        Logger::log($code, __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 营业额查询参数 code: $code, date:$date " );

        $condition = [
            ['code', '=', $code],
            ['stat_date', '=', $date]
        ];

        $turnover = Turnover::where($condition)->first();
        if(empty($turnover)){
            Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 营业额查询,未找到数据 ");
            return $data;
        }

        //计算电量
        $power = 0;
        $chargedPower = $turnover->charged_power_time;
        $chargedPower = json_decode($chargedPower, 1);
        foreach ($chargedPower as $v){
            $power = $v + $power;
        }

        $data['date'] = $turnover->stat_date;
        $data['coin_number'] = $turnover->coin_number;
        $data['card_free'] = $turnover->card_free / 100; //单位元
        $data['card_time'] = $turnover->card_time;
        $data['charged_power'] = $power / 100;//kwh

        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 营业额查询结果
         date:".$data['date']." coinNumber:".$data['coin_number']." cardFree:".$data['card_free']." cardTime:".$data['card_time']." chargedPower:".$data['charged_power']);

        //返回数据
        return $data;



    }



    //通道查询
    public function getChannel($code, $channelNum){

//        $params = $this->request->all();
//        $params = $params['params'];
//        $code = $params['code'];
//        $channelNum = $params['channelNum'];

        $date = Carbon::now();
        Logger::log($code, __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 通道查询,时间 date: $date" );
        Logger::log($code, __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 通道查询参数 code: $code, channelNum:$channelNum " );

        //获取workeId
        $evse = Evse::where("code",$code)->first(); //firstOrFail
        $workeId = $evse->worker_id;
        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 获取workeId:$workeId ");
        if(empty($workeId)){
            Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 清空营业额,workeId为空 ");
            return false;
        }


        //从redis中取出流水号,如果没有设置为1
        $serialNumber = Redis::get($code.':serial_number');
        if(empty($serialNumber)){
            $serialNumber = 1;
            Redis::set($code.':serial_number',$serialNumber);
        }else{
            Redis::set($code.':serial_number',++$serialNumber);
        }
        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 通道查询,流水号serialNumber：$serialNumber ");

        $status = new ServerGetChannelStatus();
        $status->code(intval($code));
        $status->serial_number($serialNumber);
        $status->channel_num($channelNum);
        $frame = strval($status);
        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . "frame：$frame");
        $sendResult  = EventsApi::sendMsg($workeId,$frame);
        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . ",
            frame:".bin2hex($frame)."
            通道查询:$sendResult " . date('Y-m-d H:i:s', time()));

        Logger::log($code, __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 通道查询下发帧 frame: ".bin2hex($frame) );

        //记录log
//        $content = "心跳周期查询: code:$code,channelNum:$channelNum".'-'.bin2hex($frame).'-'.$date;
//        $this->record_log($code, $content);

        //返回下发结果
        if($sendResult){
            return $this->response->array(
                [
                    'status' => 201,
                    'message' => "command send sucesss"
                ]
            );
        }else{
            return $this->response->array(
                [
                    'status' => 500,
                    'message' => "command send failed"
                ]
            );
        }

    }


    //查询参数
    public function getParameter($code){

        //初始化数据
        $data = array('channel_maximum_current'=>0, 'power_base'=>0, 'coin_rate'=>0, 'card_rate'=>0, 'full_judge'=>0, 'disconnect'=>0, 'clock'=>'');
        if(empty($code)){
            Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 查询参数,monitor给桩编号为空 ");
            Logger::log($code, __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 查询参数,monitor给桩编号为空 " );
            return $data;
        }



        //获取workeId
        $evse = Evse::where("code",$code)->first(); //firstOrFail
        if(!empty($evse)){
            $parameter = $evse->parameter;
            $parameter = json_decode($parameter, 1);
            if(!empty($parameter)){
                Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 查询参数 ");
                //桩当前时间转换成正确格式
                //$clock = '20'.$parameter['clock'];
                //$clock = substr($clock, 0,4).'-'.substr($clock, 4,2).'-'.substr($clock, 6,2).' '.substr($clock, 8,2).':'.substr($clock, 10,2).':'.substr($clock, 12,2);
                $data = array('channel_maximum_current'=>$parameter['channel_maximum_current'], 'power_base'=>$parameter['power_base'], 'coin_rate'=>$parameter['coin_rate'], 'card_rate'=>$parameter['card_rate'], 'full_judge'=>$parameter['full_judge'], 'disconnect'=>$parameter['disconnect'], 'clock'=>$parameter['clock']);
                return $data;
            }
        }


        $workeId = $evse->worker_id;
        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 获取workeId:$workeId ");
        if(empty($workeId)){
            Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 清空营业额,workeId为空 ");
            return false;
        }

        //从redis中取出流水号,如果没有设置为1
        $serialNumber = Redis::get($code.':serial_number');
        if(empty($serialNumber)){
            $serialNumber = 1;
            Redis::set($code.':serial_number',$serialNumber);
        }else{
            Redis::set($code.':serial_number',++$serialNumber);
        }
        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 通道查询,流水号serialNumber：$serialNumber ");

        $parameter = new ServerGetParameter();
        $parameter->code(intval($code));
        $parameter->serial_number($serialNumber);
        $frame = strval($parameter);
        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . "frame：$frame");
        $sendResult  = EventsApi::sendMsg($workeId,$frame);
        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . ",
            frame:".bin2hex($frame)."
            查询参数:$sendResult " . date('Y-m-d H:i:s', time()));

        Logger::log($code, __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 查询参数下发帧 frame: ".bin2hex($frame) );

        //记录log
        $fiel_data = " 查询参数下发: code:$code".PHP_EOL."frame".bin2hex($frame).'时间:'.Carbon::now();
        $redis_data = json_encode(['frame_name'=>'查询参数下发', 'protocol_name'=>env("PROTOCOL_NAME"), 'version'=>env("VERSION"), 'parameter'=>array('code'=>$code), 'frame'=>bin2hex($frame), 'date'=>strval(Carbon::now())]).'+';

        Common::record_log($code, $fiel_data, $redis_data);
        //返回下发结果
        Logger::log($code, __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 查询参数下发结果 sendResult:$sendResult" );
        return $data;



    }


    //查询设备识别号
    public function getDeviceIdentification($code){

//        $params = $this->request->all();
//        $params = $params['params'];
//        $code = $params['code'];
        $identificationNumber = 0;
        if(empty($code)){
            Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 查询设备识别号,monitor给桩编号为空 ");
            Logger::log($code, __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 查询设备识别号,monitor给桩编号为空 " );
            return $identificationNumber;
        }


        //获取workeId
        $evse = Evse::where("code",$code)->first(); //firstOrFail
        if(!empty($evse)){
            $identificationNumber = $evse->identification_number;
            Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 查询设备识别号 $identificationNumber ");
            //返回数据
            return $identificationNumber;
        }


        $workeId = $evse->worker_id;
        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 查询设备识别号:$workeId ");
        if(empty($workeId)){
            Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 查询设备识别号,workeId为空 ");
            return $identificationNumber;
        }

        //从redis中取出流水号,如果没有设置为1
        $serialNumber = Redis::get($code.':serial_number');
        if(empty($serialNumber)){
            $serialNumber = 1;
            Redis::set($code.':serial_number',$serialNumber);
        }else{
            Redis::set($code.':serial_number',++$serialNumber);
        }
        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 查询设备识别号,流水号serialNumber：$serialNumber ");


        $deviceIdentification = new ServerGetDeviceIdentification();
        $deviceIdentification->code(intval($code));
        $deviceIdentification->serial_number(intval($serialNumber));
        $frame = strval($deviceIdentification);
        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . "frame：$frame");
        $sendResult  = EventsApi::sendMsg($workeId,$frame);
        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . ",
            frame:".bin2hex($frame)."
            查询设备识别号:$sendResult " . date('Y-m-d H:i:s', time()));


        //返回下发结果
        Logger::log($code, __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 查询设备识别号下发结果 $sendResult " );
        return $identificationNumber;


    }




    //查询信号强度
    public function getSignalIntensity($code){

        $signalIntensity = 0;
        if(empty($code)){
            Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 查询信号强度,monitor给桩编号为空 ");
            Logger::log($code, __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 查询信号强度,monitor给桩编号为空 " );
            return $signalIntensity;
        }

        $evse = Evse::where("code",$code)->first();
        if(!empty($evse)){
            $signalIntensity = $evse->signal_intensity;
            Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 查询信号强度signalIntensity:$signalIntensity ");
        }

        return $signalIntensity;

    }





    //时间查询
    public function getDateTime(){


        $params = $this->request->all();
        $params = $params['params'];
        $code = $params['code'];

        //获取workeId
        $evse = Evse::where("code",$code)->first(); //firstOrFail
        if(empty($evse)){
            Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 查询时间,未找到数据 ");
            //返回数据
            return $this->response->array(
                [
                    'status' => 500,
                    'message' => "command send failed",
                ]
            );
        }


        $workeId = $evse->worker_id;
        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 获取workeId:$workeId ");
        if(empty($workeId)){
            Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 清空营业额,workeId为空 ");
            return false;
        }

        //从redis中取出流水号,如果没有设置为1
        $serialNumber = Redis::get($code.':serial_number');
        if(empty($serialNumber)){
            $serialNumber = 1;
            Redis::set($code.':serial_number',$serialNumber);
        }else{
            Redis::set($code.':serial_number',++$serialNumber);
        }
        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 查询时间,流水号serialNumber：$serialNumber ");


        $dateTime = new ServerGetDateTime();
        $dateTime->code(intval($code));
        $dateTime->serial_number(intval($serialNumber));
        $frame = strval($dateTime);
        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . "frame：$frame");
        $sendResult  = EventsApi::sendMsg($workeId,$frame);
        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . ",
            frame:".bin2hex($frame)."
            查询时间:$sendResult " . date('Y-m-d H:i:s', time()));

        //记录log
        $fiel_data = " 时间查询: code:$code".PHP_EOL."frame".bin2hex($frame).'时间:'.Carbon::now();
        $redis_data = json_encode(['frame_name'=>'时间查询', 'protocol_name'=>env("PROTOCOL_NAME"), 'version'=>env("VERSION"), 'parameter'=>array('code'=>$code), 'frame'=>bin2hex($frame), 'date'=>strval(Carbon::now())]).'+';

        Common::record_log($code, $fiel_data, $redis_data);


        //返回下发结果
        if($sendResult){
            return $this->response->array(
                [
                    'status' => 201,
                    'message' => "command send sucesss",
                ]
            );
        }else{
            return $this->response->array(
                [
                    'status' => 500,
                    'message' => "command send failed",
                ]
            );
        }



    }







    //设置id
    public function setId(){

        $params = $this->request->all();
        $params = $params['params'];
        $code = $params['code'];
        $deviceId = $params['device_id'];//设备id

        //获取workeId
        $evse = Evse::where("code",$code)->first(); //firstOrFail
        if(empty($evse)){
            Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 设置id,未找到数据 ");
            //返回数据
            return $this->response->array(
                [
                    'status' => 500,
                    'message' => "command send failed",
                ]
            );
        }


        $workeId = $evse->worker_id;
        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 获取workeId:$workeId ");
        if(empty($workeId)){
            Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 设置id,workeId为空 ");
            return false;
        }

        //从redis中取出流水号,如果没有设置为1
        $serialNumber = Redis::get($code.':serial_number');
        if(empty($serialNumber)){
            $serialNumber = 1;
            Redis::set($code.':serial_number',$serialNumber);
        }else{
            Redis::set($code.':serial_number',++$serialNumber);
        }
        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 设置id,流水号serialNumber：$serialNumber ");


        $setId = new ServerSetId();
        $setId->code(intval($code));
        $setId->serial_number(intval($serialNumber));
        $setId->device(intval($deviceId));
        $frame = strval($setId);
        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . "frame：$frame");
        $sendResult  = EventsApi::sendMsg($workeId,$frame);
        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . ",
            frame:".bin2hex($frame)."
            设置id:$sendResult " . date('Y-m-d H:i:s', time()));


        //返回下发结果
        if($sendResult){
            return $this->response->array(
                [
                    'status' => 201,
                    'message' => "command send sucesss",
                ]
            );
        }else{
            return $this->response->array(
                [
                    'status' => 500,
                    'message' => "command send failed",
                ]
            );
        }




    }


    //查询ID
    public function getId(){

        $params = $this->request->all();
        $params = $params['params'];
        $code = $params['code'];

        //获取workeId
        $evse = Evse::where("code",$code)->first(); //firstOrFail
        if(empty($evse)){
            Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 查询ID,未找到数据 ");
            //返回数据
            return $this->response->array(
                [
                    'status' => 500,
                    'message' => "command send failed",
                ]
            );
        }


        $workeId = $evse->worker_id;
        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 查询ID:$workeId ");
        if(empty($workeId)){
            Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 查询ID,workeId为空 ");
            return false;
        }

        //从redis中取出流水号,如果没有设置为1
        $serialNumber = Redis::get($code.':serial_number');
        if(empty($serialNumber)){
            $serialNumber = 1;
            Redis::set($code.':serial_number',$serialNumber);
        }else{
            Redis::set($code.':serial_number',++$serialNumber);
        }
        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 查询ID,流水号serialNumber：$serialNumber ");


        $getId = new ServerGetId();
        $getId->code(intval($code));
        $getId->serial_number(intval($serialNumber));
        $frame = strval($getId);
        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . "frame：$frame");
        $sendResult  = EventsApi::sendMsg($workeId,$frame);
        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . ",
            frame:".bin2hex($frame)."
            查询ID:$sendResult " . date('Y-m-d H:i:s', time()));


        //返回下发结果
        if($sendResult){
            return $this->response->array(
                [
                    'status' => 201,
                    'message' => "command send sucesss",
                ]
            );
        }else{
            return $this->response->array(
                [
                    'status' => 500,
                    'message' => "command send failed",
                ]
            );
        }


    }




    //获取参数
    public function getParameterData(){

        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 获取参数start ");
        $params = $this->request->all();
        $params = $params['params'];
        //桩编号
        $code = $params['evse_code']; //桩编号
        $date = $params['date'];      //查询营业额日期,如果为空默认前一天

        $data = [];

        //心跳查询
        $data['heartbeat_cycle'] = $this->getHearbeat($code);
        //查询前一天总电量
        $data['before_charged_power'] = $this->getMeter($code);
        //营业额查询
        $data['turnover'] = $this->getTurnover($code, $date); //date coin_number card_free card_time charged_power
        //通道状态查询
        //$this->getChannel($code, $channel);
        //查询参数
        $data['parameter'] = $this->getParameter($code);//channel_maximum_current  power_base  coin_rate card_rate full_judge disconnect clock
        //查询设备识别号
        $data['device_identification'] = $this->getDeviceIdentification($code);
        //查找信号强度
        $data['signal_intensity'] = $this->getSignalIntensity($code);


        return $this->response->array(
            [
                'status' => 201,
                'message' => "command send sucesss",
                'data'=>$data
            ]
        );




    }


    //设置参数
    public function setParameterData(){



        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 设置参数start ");
        //获取参数
        $params = $this->request->all();
        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 设置参数  ".json_encode($params));
        $params = $params['params'];
        $code = $params['evse_code'];

        foreach ($params as $k=>$v){

            switch ($k) {
                case 'heartbeat_cycle':
                    //心跳周期
                    //$hearbeatCycle = $params['hearbeat_cycle'];
                    Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 心跳设置参数== $v");
                    //设置心跳周期
                    $hearbeatCycleRes = $this->setHearbeat($code, $v);
                    break;
                case 'server_info':
                    //域名
                    $domainName = $v['domain_name'];
                    //端口号
                    $portNumber = $v['port_number'];
                    Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 服务器设置参数== $domainName, $portNumber");
                    //服务器信息设置
                    $serverInfoRes = $this->setServerInfo($code, $domainName, $portNumber);
                    break;
                case 'parameter':
                    //设置参数
                   // $parameter = $params['parameter']; //channel_maximum_current  power_base  coin_rate card_rate full_judge disconnect
                    Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 参数设置参数== ".json_encode($v));
                    //设置参数
                    $parameterRes = $this->setParameter($code, $v);
                    break;
                case 'evse_date':
                    //修改桩上的时间
                    //$dateTime = $params['evse_date']; //修改桩上的时间
                    Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 修改时间== $v");
                    //修改时间
                    $parameterRes = $this->setDateTime($code, $v);
                    break;
                case 'is_empty_turnover':
                    //是否清空营业额
                    //$is_empty = $params['is_empty_turnover'];
                    Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 清空营业额== $v");
                    //清空营业额
                    $emptyTurnoverRes = $this->emptyTurnover($code, $v);
                    break;

            }
        }




        return $this->response->array(
            [
                'status' => 201,
                'message' => "command send sucesss",
                'data'=>true
            ]
        );


    }








    //获取redis存储log
    public function code_log(){

        $params = $this->request->all();
        $params = $params['params'];
        $code = $params['code'];
        $data = Redis::keys($code.'*');

        return $data;

//        $key = Redis::keys('c*');
//        $aa = Redis::get($key[0]);
//        var_dump($aa);die;

    }





    //获取流水号
    private function getSerialNumber($code){

        //从redis中取出流水号,如果没有设置为1
        $serialNumber = Redis::get($code.':serial_number');
        if(empty($serialNumber)){
            $serialNumber = 1;
            Redis::set($code.':serial_number',$serialNumber);
        }else{
            Redis::set($code.':serial_number',++$serialNumber);
        }
        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 心跳设置,流水号serialNumber：$serialNumber ");

        return $serialNumber;

    }

    //获取订单号
    private function getOrderId(){

        //从redis中取出订单号,如果没有设置为0
        $orderId = Redis::get('order_id');
        if(empty($orderId)){
            $orderId = 1;
            Redis::set('order_id',$orderId);
        }else{
            //如果大于等于255则重置为1
            if($orderId >= 255){
                Redis::set('order_id',1);
            }else{
                Redis::set('order_id',++$orderId);
            }

        }

        Log::debug(__NAMESPACE__ . "/" . __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " 订单号 order_id:$orderId ");

        return $orderId;

    }


}