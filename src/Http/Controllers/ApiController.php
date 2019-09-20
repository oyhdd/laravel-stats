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
        $grid->column('module.name', '模块名')->label();
        $grid->column('name', '接口名');
        $grid->column('success_rate', '成功率阀值');
        $grid->column('request_total_rate', '调用量波动阀值');
        $grid->column('user.name', '负责人');
        $grid->column('backup_uids', '备选负责人')->display(function ($backup_uids) {
            return implode(',', $this->getUserList(explode(',', $backup_uids)));
        });
        $grid->enable_alarm('告警策略')->using(Api::$label_enable_alarm);
        $grid->status('状态')->using(Api::$label_status);
        $grid->create_time('创建时间')->sortable();
        // $grid->update_time('更新时间')->sortable();

        $grid->actions(function ($actions) {
            $actions->disableView();
        });

        $grid->filter(function ($filter) {
            $filter->column(1/3, function ($filter) {
                // 去掉默认的id过滤器
                $filter->disableIdFilter();
                $filter->like('name', '接口名');
                $filter->equal('module_id', '模块名')->select(Module::getList()->pluck('name', 'id')->toArray());
            });
            $filter->column(1/3, function ($filter) {
                $filter->where(function ($query) {
                    $query->whereHas('user', function ($query) {
                        $query->where('name', 'like', "%{$this->input}%");
                    });
                }, '负责人');
                $filter->equal('enable_alarm', '告警策略')->select(Api::$label_enable_alarm);
            });
            $filter->column(1/3, function ($filter) {
                $filter->equal('status', '状态')->select(Api::$label_status);
                $filter->between('create_time', '创建时间')->datetime();
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
            $form->select('module_id', '模块名')->options(Module::getList()->pluck('name', 'id')->toArray())->rules('required');
            $form->text('name', '接口名')->rules('required');
            $form->select('owner_uid', '负责人')->options(Api::getUserList());
            $form->multipleSelect('backup_uids', '备选负责人')->options(Api::getUserList());
            $form->select('status', '状态')->options(Api::$label_status);
            $form->textarea('intro', '简介');
            $form->display('create_time', '创建时间');
            $form->display('update_time', '更新时间');
        });
        $form->tab('告警设置',function($form) {
            $form->radio('enable_alarm', '告警策略')->options(Api::$label_enable_alarm)->default(Api::ALARM_DISABLE);
            $form->checkbox('alarm_types', '告警方式')->options(Api::$label_alarm_types);
            $form->multipleSelect('alarm_uids', '告警接收方')->options(Api::getUserList());
            $form->number('alarm_per_minute', '告警间隔时间(分钟)');
            $form->number('success_rate', '成功率阀值(0-100)');
            $form->number('request_wave_rate', '调用量波动阀值(0-100)');
            $form->number('request_total_rate', '调用量报警阀值(0-100)');
            $form->number('avg_time_rate', '平均耗时报警阀值(ms),0表示不开启');
        });

        $form->tools(function (Form\Tools $tools) {
            $tools->disableView();
        });
        $form->footer(function ($footer) {
            $footer->disableViewCheck()->disableEditingCheck()->disableCreatingCheck();
        });

        return $form->setWidth(7,3);
    }
}
