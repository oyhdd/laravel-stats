<?php

namespace Oyhdd\StatsCenter\Http\Controllers;

use DB;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Row;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Layout\Content;
use Encore\Admin\Widgets\Tab;
use Encore\Admin\Widgets\InfoBox;
use Encore\Admin\Widgets\Form;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Oyhdd\StatsCenter\Models\StatsSum;
use Oyhdd\StatsCenter\Models\Api;
use Oyhdd\StatsCenter\Models\Module;
use Oyhdd\StatsCenter\Models\Stats;
use Oyhdd\StatsCenter\Models\StatsClient;
use Oyhdd\StatsCenter\Models\StatsServer;

/**
 * 模调统计
 */
class StatsController extends Controller
{
    /**
     * 模调统计首页
     *
     * @return Grid
     */
    public function index(Request $request, Content $content)
    {
        $date_key = $request->get('date_key', date('Y-m-d'));
        $date_key = date("Y-m-d", strtotime($date_key));
        $save_day = config('statscenter.save_day');
        if (strtotime($date_key) <= strtotime("-{$save_day} day")) {
            admin_error("查询失败", "无法查看{$save_day}天前的统计信息。");
        }

        $interfaceList = Api::getList()->toArray();
        $interfaceList = array_column($interfaceList, null, 'id');

        $grid = new Grid(new StatsSum());
        $grid->model()->where(['date_key' => $date_key]);

        $grid->column('module.name', '模块名');
        $grid->column('interface_name', '接口名称')->display(function () use ($interfaceList) {
            return isset($interfaceList[$this->interface_id]) ? $interfaceList[$this->interface_id]['name'] : '';
        });
        $grid->date_key('日期')->sortable();
        $grid->column('total_count', '调用次数')->sortable();
        $grid->succ_count('成功次数')->display(function ($succ_count) {
            if (!empty($succ_count)) {
                return "<span class='label label-success'>{$succ_count}</span>";
            }
            return $succ_count;
        })->sortable();
        $grid->fail_count('失败次数')->display(function ($fail_count) {
            if (!empty($fail_count)) {
                return "<span class='label label-danger'>{$fail_count}</span>";
            }
            return $fail_count;
        })->sortable();
        $grid->succ_rate('成功率')->display(function ($succ_rate) {
            return $succ_rate."%";
        })->sortable();
        $grid->max_time('响应最大值')->display(function ($max_time) {
            return $max_time."ms";
        })->sortable();
        $grid->min_time('响应最小值')->display(function ($min_time) {
            return $min_time."ms";
        })->sortable();
        $grid->avg_time('平均响应时间')->display(function ($avg_time) {
            return $avg_time."ms";
        })->sortable();
        $grid->avg_fail_time('平均失败时间')->display(function ($avg_fail_time) {
            return $avg_fail_time."ms";
        })->sortable();

        $grid->disableCreation();
        $grid->actions(function ($actions) use ($date_key) {
            $actions->disableView()->disableDelete()->disableEdit();
            $routePrefix = config('admin.route.prefix');
            $param = "interface_id={$actions->row->interface_id}&module_id={$actions->row->module_id}&date_key={$date_key}";
            $history_param = "interface_id={$actions->row->interface_id}&start_date=".date("Y-m-d", strtotime($date_key)-7*86400)."&end_date={$date_key}";
            $actions->append("<a href='/{$routePrefix}/stats/detail?{$param}'>调用明细&nbsp;</a>");
            $actions->append("&nbsp;|&nbsp;<a href='/{$routePrefix}/stats/history?{$history_param}'>&nbsp;历史数据对比&nbsp;</a>");
            $actions->append("&nbsp;|&nbsp;<a href='/{$routePrefix}/stats/client?{$param}'>&nbsp;主调明细&nbsp;</a>");
            $actions->append("&nbsp;|&nbsp;<a href='/{$routePrefix}/stats/server?{$param}'>&nbsp;被调明细</a>");
        });

        $grid->filter(function ($filter) {
            $filter->column(1/3, function ($filter) {
                $moduleList = Module::getList()->pluck('name', 'id')->toArray();
                array_walk($moduleList, function (&$module, $module_id){
                    $module = $module_id.' : '.$module;
                });
                $filter->equal('module_id', '模块名')->select($moduleList);
                $filter->date('date_key', '日期')->datetime(['format' => 'YYYY-MM-DD'])->default(date("Y-m-d"));
            });
            $filter->column(2/3, function ($filter) {
                // 去掉默认的id过滤器
                $filter->disableIdFilter();
                $interfaceList = Api::getList()->pluck('name', 'id')->toArray();
                array_walk($interfaceList, function (&$interface, $interface_id){
                    $interface = $interface_id.' : '.$interface;
                });
                $filter->equal('interface_id', '接口名')->select($interfaceList);
            });
        });

        $grid->footer(function ($query){
            $total_interface = $query->orderBy('interface_id')->count();
            $data = $query->select(['total_count', 'succ_count'])->get()->toArray();
            $total_count = $succ_count = $succ_rate = 0;
            foreach ($data as $model) {
                $total_count += $model['total_count'];
                $succ_count += $model['succ_count'];
                $succ_rate = floor(($succ_count / $total_count) * 10000) / 100;
            }
            return "<h4><span class='label label-warning'>共有 {$total_interface} 个接口</span>&nbsp;&nbsp;&nbsp;&nbsp;
                    <span class='label label-warning'>请求 {$total_count} 次，成功 {$succ_count}，成功率 {$succ_rate}%</span></h4>";
        });

        return $content
            ->title('模调统计')
            ->description("仅保存{$save_day}天内数据")
            ->breadcrumb(['text' => '模调统计'])
            ->body($grid);
    }

