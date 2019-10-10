<?php

namespace Oyhdd\StatsCenter\Console;

use Log;
use Illuminate\Console\Command;
use Oyhdd\StatsCenter\Models\Api;
use Oyhdd\StatsCenter\Services\StatsService;

/**
 * 模调系统上报服务
 *
 * php artisan stats:server
 */
class StatsServer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stats:server';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * swoole config
     */
    public $setting      = [];
    public $insert_mysql = [];

    public $count      = [];
    public $task_count = [];

    protected $serv;
    protected $worker_id;
    protected $pid_file;

    public $recyle_time = 60000; // 60秒回收一次内存
    public $insert_time = 50000; // 50秒执行一次Timer
    public $time_interval;

    const PROCESS_NAME   = "stats_server";
    const CONSOLE_LENGTH = 14;
    const STATS_PKG_LEN  = 25;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->time_interval = config('statscenter.time_key_min', 5);
        $this->statsService = new StatsService();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $udp_setting = [
            'worker_num'      => 24,
            'task_worker_num' => 24,
            'max_request'     => 0,
            'dispatch_mode'   => 1,
        ];
        $this->setting  = array_merge($udp_setting, config('statscenter.swoole_setting.udp'));
        $this->pid_file = $this->setting['pid_file'] ?? storage_path('logs/server.pid');

        $udp_port = config('statscenter.stats_port.udp', 9903);
        $tcp_port = config('statscenter.stats_port.tcp', 9904);

        $serv = new \swoole_server('0.0.0.0', $udp_port, SWOOLE_PROCESS, SWOOLE_UDP);  //构建Server对象
        $tcp_serv = $serv->listen('0.0.0.0', $tcp_port, SWOOLE_SOCK_TCP); //处理统计页面请求的数据
        $serv->set($this->setting);
        $tcp_serv->set(config('statscenter.swoole_setting.tcp', []));
        $serv->on('Start', array($this, 'onStart'));
        $serv->on('ManagerStart', array($this, 'onManagerStart'));
        $serv->on('ManagerStop', array($this, 'onManagerStop'));
        $serv->on('WorkerStart', array($this, 'onWorkerStart'));
        $serv->on('WorkerError', array($this, 'onWorkerError'));
        $serv->on('WorkerStop', array($this, 'onWorkerStop'));
        $serv->on('Task', array($this, 'onTask'));
        $serv->on('Connect', array($this, 'onConnect'));
        $tcp_serv->on('Receive', array($this, 'onReceive'));
        $serv->on('Packet', array($this, 'onPacket'));
        $serv->on('Finish', array($this, 'onFinish'));
        $serv->on('Close', array($this, 'onClose'));
        $serv->on('Shutdown', array($this, 'onShutdown'));
        $serv->on('WorkerExit', array($this, 'onWorkerExit'));
        $this->serv = $serv;
        $serv->start();
    }

    /**
     * onStart 回调函数
     *
     * @param \swoole_server $serv
     */
    public function onStart(\swoole_server $serv)
    {
        //设置主进程名称
        swoole_set_process_name(self::PROCESS_NAME.": master");

        file_put_contents($this->pid_file, $serv->master_pid);

        echo "\033[1A\n\033[K-----------------------\033[47;30m SWOOLE \033[0m-----------------------------\n\033[0m";
        echo 'swoole version:' . swoole_version() . "          PHP version:" . PHP_VERSION . "\n";
        echo "------------------------\033[47;30m WORKERS \033[0m---------------------------\n";
        echo "\033[47;30mMasterPid\033[0m", str_pad('',
            self::CONSOLE_LENGTH - strlen('MasterPid')), "\033[47;30mManagerPid\033[0m", str_pad('',
            self::CONSOLE_LENGTH - strlen('ManagerPid')), "\033[47;30mWorkerId\033[0m", str_pad('',
            self::CONSOLE_LENGTH - strlen('WorkerId')), "\033[47;30mWorkerPid\033[0m\n";
    }

    /**
     * 管理进程启动时调用
     *
     * @param \swoole_server $serv
     */
    public function onManagerStart($server)
    {
        swoole_set_process_name(self::PROCESS_NAME . ": manager");
    }

    /**
     * 管理进程结束时调用
     *
     * @param \swoole_server $serv
     */
    public function onManagerStop($server)
    {
        if (file_exists($this->pid_file)) {
            unlink($this->pid_file);
        }
    }

    /**
     * 进程启动
     *
     * @param swoole_server  $serv
     * @param int            $worker_id
     */
    public function onWorkerStart(\swoole_server $serv, $worker_id)
    {
        $this->worker_id = $worker_id;
        if ($this->worker_id < $this->setting['worker_num']) {
            swoole_set_process_name(self::PROCESS_NAME.": worker #$worker_id");
            $serv->tick($this->recyle_time, array($this, 'onTimer'));
            if (isset($this->setting['worker_dump_file'])) {
                $dump_file = $this->setting['worker_dump_file']."_".$worker_id;
                if (file_exists($dump_file)) {
                    $this->count = unserialize(file_get_contents($dump_file));
                    // Log::info("load worker {$worker_id} data from last :".print_r($this->count,1));
                    unlink($dump_file);
                }
            }

            usleep($worker_id * 50000);//保证顺序输出格式
            echo str_pad($serv->master_pid, self::CONSOLE_LENGTH), str_pad($serv->manager_pid,
            self::CONSOLE_LENGTH), str_pad($serv->worker_id, self::CONSOLE_LENGTH), str_pad($serv->worker_pid,
            12), "\n";
        } else {
            swoole_set_process_name(self::PROCESS_NAME.": task #$worker_id");
            $serv->tick($this->insert_time, array($this, 'onTimer'));
            if (isset($this->setting['task_dump_file'])) {
                $dump_file = $this->setting['task_dump_file']."_".$worker_id;
                if (file_exists($dump_file)) {
                    $this->task_count = unserialize(file_get_contents($dump_file));
                    // Log::info("load task {$worker_id} data from last :".print_r($this->task_count,1));
                    unlink($dump_file);
                }
            }
        }
    }

    /**
     * worker出现问题调用
     *
     * @param \swoole\server $serv
     * @param int            $worker_id
     * @param int            $worker_pid
     * @param int            $exit_code
     */
    public function onWorkerError(\swoole_server $serv, $worker_id, $worker_pid, $exit_code)
    {
        Log::info("worker abnormal exit. WorkerId=$worker_id|Pid=$worker_pid|ExitCode=$exit_code");
    }

    /**
     * worker退出时调用
     *
     * @param int            $worker_id
     * @param  \swoole\server      $serv
     */
    public function onWorkerExit(\swoole_server $serv, $worker_id)
    {
        Log::info("onWorkerExit [$worker_id]|pid=" . $serv->worker_pid);
        \Swoole\Timer::clearAll();
    }

    /**
     * 关闭进程
     *
     * @param \swoole\server $serv
     * @param int            $worker_id
     */
    public function onWorkerStop($serv, $worker_id)
    {
        Log::info("WorkerStop [$worker_id]|pid=" . $serv->worker_pid);
    }

    /**
     * task任务
     *
     * @param \swoole\server $serv
     * @param int            $task_id
     * @param int            $from_id
     * @param int            $data
     *
     * @return mixed
     */
    public function onTask(\swoole_server $serv, $task_id, $from_id, $data)
    {
        $map = json_decode($data, true);
        if ($map) {
            foreach ($map as $k => $v) {
                if ($k == 'all') {
                    $key = $v['key'];
                    if (!isset($this->task_count[$key])) {
                        list(
                            $this->task_count[$key]['all']['module_id'],
                            $this->task_count[$key]['all']['interface_id'],
                            ,
                            $this->task_count[$key]['all']['time_key']
                        ) = explode('_', $key, 4);
                        $this->task_count[$key]['all']['date_key'] = date('Ymd', time() - 300);
                        $this->task_count[$key]['all']['total_count'] = 0;
                        $this->task_count[$key]['all']['fail_count'] = 0;
                        $this->task_count[$key]['all']['total_time'] = 0.0;
                        $this->task_count[$key]['all']['total_fail_time'] = 0;
                        $this->task_count[$key]['all']['key'] = $key;
                        $this->task_count[$key]['all']['max_time'] = $v['max_time'];
                        $this->task_count[$key]['all']['min_time'] = $v['min_time'];
                        $this->task_count[$key]['all']['cost_time'] = $v['cost_time'];
                    } else {
                        if ($v['max_time'] > $this->task_count[$key]['all']['max_time']) {
                            $this->task_count[$key]['all']['max_time'] = $v['max_time'];
                        }
                        if ($v['min_time'] < $this->task_count[$key]['all']['min_time']) {
                            $this->task_count[$key]['all']['min_time'] = $v['min_time'];
                        }
                        /**
                         * 汇总耗时分布数据
                         */
                        self::sumCostTimeDistribution($this->task_count[$key]['all']['cost_time'], $v['cost_time']);
                    }

                    /**
                     * 汇总统计数据
                     */
                    $this->task_count[$key]['all']['total_count'] += $v['total_count'];
                    $this->task_count[$key]['all']['total_time'] += $v['total_time'];
                    $this->task_count[$key]['all']['fail_count'] += $v['fail_count'];
                    $this->task_count[$key]['all']['total_fail_time'] += $v['total_fail_time'];

                    foreach ($v as $s => $vv) {
                        if (strpos($s, 'total_server_') === 0) {
                            if (!isset( $this->task_count[$key]['all']['total_server'][substr($s, 13)])) {
                                $this->task_count[$key]['all']['total_server'][substr($s, 13)] = $vv;
                            } else {
                                $this->task_count[$key]['all']['total_server'][substr($s, 13)] += $vv;
                            }
                        }
                        if (strpos($s, 'total_client_') === 0) {
                            if (!isset( $this->task_count[$key]['all']['total_client'][substr($s, 13)])) {
                                $this->task_count[$key]['all']['total_client'][substr($s, 13)] = $vv;
                            } else {
                                $this->task_count[$key]['all']['total_client'][substr($s, 13)] += $vv;
                            }
                        }
                        if (strpos($s, 'fail_server_') === 0) {
                            if (!isset( $this->task_count[$key]['all']['fail_server'][substr($s, 12)])) {
                                $this->task_count[$key]['all']['fail_server'][substr($s, 12)] = $vv;
                            } else {
                                $this->task_count[$key]['all']['fail_server'][substr($s, 12)] += $vv;
                            }
                        }
                        if (strpos($s, 'fail_client_') === 0) {
                            if (!isset( $this->task_count[$key]['all']['fail_client'][substr($s, 12)])) {
                                $this->task_count[$key]['all']['fail_client'][substr($s, 12)] = $vv;
                            } else {
                                $this->task_count[$key]['all']['fail_client'][substr($s, 12)] += $vv;
                            }
                        }
                        if (strpos($s, 'ret_code_') === 0) {
                            if (!isset( $this->task_count[$key]['all']['ret_code'][substr($s, 9)])) {
                                $this->task_count[$key]['all']['ret_code'][substr($s, 9)] = $vv;
                            } else {
                                $this->task_count[$key]['all']['ret_code'][substr($s, 9)] += $vv;
                            }
                        }
                        if (strpos($s, 'succ_server_') === 0) {
                            if (!isset( $this->task_count[$key]['all']['succ_server'][substr($s, 12)])) {
                                $this->task_count[$key]['all']['succ_server'][substr($s, 12)] = $vv;
                            } else {
                                $this->task_count[$key]['all']['succ_server'][substr($s, 12)] += $vv;
                            }
                        }
                        if (strpos($s, 'succ_client_') === 0) {
                            if (!isset( $this->task_count[$key]['all']['succ_client'][substr($s, 12)])) {
                                $this->task_count[$key]['all']['succ_client'][substr($s, 12)] = $vv;
                            } else {
                                $this->task_count[$key]['all']['succ_client'][substr($s, 12)] += $vv;
                            }
                        }
                        if (strpos($s, 'succ_ret_code_') === 0) {
                            if (!isset( $this->task_count[$key]['all']['succ_ret_code'][substr($s, 14)])) {
                                $this->task_count[$key]['all']['succ_ret_code'][substr($s, 14)] = $vv;
                            } else {
                                $this->task_count[$key]['all']['succ_ret_code'][substr($s, 14)] += $vv;
                            }
                        }
                    }
                }

                if ($k == 'server') {
                    foreach ($v as $server_ip => $server) {
                        if (!isset($this->task_count[$key]['server'][$server_ip])) {
                            $this->task_count[$key]['server'][$server_ip]['total_count'] = $server['total_count'];
                            $this->task_count[$key]['server'][$server_ip]['total_time'] = $server['total_time'];
                            $this->task_count[$key]['server'][$server_ip]['fail_count'] = $server['fail_count'];
                            $this->task_count[$key]['server'][$server_ip]['total_fail_time'] = $server['total_fail_time'];
                            $this->task_count[$key]['server'][$server_ip]['key'] = $key;

                            $this->task_count[$key]['server'][$server_ip]['max_time'] = $server['max_time'];
                            $this->task_count[$key]['server'][$server_ip]['min_time'] = $server['min_time'];
                        } else {
                            $this->task_count[$key]['server'][$server_ip]['total_count'] += $server['total_count'];
                            $this->task_count[$key]['server'][$server_ip]['total_time'] += $server['total_time'];
                            $this->task_count[$key]['server'][$server_ip]['fail_count'] += $server['fail_count'];
                            $this->task_count[$key]['server'][$server_ip]['total_fail_time'] += $server['total_fail_time'];

                            if ($server['max_time'] > $this->task_count[$key]['server'][$server_ip]['max_time']) {
                                $this->task_count[$key]['server'][$server_ip]['max_time'] = $server['max_time'];
                            }
                            if ($server['min_time'] < $this->task_count[$key]['server'][$server_ip]['min_time']) {
                                $this->task_count[$key]['server'][$server_ip]['min_time'] = $server['min_time'];
                            }
                        }
                        foreach ($server as $s => $vv) {
                            if (strpos($s, 'total_client_') === 0) {
                                if (!isset( $this->task_count[$key]['server'][$server_ip]['total_client'][substr($s, 13)])) {
                                    $this->task_count[$key]['server'][$server_ip]['total_client'][substr($s, 13)] = $vv;
                                } else {
                                    $this->task_count[$key]['server'][$server_ip]['total_client'][substr($s, 13)] += $vv;
                                }
                            }
                            if (strpos($s, 'fail_client_') === 0) {
                                if (!isset( $this->task_count[$key]['server'][$server_ip]['fail_client'][substr($s, 12)])) {
                                    $this->task_count[$key]['server'][$server_ip]['fail_client'][substr($s, 12)] = $vv;
                                } else {
                                    $this->task_count[$key]['server'][$server_ip]['fail_client'][substr($s, 12)] += $vv;
                                }
                            }
                            if (strpos($s, 'ret_code_') === 0) {
                                if (!isset( $this->task_count[$key]['server'][$server_ip]['ret_code'][substr($s, 9)])) {
                                    $this->task_count[$key]['server'][$server_ip]['ret_code'][substr($s, 9)] = $vv;
                                } else {
                                    $this->task_count[$key]['server'][$server_ip]['ret_code'][substr($s, 9)] += $vv;
                                }
                            }
                            if (strpos($s, 'succ_client_') === 0) {
                                if (!isset( $this->task_count[$key]['server'][$server_ip]['succ_client'][substr($s, 12)])) {
                                    $this->task_count[$key]['server'][$server_ip]['succ_client'][substr($s, 12)] = $vv;
                                } else {
                                    $this->task_count[$key]['server'][$server_ip]['succ_client'][substr($s, 12)] += $vv;
                                }
                            }
                            if (strpos($s, 'succ_ret_code_') === 0) {
                                if (!isset( $this->task_count[$key]['server'][$server_ip]['succ_ret_code'][substr($s, 14)])) {
                                    $this->task_count[$key]['server'][$server_ip]['succ_ret_code'][substr($s, 14)] = $vv;
                                } else {
                                    $this->task_count[$key]['server'][$server_ip]['succ_ret_code'][substr($s, 14)] += $vv;
                                }
                            }
                        }

                    }
                }

                if ($k == 'client') {
                    foreach ($v as $client_ip => $client) {
                        if (!isset($this->task_count[$key]['client'][$client_ip])) {
                            $this->task_count[$key]['client'][$client_ip]['total_count'] = $client['total_count'];
                            $this->task_count[$key]['client'][$client_ip]['total_time'] = $client['total_time'];
                            $this->task_count[$key]['client'][$client_ip]['fail_count'] = $client['fail_count'];
                            $this->task_count[$key]['client'][$client_ip]['total_fail_time'] = $client['total_fail_time'];
                            $this->task_count[$key]['client'][$client_ip]['key'] = $key;

                            $this->task_count[$key]['client'][$client_ip]['max_time'] = $client['max_time'];
                            $this->task_count[$key]['client'][$client_ip]['min_time'] = $client['min_time'];
                        } else {
                            $this->task_count[$key]['client'][$client_ip]['total_count'] += $client['total_count'];
                            $this->task_count[$key]['client'][$client_ip]['total_time'] += $client['total_time'];
                            $this->task_count[$key]['client'][$client_ip]['fail_count'] += $client['fail_count'];
                            $this->task_count[$key]['client'][$client_ip]['total_fail_time'] += $client['total_fail_time'];

                            if ($client['max_time'] > $this->task_count[$key]['client'][$client_ip]['max_time']) {
                                $this->task_count[$key]['client'][$client_ip]['max_time'] = $client['max_time'];
                            }
                            if ($client['min_time'] < $this->task_count[$key]['client'][$client_ip]['min_time']) {
                                $this->task_count[$key]['client'][$client_ip]['min_time'] = $client['min_time'];
                            }
                        }
                        foreach ($client as $s => $vv) {
                            if (strpos($s, 'total_server_') === 0) {
                                if (!isset( $this->task_count[$key]['client'][$client_ip]['total_server'][substr($s, 13)])) {
                                    $this->task_count[$key]['client'][$client_ip]['total_server'][substr($s, 13)] = $vv;
                                } else {
                                    $this->task_count[$key]['client'][$client_ip]['total_server'][substr($s, 13)] += $vv;
                                }
                            }
                            if (strpos($s, 'fail_server_') === 0) {
                                if (!isset( $this->task_count[$key]['client'][$client_ip]['fail_server'][substr($s, 12)])) {
                                    $this->task_count[$key]['client'][$client_ip]['fail_server'][substr($s, 12)] = $vv;
                                } else {
                                    $this->task_count[$key]['client'][$client_ip]['fail_server'][substr($s, 12)] += $vv;
                                }
                            }
                            if (strpos($s, 'ret_code_') === 0) {
                                if (!isset( $this->task_count[$key]['client'][$client_ip]['ret_code'][substr($s, 9)])) {
                                    $this->task_count[$key]['client'][$client_ip]['ret_code'][substr($s, 9)] = $vv;
                                } else {
                                    $this->task_count[$key]['client'][$client_ip]['ret_code'][substr($s, 9)] += $vv;
                                }
                            }
                            if (strpos($s, 'succ_server_') === 0) {
                                if (!isset( $this->task_count[$key]['client'][$client_ip]['succ_server'][substr($s, 12)])) {
                                    $this->task_count[$key]['client'][$client_ip]['succ_server'][substr($s, 12)] = $vv;
                                } else {
                                    $this->task_count[$key]['client'][$client_ip]['succ_server'][substr($s, 12)] += $vv;
                                }
                            }
                            if (strpos($s, 'succ_ret_code_') === 0) {
                                if (!isset( $this->task_count[$key]['client'][$client_ip]['succ_ret_code'][substr($s, 14)])) {
                                    $this->task_count[$key]['client'][$client_ip]['succ_ret_code'][substr($s, 14)] = $vv;
                                } else {
                                    $this->task_count[$key]['client'][$client_ip]['succ_ret_code'][substr($s, 14)] += $vv;
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * 建立链接
     *
     * @param \swoole\server $serv
     * @param int            $fd
     * @param int            $from_id
     */
    public function onConnect(\swoole_server $serv, $fd, $from_id)
    {
        Log::info("Worker#{$serv->worker_pid} Client[$fd@$from_id]: Connect");
    }

    /**
     * 接收数据
     *
     * @param \swoole\server $serv
     * @param int            $fd
     * @param int            $from_id
     * @param string         $data
     *
     * @return mixed
     */
    public function onReceive(\swoole_server $serv, $fd, $reactor_id, $data)
    {
        $_key = explode(' ', trim($data));
        if (count($_key) != 3 and count($_key) != 2) {
            $this->serv->send($fd, "0");
            return;
        }
        $cmd = trim($_key[0]);

        //模调系统，自动创建接口
        if ($cmd == 'GET') {
            //模块id 或者接口名称为空
            if (empty($_key[1]) || empty($_key[2])) {
                $this->serv->send($fd, "0");
            } else {
                $key = Api::getInterfaceId($_key[1], $_key[2]);
                if (empty($key)) {
                    $this->serv->send($fd, "0");
                } else {
                    $this->serv->send($fd, strval($key));
                }
            }
        } else {
            $this->serv->send($fd, "0");
        }
    }

    /**
     * 接收数据 UDP
     *
     * @param \swoole\server $serv
     * @param string         $data
     * @param array          $clientInfo
     *
     * @return mixed
     */
    public function onPacket(\swoole_server $serv, $data, $clientInfo)
    {
        $n = strlen($data) / self::STATS_PKG_LEN;
        if (is_float($n)) {
            Log::info("error udp pacakge size[".strlen($data)."]. data={$data}");
            return;
        }

        for ($i = 0; $i < $n; $i++) {
            $pkg_data = substr($data, $i * self::STATS_PKG_LEN, self::STATS_PKG_LEN);

            $pkg = unpack('Ninterface_id/Nmodule_id/Csuccess/Nret_code/Nserver_ip/Nuse_ms/Naddtime', $pkg_data);
            if (!$pkg) {
                Log::info("error package. data".$pkg_data);
                continue;
            }
            //耗时> 10s 标记时间为10s
            if ($pkg['use_ms'] > 10000) {
                $pkg['use_ms'] = 10000;
            }
            $server_ip = long2ip($pkg['server_ip']);
            $client_ip = $clientInfo['address'];
            $this->setWorkerCount($client_ip, $server_ip, $pkg);
        }
    }

    //设置计数 生产者
    public function setWorkerCount($client_ip, $server_ip, $params)
    {
        $m = $this->getM();
        $key = $params['module_id'] . '_' . $params['interface_id'] . "_" . intval($m / $this->time_interval) . "_" . intval($m / $this->time_interval);

        /**
         * all
         */
        if (!isset($this->count[$key])) {
            $this->count[$key]['all']['total_count'] = 1;
            $this->count[$key]['all']['total_time'] = (float)$params['use_ms'];
            $this->count[$key]['all']['max_time'] = $params['use_ms'];//最大响应时间
            $this->count[$key]['all']['min_time'] = $params['use_ms'];//最小响应时间
            if ($params['success']) {
                $this->count[$key]['all']['fail_count'] = 0;
                $this->count[$key]['all']['total_fail_time'] = 0;
            } else {
                $this->count[$key]['all']['fail_count'] = 1;
                $this->count[$key]['all']['total_fail_time'] = $params['use_ms'];
            }
            $this->count[$key]['all']['cost_time'] = self::initCostTimeDistribution();
            self::getCostTimeDistribution($params['use_ms'], $this->count[$key]['all']['cost_time']);
            $this->count[$key]['all']['key'] = $key;
        } else {
            $this->count[$key]['all']['total_count'] += 1;
            $this->count[$key]['all']['total_time'] += (float)$params['use_ms'];
            if (!$params['success']) {
                $this->count[$key]['all']['fail_count'] += 1;
                $this->count[$key]['all']['total_fail_time'] += (float)$params['use_ms'];
            }
            if ($params['use_ms'] > $this->count[$key]['all']['max_time']) {
                $this->count[$key]['all']['max_time'] = $params['use_ms'];//最大响应时间
            }
            if ($params['use_ms'] < $this->count[$key]['all']['min_time']) {
                $this->count[$key]['all']['min_time'] = $params['use_ms'];//最小响应时间
            }
            self::getCostTimeDistribution($params['use_ms'], $this->count[$key]['all']['cost_time']);
        }

        if (isset($this->count[$key]['all']['total_server_'.$server_ip])) {
            $this->count[$key]['all']['total_server_'.$server_ip] += 1;
        } else {
            $this->count[$key]['all']['total_server_'.$server_ip] = 1;
        }
        if (isset($this->count[$key]['all']['total_client_'.$client_ip])) {
            $this->count[$key]['all']['total_client_'.$client_ip] += 1;
        } else {
            $this->count[$key]['all']['total_client_'.$client_ip] = 1;
        }
        if ($params['success']) {
            if (isset($this->count[$key]['all']['succ_server_'.$server_ip])) {
                $this->count[$key]['all']['succ_server_'.$server_ip] += 1;
            } else {
                $this->count[$key]['all']['succ_server_'.$server_ip] = 1;
            }
            if (isset($this->count[$key]['all']['succ_client_'.$client_ip])) {
                $this->count[$key]['all']['succ_client_'.$client_ip] += 1;
            } else {
                $this->count[$key]['all']['succ_client_'.$client_ip] = 1;
            }

            if (isset($this->count[$key]['all']['succ_ret_code_'.$params['ret_code']])) {
                $this->count[$key]['all']['succ_ret_code_'.$params['ret_code']] += 1;
            } else {
                $this->count[$key]['all']['succ_ret_code_'.$params['ret_code']] = 1;
            }
        } else {
            if (isset($this->count[$key]['all']['fail_server_'.$server_ip])) {
                $this->count[$key]['all']['fail_server_'.$server_ip] += 1;
            } else {
                $this->count[$key]['all']['fail_server_'.$server_ip] = 1;
            }
            if (isset($this->count[$key]['all']['fail_client_'.$client_ip])) {
                $this->count[$key]['all']['fail_client_'.$client_ip] += 1;
            } else {
                $this->count[$key]['all']['fail_client_'.$client_ip] = 1;
            }

            if (isset($this->count[$key]['all']['ret_code_'.$params['ret_code']])) {
                $this->count[$key]['all']['ret_code_'.$params['ret_code']] += 1;
            } else {
                $this->count[$key]['all']['ret_code_'.$params['ret_code']] = 1;
            }
        }

        /**
         * Server 被调
         */
        if (!isset($this->count[$key]['server'][$server_ip])) {
            $this->count[$key]['server'][$server_ip]['total_count'] = 1;
            $this->count[$key]['server'][$server_ip]['total_time'] = (float)$params['use_ms'];
            $this->count[$key]['server'][$server_ip]['max_time'] = $params['use_ms'];//最大响应时间
            $this->count[$key]['server'][$server_ip]['min_time'] = $params['use_ms'];//最小响应时间
            if ($params['success']) {
                $this->count[$key]['server'][$server_ip]['fail_count'] = 0;
                $this->count[$key]['server'][$server_ip]['total_fail_time'] = 0;
            } else {
                $this->count[$key]['server'][$server_ip]['fail_count'] = 1;
                $this->count[$key]['server'][$server_ip]['total_fail_time'] = $params['use_ms'];
            }
            $this->count[$key]['server'][$server_ip]['key'] = $key;
        } else {
            $this->count[$key]['server'][$server_ip]['total_count'] += 1;
            $this->count[$key]['server'][$server_ip]['total_time'] += (float)$params['use_ms'];
            if (!$params['success']) {
                $this->count[$key]['server'][$server_ip]['fail_count'] += 1;
                $this->count[$key]['server'][$server_ip]['total_fail_time'] += $params['use_ms'];
            }

            if ($params['use_ms'] > $this->count[$key]['server'][$server_ip]['max_time']) {
                $this->count[$key]['server'][$server_ip]['max_time'] = $params['use_ms'];//最大响应时间
            }
            if ($params['use_ms'] < $this->count[$key]['server'][$server_ip]['min_time']) {
                $this->count[$key]['server'][$server_ip]['min_time'] = $params['use_ms'];//最小响应时间
            }
        }

        if (isset($this->count[$key]['server'][$server_ip]['total_client_'.$client_ip])) {
            $this->count[$key]['server'][$server_ip]['total_client_'.$client_ip] += 1;
        } else {
            $this->count[$key]['server'][$server_ip]['total_client_'.$client_ip] = 1;
        }
        if ($params['success']) {
            if (isset($this->count[$key]['server'][$server_ip]['succ_client_'.$client_ip])) {
                $this->count[$key]['server'][$server_ip]['succ_client_'.$client_ip] += 1;
            } else {
                $this->count[$key]['server'][$server_ip]['succ_client_'.$client_ip] = 1;
            }

            if (isset($this->count[$key]['server'][$server_ip]['succ_ret_code_'.$params['ret_code']])) {
                $this->count[$key]['server'][$server_ip]['succ_ret_code_'.$params['ret_code']] += 1;
            } else {
                $this->count[$key]['server'][$server_ip]['succ_ret_code_'.$params['ret_code']] = 1;
            }
        } else {
            if (isset($this->count[$key]['server'][$server_ip]['fail_client_'.$client_ip])) {
                $this->count[$key]['server'][$server_ip]['fail_client_'.$client_ip] += 1;
            } else {
                $this->count[$key]['server'][$server_ip]['fail_client_'.$client_ip] = 1;
            }

            if (isset($this->count[$key]['server'][$server_ip]['ret_code_'.$params['ret_code']])) {
                $this->count[$key]['server'][$server_ip]['ret_code_'.$params['ret_code']] += 1;
            } else {
                $this->count[$key]['server'][$server_ip]['ret_code_'.$params['ret_code']] = 1;
            }
        }

        /**
         * Client
         */
        if (!isset($this->count[$key]['client'][$client_ip])) {
            $this->count[$key]['client'][$client_ip]['total_count'] = 1;
            $this->count[$key]['client'][$client_ip]['total_time'] = (float)$params['use_ms'];
            $this->count[$key]['client'][$client_ip]['max_time'] = $params['use_ms'];
            $this->count[$key]['client'][$client_ip]['min_time'] = $params['use_ms'];
            if ($params['success']) {
                $this->count[$key]['client'][$client_ip]['fail_count'] = 0;
                $this->count[$key]['client'][$client_ip]['total_fail_time'] = 0;
            } else {
                $this->count[$key]['client'][$client_ip]['fail_count'] = 1;
                $this->count[$key]['client'][$client_ip]['total_fail_time'] = $params['use_ms'];
            }
            $this->count[$key]['client'][$client_ip]['key'] = $key;
        } else {
            $this->count[$key]['client'][$client_ip]['total_count'] += 1;
            $this->count[$key]['client'][$client_ip]['total_time'] += (float)$params['use_ms'];
            if (!$params['success']) {
                $this->count[$key]['client'][$client_ip]['fail_count'] += 1;
                $this->count[$key]['client'][$client_ip]['total_fail_time'] += $params['use_ms'];
            }

            if ($params['use_ms'] > $this->count[$key]['client'][$client_ip]['max_time']) {
                $this->count[$key]['client'][$client_ip]['max_time'] = $params['use_ms'];//最大响应时间
            }
            if ($params['use_ms'] < $this->count[$key]['client'][$client_ip]['min_time']) {
                $this->count[$key]['client'][$client_ip]['min_time'] = $params['use_ms'];//最小响应时间
            }
        }

        if (isset($this->count[$key]['client'][$client_ip]['total_server_'.$server_ip])) {
            $this->count[$key]['client'][$client_ip]['total_server_'.$server_ip] += 1;
        } else {
            $this->count[$key]['client'][$client_ip]['total_server_'.$server_ip] = 1;
        }
        if ($params['success']) {
            if (isset($this->count[$key]['client'][$client_ip]['succ_server_'.$server_ip])) {
                $this->count[$key]['client'][$client_ip]['succ_server_'.$server_ip] += 1;
            } else {
                $this->count[$key]['client'][$client_ip]['succ_server_'.$server_ip] = 1;
            }

            if (isset($this->count[$key]['client'][$client_ip]['succ_ret_code_'.$params['ret_code']])) {
                $this->count[$key]['client'][$client_ip]['succ_ret_code_'.$params['ret_code']] += 1;
            } else {
                $this->count[$key]['client'][$client_ip]['succ_ret_code_'.$params['ret_code']] = 1;
            }
        } else {
            if (isset($this->count[$key]['client'][$client_ip]['fail_server_'.$server_ip])) {
                $this->count[$key]['client'][$client_ip]['fail_server_'.$server_ip] += 1;
            } else {
                $this->count[$key]['client'][$client_ip]['fail_server_'.$server_ip] = 1;
            }

            if (isset($this->count[$key]['client'][$client_ip]['ret_code_'.$params['ret_code']])) {
                $this->count[$key]['client'][$client_ip]['ret_code_'.$params['ret_code']] += 1;
            } else {
                $this->count[$key]['client'][$client_ip]['ret_code_'.$params['ret_code']] = 1;
            }
        }
    }

    /**
     * task执行完毕调用
     *
     * @param \swoole\server $serv
     * @param int            $task_id
     * @param mixed          $data
     */
    public function onFinish(\swoole_server $serv, $task_id, $data)
    {
        Log::info('onFinish');
        $this->insert_mysql[] = $data;
    }


    /**
     * 链接断开
     *
     * @param \swoole\swoole $serv
     * @param int            $fd
     * @param int            $from_id
     */
    public function onClose($serv, $fd, $from_id)
    {
        Log::info("Worker#{$serv->worker_pid} Client[$fd@$from_id]: fd=$fd is closed");
    }

    /**
     * @name   关闭服务器
     * @uses   服务器关闭程序终止时
     * @author Eric
     * @param  \swoole\server      $serv
     */
    public function onShutdown(\swoole_server $serv)
    {
        Log::info("onShutdown");
    }

    public function onTimer($id)
    {

        $serv = $this->serv;
        $time_key = $this->getMinute();
        /**
         * worker
         *
         * 回收内存
         */
        if (!$serv->taskworker) {
            // Log::info("worker [{$this->worker_id}] onTimer ({$this->recyle_time}) time_key : $time_key -- ",$this->count);
            if (!empty($this->count)) {
                foreach ($this->count as $key => $v) {
                    $m = explode('_', $key, 4);
                    if ($time_key == $m[2]) {
                        continue;
                    }
                    $target_id = $m[1] % $this->setting['task_worker_num'];
                    Log::info("[{$this->worker_id}] task $key to " . ($target_id));
                    $serv->task(json_encode($v), $target_id);
                    unset($this->count[$key]);
                }
            }
        } else {
            /**
             * task worker
             *
             * 写入数据
             */
            // Log::info("task worker [".($this->worker_id)."] onTimer ({$this->insert_time}) time_key : $time_key -- ",$this->task_count);
            $this->taskReport();
        }
    }

    public function taskReport()
    {
        if (!empty($this->task_count)) {
            $return = [];
            $time_key = $this->getMinute();
            foreach ($this->task_count as $key => $data) {
                //task 将本time_key之前的数据进行上报,本time_key的数据进行汇总
                $m = explode('_', $key, 4);
                if ($m[2] < $time_key-1) {
                    $return[$key] = $data;
                    unset($this->task_count[$key]);
                }
            }
            if ($return) {
                $this->statsService->addStatsData($return);
            }
        }
    }

    /**
     * 获取当前time_key
     */
    public function getMinute()
    {
        return intval((date('G')*60 + date('i')) / $this->time_interval);
    }

    /**
     * 获取今天零点到现在有多少分钟
     */
    public function getM()
    {
        return date('G')*60 + date('i');
    }

    public static function initCostTimeDistribution()
    {
        return [
            '5'    => 0, //少于5ms
            '10'   => 0, //少于10ms
            '50'   => 0, //少于50ms
            '100'  => 0, //少于100ms
            '500'  => 0, //少于500ms
            '500+' => 0, //大于500ms
        ];
    }

    /**
     * 计算耗时分布，统计标准线 5ms 10ms 50ms 100ms 500ms 500ms+
     * @param $costTime
     * @param $array
     */
    public static function getCostTimeDistribution($costTime, &$array)
    {
        if ($costTime < 5) {
            $array['5']++;
        } elseif ($costTime < 10) {
            $array['10']++;
        } elseif ($costTime < 50) {
            $array['50']++;
        } elseif ($costTime < 100) {
            $array['100']++;
        } elseif ($costTime < 500) {
            $array['500']++;
        } else {
            $array['500+']++;
        }
    }

    public static function sumCostTimeDistribution(&$result, $data)
    {
        $result['5'] += $data['5'];
        $result['10'] += $data['10'];
        $result['50'] += $data['50'];
        $result['100'] += $data['100'];
        $result['500'] += $data['500'];
        $result['500+'] += $data['500+'];
    }
}
