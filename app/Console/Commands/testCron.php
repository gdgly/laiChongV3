<?php

namespace Wormhole\Console\Commands;

use Illuminate\Console\Command;

use Wormhole\Protocols\Library\Log as Logger;

use Wormhole\Protocols\QianNiu\Controllers\Api\EvseController;

class testCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'testLog';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Log Info';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //Logger::log('11223344', __CLASS__ . "/" . __FUNCTION__ . "@" . __LINE__ . " test12345");
        EvseController::testaaa();
    }
}