    /**
     * 接口模调统计详情
     *
     * @return Grid
     */
    public function detail(Request $request, Content $content)
    {
        $interface_id = $request->get('interface_id', '');
        if (empty($interface_id)) {
            admin_error("查询失败", "请指定接口");
        }
        $date_key = $request->get('date_key', date('Y-m-d'));
        $date_key = date("Y-m-d", strtotime($date_key));
        $save_day = config('statscenter.save_day');
        if (strtotime($date_key) <= strtotime("-{$save_day} day")) {
            admin_error("查询失败", "无法查看{$save_day}天前的统计信息。");
        }

        $interface = Api::find($interface_id);

        $grid = new Grid(new Stats());
        $grid->model()->where(['date_key' => $date_key, 'interface_id' => $interface_id])->orderBy('time_key', 'desc');

        $grid->column('module', '模块名')->display(function ($module) {
            return $module['id']." : ".$module['name'];
        });
        $grid->column('api', '接口名称')->display(function ($api) {
            return $api['id']." : ".$api['name'];
        });
        $grid->date_key('日期')->sortable();
        $grid->time_key('时间')->display(function ($time_key) use ($date_key) {
            $time_key_min = config('statscenter.time_key_min', 5);
            return date("H:i", strtotime($date_key) + $time_key*$time_key_min*60)."~".date("H:i", strtotime($date_key) + ($time_key+1)*$time_key_min*60);
        })->sortable();
        $grid->column('total_count', '调用次数')->label('info')->sortable();
        $grid->column('succ_count', '成功次数')->display(function () {
            return $this->total_count - $this->fail_count;
        })->label('success');
        $grid->fail_count('失败次数')->display(function ($fail_count) {
            if (!empty($fail_count)) {
                return "<span class='label label-danger'>{$fail_count}</span>";
            }
            return $fail_count;
        })->sortable();
        $grid->column('succ_rate', '成功率')->display(function () {
            return round((1-$this->fail_count/$this->total_count) * 100, 2)."%";
        })->label('info');
        $grid->max_time('响应最大值')->display(function ($max_time) {
            return $max_time."ms";
        })->sortable();
        $grid->min_time('响应最小值')->display(function ($min_time) {
            return $min_time."ms";
        })->sortable();
        $grid->avg_time('平均响应时间')->display(function ($succ_rate) {
            return $succ_rate."ms";
        })->sortable();
        $grid->avg_fail_time('平均失败时间')->display(function ($avg_fail_time) {
            return $avg_fail_time."ms";
        })->sortable();

        $grid->disableCreation();
        $grid->actions(function ($actions) use ($date_key) {
            $actions->disableView()->disableDelete()->disableEdit();
            $routePrefix = config('admin.route.prefix');
            $param = "interface_id={$actions->row->interface_id}&module_id={$actions->row->module_id}&date_key={$date_key}";
            $history_param = "interface_id={$actions->row->interface_id}&start_date=".date("Y-m-d", strtotime($date_key)-7*86400)."&end_date={$date_key}";
            $actions->append("<a href='/{$routePrefix}/stats/history?{$history_param}'>&nbsp;历史数据对比&nbsp;</a>");
            $actions->append("&nbsp;|&nbsp;<a href='/{$routePrefix}/stats/client?{$param}'>&nbsp;主调明细&nbsp;</a>");
            $actions->append("&nbsp;|&nbsp;<a href='/{$routePrefix}/stats/server?{$param}'>&nbsp;被调明细&nbsp;</a>");
        });

        $grid->filter(function ($filter) {
            $filter->column(1/3, function ($filter) {
                $moduleList = Module::getList()->pluck('name', 'id')->toArray();
                array_walk($moduleList, function (&$module, $module_id){
                    $module = $module_id.' : '.$module;
                });
                $filter->equal('module_id', '模块名')->select($moduleList);
                $filter->date('date_key', '日期')->datetime(['format' => 'YYYY-MM-DD'])->default(date("Y-m-d"));
            });
            $filter->column(2/3, function ($filter) {
                // 去掉默认的id过滤器
                $filter->disableIdFilter();
                $interfaceList = Api::getList()->pluck('name', 'id')->toArray();
                array_walk($interfaceList, function (&$interface, $interface_id){
                    $interface = $interface_id.' : '.$interface;
                });
                $filter->equal('interface_id', '接口名')->select($interfaceList);
            });
        });

        return $content
            ->title('接口调用明细')
            ->description("仅保存{$save_day}天内数据")
            ->breadcrumb(['text' => '模调统计', 'url' => '/stats/index'], ['text' => '接口调用明细'])
            ->body($grid);
    }

