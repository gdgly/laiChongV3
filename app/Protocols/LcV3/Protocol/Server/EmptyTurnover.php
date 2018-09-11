<?php
/**
 * Created by PhpStorm.
 * User: lingf
 * Date: 2017-03-02
 * Time: 18:20
 */

namespace Wormhole\Protocols\LcV3\Protocol\Server;
use Wormhole\Protocols\LcV3\Protocol\Frame;



class EmptyTurnover extends Frame
{


    protected $instructions = 0x1303;



}