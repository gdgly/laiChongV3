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
class StopChargeSend implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var string monitor订单号
     */
    protected $monitorOrderId;

    


    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($monitorOrderId)
    {

        $this->monitorOrderId = $monitorOrderId;

    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $evseController = new EvseController();
        $evseController->stopChargeSend($this->monitorOrderId);

    }



}