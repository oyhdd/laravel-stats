<?php

namespace Oyhdd\StatsCenter\Console;

use Illuminate\Console\Command;
use Oyhdd\StatsCenter\Models\Api;
use Oyhdd\StatsCenter\Models\Module;
use Oyhdd\StatsCenter\Models\StatsSum as StatsSumModel;
use Oyhdd\StatsCenter\Models\Stats;
use Oyhdd\StatsCenter\Services\AlarmService;

/**
 * 模调系统数据统计服务
 *
 * php artisan stats-sum --date=2019-09-17
 */
class StatsSum extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stats:sum {--date=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    protected $alarmService;

    protected $moduleInfo;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(AlarmService $alarmService)
    {
        $this->alarmService = $alarmService;
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $date = $this->option('date');
        if (empty($date)) {
            $date = date('Y-m-d');
        } else {
            $date = date('Y-m-d', strtotime($date));
        }
        $this->sum($date);
    }

    public function sum($date)
    {
        //获取所有接口
        $interfaceInfo = Api::orderBy('id', 'desc')->get()->toArray();
        $this->moduleInfo = Module::pluck('name', 'id')->toArray();

        echo "update interface, interface_num=".count($interfaceInfo)." start\n";
        foreach ($interfaceInfo as $key => $ifce) {
            $res = $this->sumInterfaceData($date, $ifce, $this->moduleInfo);
            if (!empty($res)) {
                echo "update interface [", $ifce['id'], " : ", $ifce['name'], "] changed\n";
                if ($key % 20 == 0) {
                    sleep(1);
                }
            }
        }
    }

    /**
     * 汇总接口的数据
     * @param $interface_id
     * @param $name
     * @param $module_info
     * @return bool|model
     * @throws Exception
     */
    public function sumInterfaceData($date, $ifce, $module_info)
    {
        $interface_id = $ifce['id'];
        $name = $ifce['name'];
        $module_id = $ifce['module_id'];

        $res = Stats::where([
                'date_key' => $date,
                'interface_id' => $interface_id,
                'module_id' => $module_id,
            ])->orderBy('time_key', 'asc')->get()->toArray();

        $time_interval = config('statscenter.time_key_min', 5);
        $time_key = intval((date('G')*60 + date('i')) / $time_interval) -2;
        if ($time_key < 0) {
            $time_key = 0;
        }
        $total_count_yesterday = Stats::where([
                'date_key'     => date("Y-m-d", strtotime('-1 day')),
                'time_key'     => $time_key,
                'interface_id' => $interface_id,
                'module_id'    => $module_id,
            ])
            ->value('total_count');
        $total_count_now = 0;
        if (!empty($res)) {
            $caculate = [];
            foreach ($res as $v) {
                if ($v['time_key'] == $time_key) {
                    $total_count_now = $v['total_count'];
                }
                //总数
                if (!isset($caculate['total_count'])) {
                    $caculate['total_count'] = $v['total_count'];
                } else {
                    $caculate['total_count'] += $v['total_count'];
                }
                //失败汇总
                if (!isset($caculate['fail_count'])) {
                    $caculate['fail_count'] = $v['fail_count'];
                } else {
                    $caculate['fail_count'] += $v['fail_count'];
                }
                //总时间汇总
                if (!isset($caculate['total_time'])) {
                    $caculate['total_time'] = $v['total_time'];
                } else {
                    $caculate['total_time'] += $v['total_time'];
                }
                //总失败时间汇总 total_fail_time
                if (!isset($caculate['total_fail_time'])) {
                    $caculate['total_fail_time'] = $v['total_fail_time'];
                } else {
                    $caculate['total_fail_time'] += $v['total_fail_time'];
                }

                //获取最大时间
                if (!isset($caculate['max_time'])) {
                    $caculate['max_time'] = $v['max_time'];
                } elseif ($caculate['max_time'] < $v['max_time']) {
                    $caculate['max_time'] = $v['max_time'];
                }
                //获取最小时间
                if (!isset($caculate['min_time'])) {
                    $caculate['min_time'] = $v['min_time'];
                } elseif ($caculate['min_time'] > $v['min_time']) {
                    $caculate['min_time'] = $v['min_time'];
                }
            }

            //平均响应时间
            if ($caculate['total_count'] != 0) {
                $caculate['avg_time'] = round($caculate['total_time'] / $caculate['total_count'], 2);
                $caculate['succ_rate'] = floor((($caculate['total_count'] - $caculate['fail_count']) / $caculate['total_count']) * 10000) / 100;
            } else {
                $caculate['avg_time'] = 0;
                $caculate['succ_rate'] = 0;
            }
            //平均失败响应时间
            if ($caculate['fail_count'] != 0) {
                $caculate['avg_fail_time'] = round($caculate['total_fail_time'] / $caculate['fail_count'],2);
            } else {
                $caculate['avg_fail_time'] = 0;
            }
            $caculate['succ_count'] = $caculate['total_count'] - $caculate['fail_count'];
            $caculate['interface_name'] = $name;
            $module_name = isset($module_info[$module_id]) ? $module_info[$module_id] : '';
            $caculate['module_name'] = $module_name;

            $attributes = [
                'date_key'     => $date,
                'interface_id' => $interface_id,
                'module_id'    => $module_id,
            ];
            $this->alarmService->alarm($ifce, array_merge($caculate, compact('total_count_yesterday','total_count_now')));
            return StatsSumModel::updateOrCreate($attributes, $caculate);
        } else {
            return false;
        }
    }
}
