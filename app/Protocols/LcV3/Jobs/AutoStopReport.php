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
class AutoStopReport implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var string 桩编号
     */
    protected $code;

    /**
     * @var string 订单号
     */
    protected $order_number;

    /**
     * @var string 启动类型
     */
    protected $startup_type;

    /**
     * @var string 剩余时间
     */
    protected $left_time;

    /**
     * @var string 停止原因
     */
    protected $stop_reason;


    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($code, $order_number, $startup_type, $left_time, $stop_reason)
    {

        $this->code = $code;
        $this->order_number = $order_number;
        $this->left_time = $left_time;
        $this->stop_reason = $stop_reason;
        $this->startup_type = $startup_type;


    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $evseController = new EvseController();
        $evseController->autoStopReport($this->code, $this->order_number, $this->startup_type, $this->left_time, $this->stop_reason);

    }



}