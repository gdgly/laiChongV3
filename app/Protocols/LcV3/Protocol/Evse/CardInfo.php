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


class CardInfo extends Frame
{


    protected $instructions = 0x1107;


    /**
     * 卡号
     * @var int
     */
    protected $card_num = [BIN::class,4,FALSE];

    /**
     * 卡类型
     * @var int
     */
    protected $card_type = [BIN::class,1,FALSE];


    /**
     * 保留
     * @var int
     */
    protected $retain = [BIN::class,1,FALSE];

    /**
     * 剩余金额/时间
     * @var int
     */
    protected $balance = [BIN::class,4,FALSE];

    /**
     * 用户密码
     * @var int
     */
    protected $user_password = [BIN::class,4,FALSE];

    /**
     * 开卡时间
     * @var int
     */
    protected $start_card_date = [BCD::class,3,FALSE];


    /**
     * 有效月数
     * @var int
     */
    protected $effective_month = [BIN::class,1,FALSE];

    /**
     * crc16校验
     * @var int
     */
    protected $crc16 = [BIN::class,2,FALSE];



}