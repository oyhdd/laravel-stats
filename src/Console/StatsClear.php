<?php

namespace Oyhdd\StatsCenter\Console;

use Illuminate\Console\Command;
use Oyhdd\StatsCenter\Models\Stats;
use Oyhdd\StatsCenter\Models\StatsClient;
use Oyhdd\StatsCenter\Models\StatsServer;
use Oyhdd\StatsCenter\Models\StatsSum;

/**
 * 模调系统数据清理
 *
 * php artisan stats:clear
 */
class StatsClear extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stats:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    protected $moduleInfo;

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
        $save_day = config('statscenter.save_day', 90);
        $date_key = date("Y-m-d", strtotime("-{$save_day} day"));

        Stats::where('date_key', '<', $date_key)->delete();
        StatsClient::where('date_key', '<', $date_key)->delete();
        StatsServer::where('date_key', '<', $date_key)->delete();
        StatsSum::where('date_key', '<', $date_key)->delete();
    }
}
