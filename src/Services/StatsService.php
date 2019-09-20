<?php
namespace Oyhdd\StatsCenter\Services;

use Log;
use Oyhdd\StatsCenter\Models\Stats;
use Oyhdd\StatsCenter\Models\StatsServer;
use Oyhdd\StatsCenter\Models\StatsClient;

class StatsService
{
    const T_ALL = 1;
    const T_SERVER = 2;
    const T_CLIENT = 3;

    public function addStatsData($data)
    {
        Log::info("写入数据",$data);
        $today = date('Ymd');
        $table_server = 'stats_server_'.$today;
        $table_client = 'stats_client_'.$today;
        $table_total = 'stats_'.$today;

        foreach ($data as $key => $content) {
            //server
            foreach ($content['server'] as $server_ip => $c) {
                $server_count = $this->getCount($content, self::T_SERVER, $server_ip);
                $serverModel = new StatsServer();
                $serverModel->fill($server_count);
                $serverModel->ip            = $server_ip;
                $serverModel->total_client  = self::tryEncode($server_count, 'total_client');
                $serverModel->succ_client   = self::tryEncode($server_count, 'succ_client');
                $serverModel->fail_client   = self::tryEncode($server_count, 'fail_client');
                $serverModel->ret_code      = self::tryEncode($server_count, 'ret_code');
                $serverModel->succ_ret_code = self::tryEncode($server_count, 'succ_ret_code');
                $serverModel->save();
            }

            //client
            foreach ($content['client'] as $client_ip => $c) {
                $client_count = $this->getCount($content, self::T_CLIENT, $client_ip);
                $clientModel = new StatsClient();
                $clientModel->fill($client_count);
                $clientModel->ip            =  $client_ip;
                $clientModel->total_server  = self::tryEncode($client_count, 'total_server');
                $clientModel->succ_server   = self::tryEncode($client_count, 'succ_server');
                $clientModel->fail_server   = self::tryEncode($client_count, 'fail_server');
                $clientModel->ret_code      = self::tryEncode($client_count, 'ret_code');
                $clientModel->succ_ret_code = self::tryEncode($client_count, 'succ_ret_code');
                $clientModel->save();
            }

            //all
            $count = $this->getCount($content, self::T_ALL);
            $statsModel = new Stats();
            $statsModel->fill($count);
            $statsModel->total_server = self::tryEncode($count, 'total_server');
            $statsModel->succ_server = self::tryEncode($count, 'succ_server');
            $statsModel->fail_server = self::tryEncode($count, 'fail_server');
            $statsModel->total_client = self::tryEncode($count, 'total_client');
            $statsModel->succ_client = self::tryEncode($count, 'succ_client');
            $statsModel->fail_client = self::tryEncode($count, 'fail_client');
            $statsModel->ret_code = self::tryEncode($count, 'ret_code');
            $statsModel->cost_time = self::tryEncode($count, 'cost_time');
            $statsModel->succ_ret_code = self::tryEncode($count, 'succ_ret_code');
            $statsModel->save();
        }
    }

