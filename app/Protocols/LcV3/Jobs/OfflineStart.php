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
class OfflineStart implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var string 桩编号
     */
    protected $code;

    /**
     * @var string 单号
     */
    protected $odd;

    /**
     * @var string 通道号
     */
    protected $channel_number;

    /**
     * @var string 启动类型
     */
    protected $start_type;

    /**
     * @var string 金额
     */
    protected $balance;


    /**
     * @var string 充电时长
     */
    protected $duration;

    /**
     * @var string 卡号:刷卡支付有效
     */
    protected $card_num;

    /**
     * @var string $client_id
     */
    protected $client_id;





    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($code, $odd, $channel_number, $start_type, $balance, $duration, $card_num, $client_id)
    {

        $this->code = $code;
        $this->odd = $odd;
        $this->channel_number = $channel_number;
        $this->start_type = $start_type;
        $this->balance = $balance;
        $this->duration = $duration;
        $this->card_num = $card_num;
        $this->client_id = $client_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $evseController = new EvseController();
        $evseController->offlineStart($this->code, $this->odd, $this->channel_number, $this->start_type, $this->balance, $this->duration, $this->card_num, $this->client_id);
    }



}