    /**
     * 主调明细
     *
     * @return Grid
     */
    public function client(Request $request, Content $content)
    {
        $interface_id = $request->get('interface_id', '');
        if (empty($interface_id)) {
            admin_error("查询失败", "请指定接口");
        }
        $date_key = $request->get('date_key', date('Y-m-d'));
        $date_key = date("Y-m-d", strtotime($date_key));
        $save_day = config('statscenter.save_day');
        if (strtotime($date_key) <= strtotime("-{$save_day} day")) {
            admin_error("查询失败", "无法查看{$save_day}天前的统计信息。");
        }

        $interface = Api::find($interface_id);
        $grid = new Grid(new StatsClient());
        $grid->model()
            ->select(DB::raw('max(max_time) as max_time,min(min_time) as min_time,ip,sum(total_count) as total_count,sum(fail_count) as fail_count,sum(total_time) as total_time,sum(total_fail_time) as total_fail_time'))
            ->where(['date_key' => $date_key, 'interface_id' => $interface_id])
            ->groupBy(['ip']);
        $models = StatsClient::where(['date_key' => $date_key, 'interface_id' => $interface_id])->get()->toArray();
        $grid = $this->statsGrid($grid, $models);

        return $content
            ->title('主调明细')
            ->description("仅保存{$save_day}天内数据")
            ->breadcrumb(['text' => '模调统计', 'url' => '/stats/index'], ['text' => '主调明细'])
            ->body($grid);
    }

    /**
     * 被调明细
     *
     * @return Grid
     */
    public function server(Request $request, Content $content)
    {
        $interface_id = $request->get('interface_id', '');
        if (empty($interface_id)) {
            admin_error("查询失败", "请指定接口");
        }
        $date_key = $request->get('date_key', date('Y-m-d'));
        $date_key = date("Y-m-d", strtotime($date_key));
        $save_day = config('statscenter.save_day');
        if (strtotime($date_key) <= strtotime("-{$save_day} day")) {
            admin_error("查询失败", "无法查看{$save_day}天前的统计信息。");
        }

        $interface = Api::find($interface_id);
        $grid = new Grid(new StatsServer());
        $grid->model()
            ->select(DB::raw('max(max_time) as max_time,min(min_time) as min_time,ip,sum(total_count) as total_count,sum(fail_count) as fail_count,sum(total_time) as total_time,sum(total_fail_time) as total_fail_time'))
            ->where(['date_key' => $date_key, 'interface_id' => $interface_id])
            ->groupBy(['ip']);

        $models = StatsServer::where(['date_key' => $date_key, 'interface_id' => $interface_id])->get()->toArray();
        $grid = $this->statsGrid($grid, $models);

        return $content
            ->title('被调明细')
            ->description("仅保存{$save_day}天内数据")
            ->breadcrumb(['text' => '模调统计', 'url' => '/stats/index'], ['text' => '被调明细'])
            ->body($grid);
    }