    /**
     * 计算计数
     * @param $data
     * @param int $type
     * @param null $ip
     * @return array
     */
    public function getCount($data, $type = self::T_ALL, $ip = null)
    {
        $count = [];
        $count['module_id'] =  $data['all']['module_id'];
        $count['interface_id'] =  $data['all']['interface_id'];
        $count['time_key'] =  $data['all']['time_key'];
        $count['date_key'] =  $data['all']['date_key'];
        $count['total_count'] =  $data['all']['total_count'];
        $count['fail_count'] =  $data['all']['fail_count'];
        $count['total_time'] =  $data['all']['total_time'];
        $count['total_fail_time'] = $data['all']['total_fail_time'];

        if ($type == self::T_ALL) {
            $count['total_count'] = $data['all']['total_count'];
            $count['fail_count'] = $data['all']['fail_count'];
            $count['total_time'] = $data['all']['total_time'];
            $count['max_time'] = $data['all']['max_time'];
            $count['min_time'] = $data['all']['min_time'];
            $count['total_fail_time'] = $data['all']['total_fail_time'];

            $count['total_server'] = $data['all']['total_server'];
            $count['succ_server'] = isset($data['all']['succ_server']) ? $data['all']['succ_server'] : [];
            $count['fail_server'] = isset($data['all']['fail_server']) ? $data['all']['fail_server'] : [];
            $count['total_client'] = $data['all']['total_client'];
            $count['succ_client'] = isset($data['all']['succ_client']) ? $data['all']['succ_client'] : [];
            $count['fail_client'] = isset($data['all']['fail_client']) ? $data['all']['fail_client'] : [];
            $count['ret_code'] = isset($data['all']['ret_code']) ? $data['all']['ret_code'] : [];
            $count['cost_time'] = isset($data['all']['cost_time']) ? $data['all']['cost_time'] : [];
            $count['succ_ret_code'] = isset($data['all']['succ_ret_code']) ? $data['all']['succ_ret_code'] : [];
        }
        if ($type == self::T_SERVER and !empty($ip)) {
            $count['total_count'] =  $data['server'][$ip]['total_count'];
            $count['fail_count'] =  $data['server'][$ip]['fail_count'];
            $count['total_time'] =  $data['server'][$ip]['total_time'];
            $count['max_time'] =  $data['server'][$ip]['max_time'];
            $count['min_time'] =  $data['server'][$ip]['min_time'];
            $count['total_fail_time'] =  $data['server'][$ip]['total_fail_time'];

            $count['total_client'] = $data['server'][$ip]['total_client'];
            $count['succ_client'] = isset($data['server'][$ip]['succ_client'])?$data['server'][$ip]['succ_client']:[];
            $count['fail_client'] = isset($data['server'][$ip]['fail_client'])?$data['server'][$ip]['fail_client']:[];
            $count['ret_code'] = isset($data['server'][$ip]['ret_code'])?$data['server'][$ip]['ret_code']:[];
            $count['succ_ret_code'] = isset($data['server'][$ip]['succ_ret_code'])?$data['server'][$ip]['succ_ret_code']:[];
        }
        if ($type == self::T_CLIENT and !empty($ip)) {
            $count['total_count'] = $data['client'][$ip]['total_count'];
            $count['fail_count'] = $data['client'][$ip]['fail_count'];
            $count['total_time'] = $data['client'][$ip]['total_time'];
            $count['max_time'] = $data['client'][$ip]['max_time'];
            $count['min_time'] = $data['client'][$ip]['min_time'];
            $count['total_fail_time'] =  $data['client'][$ip]['total_fail_time'];

            $count['total_server'] = $data['client'][$ip]['total_server'];
            $count['succ_server'] = isset($data['client'][$ip]['succ_server'])?$data['client'][$ip]['succ_server']:[];
            $count['fail_server'] = isset($data['client'][$ip]['fail_server'])?$data['client'][$ip]['fail_server']:[];
            $count['ret_code'] = isset($data['client'][$ip]['ret_code'])?$data['client'][$ip]['ret_code']:[];
            $count['succ_ret_code'] = isset($data['client'][$ip]['succ_ret_code'])?$data['client'][$ip]['succ_ret_code']:[];
        }
        //平均响应时间
        if ($count['total_count'] != 0) {
            $count['avg_time'] = $count['total_time'] / $count['total_count'];
        } else {
            $count['avg_time'] = 0;
        }
        //平均失败响应时间
        if ($count['fail_count'] != 0) {
            $count['avg_fail_time'] = $count['total_fail_time'] / $count['fail_count'];
        } else {
            $count['avg_fail_time'] = 0;
        }

        return $count;
    }

    public static function tryEncode($data, $key)
    {
        if (!empty($data[$key])) {
            return json_encode($data[$key]);
        }

        return "";
    }
}
