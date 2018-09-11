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
use Wormhole\Protocols\Library\BCD;


class OfflineStart extends Frame
{


    protected $instructions = 0x1106;

    /**
     * 通道号
     * @var int
     */
    protected $channel_num = [BIN::class,1,TRUE];

    /**
     * 单号
     * @var int
     */
    protected $odd_num = [BIN::class,1,TRUE];


    /**
     * 响应结果 成功 0x01 失败 0x00
     * @var int
     */
    protected $result = [BIN::class,1,TRUE];


}