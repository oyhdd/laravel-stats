<?php

namespace Oyhdd\StatsCenter\Http\Controllers;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Layout\Content;
use Oyhdd\StatsCenter\Models\Api;
use Oyhdd\StatsCenter\Models\Module;

/**
 * 接口管理
 */
class ApiController extends BaseController
{

    /**
     * {@inheritdoc}
     */
    protected function title()
    {
        return "接口管理";
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Api());

        $grid->column('id', 'ID')->sortable();
        $grid->column('module', '模块名')->display(function ($module) {
            $routePrefix = config('admin.route.prefix');
            return "<a href='/{$routePrefix}/stats/index/?module_id=".$module["id"]."'>{$module['id']}:{$module['name']}</a>";
        });
        $grid->column('name', '接口名')->display(function ($name) {
            $routePrefix = config('admin.route.prefix');
            return "<a href='/{$routePrefix}/stats/index?interface_id=".$this->id."'>{$this->id}:{$name}</a>";
        });
        $grid->enable_alarm('告警策略')->switch([
            'on'  => ['value' => Api::ALARM_ENABLE, 'text' => 'ON'],
            'off' => ['value' => Api::ALARM_DISABLE, 'text' => 'OFF'],
        ]);
        $grid->column('enable_alarm_setting', '自定义告警')->switch([
            'on'  => ['value' => Api::ALARM_ENABLE, 'text' => 'ON'],
            'off' => ['value' => Api::ALARM_DISABLE, 'text' => 'OFF'],
        ]);
        $grid->success_rate('成功率阀值')->display(function ($success_rate) {
            if (!empty($success_rate)) {
                return $success_rate."%";
            }
            return $success_rate;
        });
        $grid->column('request_total_rate', '调用量报警阀值');
        $grid->column('avg_time_rate', '平均耗时报警阀值(ms)');
        $grid->request_wave_rate('调用量波动阀值')->display(function ($request_wave_rate) {
            if (!empty($request_wave_rate)) {
                return $request_wave_rate."%";
            }
            return $request_wave_rate;
        });
        $grid->column('alarm_per_minute', '告警间隔时间(分钟)');
        $grid->column('user.name', '负责人');
        $grid->create_time('创建时间')->sortable();

        $grid->disableCreation();
        $grid->actions(function ($actions) {
            $actions->disableView()->disableDelete();
        });

        $grid->filter(function ($filter) {
            $filter->disableIdFilter();// 去掉默认的id过滤器
            $filter->column(2/3, function ($filter) {
                $moduleList = Module::getList()->pluck('name', 'id')->toArray();
                array_walk($moduleList, function (&$module, $module_id){
                    $module = $module_id.' : '.$module;
                });
                $interfaceList = Api::getList()->pluck('name', 'id')->toArray();
                array_walk($interfaceList, function (&$interface, $interface_id){
                    $interface = $interface_id.' : '.$interface;
                });
                $filter->equal('module_id', '模块名')->select($moduleList);
                $filter->equal('id', '接口名')->select($interfaceList);
            });
            $filter->column(1/3, function ($filter) {
                $filter->equal('enable_alarm', '告警策略')->select(Api::$label_enable_alarm);
            });
        });

        return $grid;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    public function form()
    {
        $form = new Form(new Api());
        $form->tab('接口信息',function($form) {
            $form->display('id', 'ID');
            $form->display('module.name', '模块名');
            $form->display('name', '接口名');
            $form->select('owner_uid', '负责人')->options(Api::getUserList());
            $form->multipleSelect('backup_uids', '备选负责人')->options(Api::getUserList());
            $form->textarea('intro', '简介');
            $form->display('create_time', '创建时间');
            $form->display('update_time', '更新时间');
        });
        $form->tab('告警设置',function($form) {
            $form->switch('enable_alarm', '告警策略')->states([
                'on'  => ['value' => Api::ALARM_ENABLE, 'text' => 'ON'],
                'off' => ['value' => Api::ALARM_DISABLE, 'text' => 'OFF'],
            ]);
            $form->switch('enable_alarm_setting', '自定义告警设置')->states([
                'on'  => ['value' => Api::ALARM_ENABLE, 'text' => 'ON'],
                'off' => ['value' => Api::ALARM_DISABLE, 'text' => 'OFF'],
            ])->help('若关闭自定义告警设置，则使用该接口所属模块的告警配置');
            $form->fieldset('自定义告警设置', function (Form $form) {
                $form->checkbox('alarm_types', '告警方式')->options(Api::$label_alarm_types);
                $form->multipleSelect('alarm_uids', '告警接收方')->options(Api::getUserList());
                $form->number('alarm_per_minute', '告警间隔时间(分钟)')->default(10)->help('此间隔时间内相同的内容将不会告警');
                $form->number('success_rate', '成功率阀值')->default(0)->help('0-100，低于该阈值将会告警');
                $form->number('request_total_rate', '调用量报警阀值')->default(0)->help('0表示不开启，低于该阈值将会告警');
                $form->number('request_wave_rate', '调用量波动阀值')->default(0)->help('0-100，0表示不开启，高于该阈值将会告警（今天与昨天的调用量波动值）');
                $form->number('avg_time_rate', '平均耗时报警阀值(ms)')->default(0)->help('0表示不开启，高于该阈值将会告警');
            });
        });

        $form->tools(function (Form\Tools $tools) {
            $tools->disableView()->disableDelete();
        });
        $form->footer(function ($footer) {
            $footer->disableViewCheck()->disableEditingCheck()->disableCreatingCheck();
        });

        return $form->setWidth(7,3);
    }
}
