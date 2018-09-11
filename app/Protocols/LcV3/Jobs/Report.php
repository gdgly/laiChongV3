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
class Report implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var string 桩编号
     */
    protected $code;

    /**
     * @var string 投币数量
     */
    protected $coins_number;

    /**
     * @var string 当天投币数量
     */
    protected $same_day_coins_number;

    /**
     * @var string 刷卡金额
     */
    protected $card_amount;

    /**
     * @var string 一天电表数据
     */
    protected $electricity_num;

    /**
     * @var string 总电量
     */
    protected $total_electricity;


    /**
     * @var string 日期
     */
    protected $date;







    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($code, $coins_number, $sameDayCoinsNumber, $card_amount, $electricity_num, $total_electricity, $date)
    {

        $this->code = $code;
        $this->coins_number = $coins_number;
        $this->card_amount = $card_amount;
        $this->same_day_coins_number = $sameDayCoinsNumber;
        $this->electricity_num = $electricity_num;
        $this->total_electricity = $total_electricity;
        $this->date = $date;


    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $evseController = new EvseController();
        $evseController->Report($this->code, $this->coins_number, $this->same_day_coins_number, $this->card_amount, $this->electricity_num, $this->total_electricity, $this->date);

    }



}