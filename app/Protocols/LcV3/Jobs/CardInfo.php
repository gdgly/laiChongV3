<?php
/**
 * Created by PhpStorm.
 * User: lingf
 * Date: 2017-03-10
 * Time: 14:36
 */

namespace Wormhole\Protocols\LcV3\Jobs;


use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use Wormhole\Protocols\LcV3\Controllers\EvseController;
class CardInfo implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var string 桩编号
     */
    protected $code;

    /**
     * @var string 卡片id
     */
    protected $card_num;

    /**
     * @var string 卡片类型
     */
    protected $card_type;

    /**
     * @var string 保留
     */
    protected $retain;

    /**
     * @var string 剩余金额/时间
     */
    protected $balance;


    /**
     * @var string 用户密码
     */
    protected $user_password;


    /**
     * @var string 开卡时间
     */
    protected $start_card_date;


    /**
     * @var string 有效月数
     */
    protected $effective_month;

    /**
     * @var string crc16校验
     */
    protected $crc16;




    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($code, $card_num, $card_type, $retain, $balance, $user_password, $start_card_date, $effective_month, $crc16)
    {

        $this->code = $code;
        $this->card_num = $card_num;
        $this->card_type = $card_type;
        $this->balance = $balance;
        $this->user_password = $user_password;
        $this->start_card_date = $start_card_date;
        $this->effective_month = $effective_month;


    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $evseController = new EvseController();
        $evseController->cardInfo($this->code, $this->card_num, $this->card_type, $this->balance, $this->user_password, $this->start_card_date, $this->effective_month);

    }



}