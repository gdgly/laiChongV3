<?php
/**
 * Created by PhpStorm.
 * User: lingf
 * Date: 2017-03-02
 * Time: 18:20
 */

namespace Wormhole\Protocols\LcV3\Protocol\Server;



use Wormhole\Protocols\LcV3\Protocol\Frame;
use Wormhole\Protocols\Library\BIN;


class StopCharge extends Frame
{


    protected $instructions = 0x1203;

    /**
     * 订单号
     * @var int
     */
    protected $order_number = [BIN::class,1,TRUE];

    /**
     * 通道号
     * @var int
     */
    protected $channel_number = [BIN::class,1,TRUE];




}