    /**
     * 主、被调表格
     *
     * @return Grid
     */
    public function statsGrid(Grid $grid, $models)
    {
        $data = [];
        $total_count = 0;
        $fail_count = 0;
        foreach ($models as $model) {
            $total_count += $model['total_count'];
            $fail_count += $model['fail_count'];
        }

        $grid->column('ip', '机器IP');
        $grid->call_rate('调用比例')->display(function () use ($total_count){
            if ($total_count != 0) {
                return round(($this->total_count / $total_count) * 100, 2)."%";
            }
            return "--";
        });
        $grid->fail_rate('失败比例')->display(function () use ($fail_count) {
            if ($fail_count != 0) {
                return round(($this->fail_count / $fail_count) * 100, 2)."%";
            }
            return "--";
        });
        $grid->total_count('调用总数')->display(function () {
            return $this->total_count;
        })->sortable()->label('info');
        $grid->succ_count('成功总数')->display(function () {
            return $this->total_count - $this->fail_count;
        })->label('success');
        $grid->fail_count('失败总数')->display(function () {
            if (empty($this->fail_count)) {
                return $this->fail_count;
            }
            return "<span class='label label-danger'>{$this->fail_count}</span>";
        })->sortable();
        $grid->succ_rate('成功率')->display(function () {
            return round((1 - $this->fail_count / $this->total_count) * 100, 2)."%";
        });
        $grid->max_time('响应最大值')->display(function () {
            return $this->max_time.'ms';
        })->sortable();
        $grid->min_time('响应最小值')->display(function () {
            return $this->min_time.'ms';
        })->sortable();
        $grid->avg_time('平均响应时间')->display(function () {
            if ($this->total_count != 0) {
                return round($this->total_time / $this->total_count, 2).'ms';
            }
            return "--";
        });
        $grid->avg_fail_time('平均失败时间')->display(function () {
            if ($this->fail_count != 0) {
                return round($this->total_fail_time / $this->fail_count, 2).'ms';
            }
            return "--";
        });

        $grid->disableCreation();
        $grid->disableActions();

        $grid->filter(function ($filter) {
            $filter->column(1/3, function ($filter) {
                $moduleList = Module::getList()->pluck('name', 'id')->toArray();
                array_walk($moduleList, function (&$module, $module_id){
                    $module = $module_id.' : '.$module;
                });
                $filter->equal('module_id', '模块名')->select($moduleList);
                $filter->date('date_key', '日期')->datetime(['format' => 'YYYY-MM-DD'])->default(date("Y-m-d"));
            });
            $filter->column(2/3, function ($filter) {
                // 去掉默认的id过滤器
                $filter->disableIdFilter();
                $interfaceList = Api::getList()->pluck('name', 'id')->toArray();
                array_walk($interfaceList, function (&$interface, $interface_id){
                    $interface = $interface_id.' : '.$interface;
                });
                $filter->equal('interface_id', '接口名')->select($interfaceList);
            });
        });

        return $grid;
    }

