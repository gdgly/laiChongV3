<?php
/**
 * Created by PhpStorm.
 * User: lingf
 * Date: 2017-03-02
 * Time: 18:20
 */

namespace Wormhole\Protocols\LcV3\Protocol\Evse;



use Wormhole\Protocols\Library\BCD;
use Wormhole\Protocols\LcV3\Protocol\Frame;
use Wormhole\Protocols\Library\BIN;


class OfflineStart extends Frame
{


    protected $instructions = 0x1106;


    /**
     * 单号
     * @var int
     */
    protected $odd_num = [BIN::class,1,TRUE];

    /**
     * 通道号
     * @var int
     */
    protected $channel_num = [BIN::class,1,TRUE];


    /**
     * 启动类型
     * @var int
     */
    protected $start_type = [BIN::class,1,TRUE];

    /**
     * 金额
     * @var int
     */
    protected $balance = [BIN::class,2,TRUE];

    /**
     * 充电时长
     * @var int
     */
    protected $duration = [BIN::class,2,TRUE];

    /**
     * 卡号
     * @var int
     */
    protected $card_num = [BIN::class,4,FALSE];





}