<?php
/**
 * Created by PhpStorm.
 * User: lingf
 * Date: 2017-03-02
 * Time: 18:20
 */

namespace Wormhole\Protocols\LcV3\Protocol\Evse;



use Wormhole\Protocols\LcV3\Protocol\Frame;
use Wormhole\Protocols\Library\BIN;


class SetDateTime extends Frame
{


    protected $instructions = 0x1306;

    /**
     * 响应结果 成功 0x01 失败 0x00
     * @var int
     */
    protected $result = [BIN::class,1,TRUE];




}