    /**
     * 历史数据对比
     *
     * @return Grid
     */
    public function history(Request $request, Content $content)
    {
        $params = $request->all();
        if (empty($params['interface_id'])) {
            admin_warning("", "请指定接口");
        }
        if (empty($params['end_date'])) {
            $params['end_date'] = date('Y-m-d');
        }
        if (empty($params['start_date'])) {
            $params['start_date'] = date('Y-m-d', strtotime($params['end_date'])-7*86400);
        }
        $save_day = config('statscenter.save_day');
        if (strtotime($params['end_date']) - strtotime($params['start_date']) > $save_day * 86400) {
            admin_warning("", "最多可对比连续{$save_day}天的统计数据");
        }

        // 每日接口请求量对比
        $xDays = $xDayData = $yCountData = [];
        $statsSum = StatsSum::where(['interface_id' => $params['interface_id']])->get()->toArray();
        $statsSum = array_column($statsSum, null, 'date_key');
        $days = (strtotime($params['end_date']) - strtotime($params['start_date'])) / 86400;
        for ($i=0; $i <= $days; $i++) {
            $day_key = date("Y-m-d", strtotime($params['start_date']) + $i * 86400);
            $xDays[] = $day_key;
            $xDayData[] = date("m-d", strtotime($day_key));
            $yCountData['总调用量'][] = isset($statsSum[$day_key]) ? $statsSum[$day_key]['total_count'] : 0;
            $yCountData['成功次数'][] = isset($statsSum[$day_key]) ? $statsSum[$day_key]['succ_count'] : 0;
            $yCountData['失败次数'][] = isset($statsSum[$day_key]) ? $statsSum[$day_key]['fail_count'] : 0;
            $yCountData['成功率'][] = isset($statsSum[$day_key]) ? $statsSum[$day_key]['succ_rate'] : 0;
        }
        $box_count_days = new Box("每日接口请求量对比", view('stats::history_count_days', [
            'xData' => json_encode($xDayData),
            'yData' => json_encode($yCountData),
        ]));
        $box_count_days->collapsable()->style('primary')->solid();

        // 单日每小时接口请求量对比
        $xHourData = $yCountToday = $yTimeToday = [];
        $statsData = Stats::where(['date_key' => $params['end_date'],'interface_id' => $params['interface_id']])->get()->toArray();
        for ($i=1; $i <= 24; $i++) {
            $xHourData[] = sprintf("%02d:00", $i);
            $yCountToday['总调用量'][] = 0;
            $yCountToday['成功次数'][] = 0;
            $yCountToday['失败次数'][] = 0;
            $yCountToday['成功率'][] = 0;

            $yTimeToday['最大响应时间'][] = 0;
            $yTimeToday['最小响应时间'][] = 0;
            $yTimeToday['平均响应时间'][] = 0;
            $yTimeToday['平均失败时间'][] = 0;
        }
        $time_key_min = config('statscenter.time_key_min', 5);
        foreach ($statsData as $stats) {
            $time_key = $stats['time_key'];
            $hour = ceil(($time_key + 1) * $time_key_min / 60);

            $yCountToday['总调用量'][$hour-1] += $stats['total_count'];
            $yCountToday['失败次数'][$hour-1] += $stats['fail_count'];
            $yCountToday['成功次数'][$hour-1] = $yCountToday['总调用量'][$hour-1] - $yCountToday['失败次数'][$hour-1];
            if (!empty($yCountToday['总调用量'][$hour-1])) {
                $yCountToday['成功率'][$hour-1] = floor(($yCountToday['成功次数'][$hour-1] / $yCountToday['总调用量'][$hour-1]) * 10000) / 100;
            }

            $yTimeToday['最大响应时间'][$hour-1] = $stats['max_time'];
            $yTimeToday['最小响应时间'][$hour-1] = $stats['min_time'];
            $yTimeToday['平均响应时间'][$hour-1] = $stats['avg_time'];
            $yTimeToday['平均失败时间'][$hour-1] = $stats['avg_fail_time'];
        }
        $box_count_today = new Box("单日每小时接口请求量对比 ({$params['end_date']})", view('stats::history_count_today', [
            'xData'   => json_encode($xHourData),
            'yData'   => json_encode($yCountToday),
        ]));
        $box_count_today->collapsable()->style('primary')->solid();

        // 单日接口请求时间对比
        $box_time_today = new Box("单日接口请求时间对比（ms） ({$params['end_date']})", view('stats::history_time_today', [
            'xData' => json_encode($xHourData),
            'yData' => json_encode($yTimeToday),
        ]));
        $box_time_today->collapsable()->style('primary')->solid();

        // 每日接口请求时间对比
        $yTimeDays = [];
        foreach ($xDays as $day) {
            $yTimeDays['最大响应时间'][] = isset($statsSum[$day]) ? $statsSum[$day]['max_time'] : 0;
            $yTimeDays['最小响应时间'][] = isset($statsSum[$day]) ? $statsSum[$day]['min_time'] : 0;
            $yTimeDays['平均响应时间'][] = isset($statsSum[$day]) ? $statsSum[$day]['avg_time'] : 0;
            $yTimeDays['平均失败时间'][] = isset($statsSum[$day]) ? $statsSum[$day]['avg_fail_time'] : 0;
        }
        $box_time_days = new Box("每日接口请求时间对比（ms）", view('stats::history_time_days', [
            'xData' => json_encode($xDayData),
            'yData' => json_encode($yTimeDays),
        ]));
        $box_time_days->collapsable()->style('primary')->solid();

        $content->row(view('stats::history_filter', [
            'params' => $params,
            'apiList' => json_encode(Api::getList()->pluck('name', 'id')->toArray())
        ]));
        $content->row(function(Row $row) use ($box_count_days, $box_count_today) {
            $row->column(6, $box_count_days);
            $row->column(6, $box_count_today);
        });
        $content->row(function(Row $row) use ($box_time_days, $box_time_today) {
            $row->column(6, $box_time_days);
            $row->column(6, $box_time_today);
        });

        return $content
            ->header('历史数据对比')
            ->description("最多可对比连续{$save_day}天的统计数据");
    }
}
