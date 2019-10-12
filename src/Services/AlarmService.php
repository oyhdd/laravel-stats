<?php
namespace Oyhdd\StatsCenter\Services;

use App;
use Log;
use Oyhdd\StatsCenter\Models\BaseModel;
use Oyhdd\StatsCenter\Models\Module;
use Illuminate\Support\Facades\Cache;

/**
 * 模调系统告警
 */
class AlarmService
{
    private $alarm = [];

    public function __construct()
    {
        $config = config('statscenter.alarm', []);
        foreach ($config as $type => $params) {
            try {
                $this->alarm[$type] = App::make($params['class'], $params);
            } catch (\Throwable $th){
                Log::error('初始化 AlarmService 失败', [sprintf(' %s At %s:%d', $th->getMessage(), $th->getFile(), $th->getLine())]);
            }
        }
    }

    /**
     * @name   发送告警
     *
     * @author Eric
     * @param  array      $interface 接口信息
     * @param  array      $stats     接口统计信息
     * @return bool
     */
    public function alarm($interface, $stats)
    {
        // 此接口未开启告警
        $module = Module::find($interface['module_id']);
        if (empty($interface['enable_alarm']) || empty($module)) {
            return false;
        }
        // 此接口所属模块未开启告警
        $module = $module->toArray();
        if (empty($module['enable_alarm'])) {
            return false;
        }

        try {
            $alarm_content      = "";
            $success_rate       = $interface['success_rate'];
            $request_total_rate = $interface['request_total_rate'];
            $avg_time_rate      = $interface['avg_time_rate'];
            $alarm_per_minute   = $interface['alarm_per_minute'];
            $alarm_uids         = $interface['alarm_uids'];
            $alarm_types        = $interface['alarm_types'];
            $request_wave_rate  = $interface['request_wave_rate'];
            // 未自定义告警设置时使用所属模块的配置
            if ($interface['enable_alarm_setting'] == BaseModel::ALARM_DISABLE) {
                $success_rate       = $module['success_rate'];
                $request_total_rate = $module['request_total_rate'];
                $avg_time_rate      = $module['avg_time_rate'];
                $alarm_per_minute   = $module['alarm_per_minute'];
                $alarm_uids         = $module['alarm_uids'];
                $alarm_types        = $module['alarm_types'];
                $request_wave_rate  = $module['request_wave_rate'];
            }

            // 重复数据请求间隔时间
            $key = "wechat:stats_alarm_{$interface['id']}";
            if (Cache::get($key) && empty($stats['total_count_yesterday'])) {
                return true;
            }

            // 低于成功率阀值告警
            if (isset($stats['succ_rate']) && $stats['succ_rate'] < $success_rate) {
                $alarm_content .= "成功率 {$stats['succ_rate']}%，低于 {$success_rate}%\n";
            }

            // 低于调用量报警阀值告警:每日判断一次
            if ($request_total_rate > 0 && !empty($stats['total_count_yesterday']) && isset($stats['total_count']) && $stats['total_count'] < $request_total_rate) {
                $alarm_content .= "调用量 {$stats['total_count']}，低于 {$request_total_rate}\n";
            }

            // 高于平均耗时报警阀值告警
            if (isset($stats['avg_time']) && $avg_time_rate > 0 && $stats['avg_time'] > $avg_time_rate) {
                $alarm_content .= "平均耗时 {$stats['avg_time']}ms，高于 {$avg_time_rate}ms\n";
            }

            // 高于调用量波动阀值告警（今天与昨天的调用量波动值）
            if ($request_wave_rate >0 && !empty($stats['total_count_yesterday'])){
                $wave_rate = floor((($stats['total_count'] - $stats['total_count_yesterday']) / $stats['total_count_yesterday']) * 10000) / 100;
                if ($wave_rate > $request_wave_rate) {
                    $alarm_content .= "调用量波动高于 {$request_wave_rate}%：\n当前 {$stats['total_count']}，昨日 {$stats['total_count_yesterday']}\n";
                }
            }

            // 发送告警
            if (!empty($alarm_content)) {
                $alarm_content = "模块 -- {$module['id']}:{$module['name']}\n接口 -- {$interface['id']}:{$interface['name']}\n\n".$alarm_content;
                if ($this->send($alarm_types, $alarm_uids, $alarm_content)) {
                    Cache::put($key, 1, $alarm_per_minute * 60);
                }
            }
        } catch (\Throwable $th){
            Log::error('告警失败', [sprintf(' %s At %s:%d', $th->getMessage(), $th->getFile(), $th->getLine())]);
        }

        return false;
    }

    /**
     * @name   发送告警消息
     *
     * @author Eric
     * @param  string      $type                告警类型,逗号相隔： 1 微信 2 短信 3 邮件
     * @param  string      $alarm_uids          告警uids
     * @param  string      $message             告警内容
     * @return bool
     */
    public function send($type, $alarm_uids, $message)
    {
        try {
            $ret = false;
            $type = explode(',', $type);
            $alarm_uids = explode(',', $alarm_uids);

            foreach ($type as $alarm_type) {
                switch ($alarm_type) {
                    // 微信告警
                    case BaseModel::ALARM_WECHAT:
                        if (isset($this->alarm['wechat']) && $this->alarm['wechat']->sendMessage($alarm_uids, $message)) {
                            $ret = true;
                        }
                        break;
                    // 短信告警
                    case BaseModel::ALARM_MSG:
                        if (isset($this->alarm['sms']) && $this->alarm['sms']->sendMessage($alarm_uids, $message)) {
                            $ret = true;
                        }
                        break;
                    // 邮件告警
                    case BaseModel::ALARM_EMAIL:
                        if (isset($this->alarm['email']) && $this->alarm['email']->sendMessage($alarm_uids, $message)) {
                            $ret = true;
                        }
                        break;

                    default:
                        break;
                }
            }
        } catch (\Throwable $th){
            Log::error('发送告警消息失败', [sprintf(' %s At %s:%d', $th->getMessage(), $th->getFile(), $th->getLine())]);
        }

        return $ret;
    }
}
