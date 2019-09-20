<?php

namespace Oyhdd\StatsCenter\Http\Controllers;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Layout\Content;
use Oyhdd\StatsCenter\Models\Module;
use Oyhdd\StatsCenter\Models\Project;

/**
 * 模块管理
 */
class ModuleController extends BaseController
{

    /**
     * {@inheritdoc}
     */
    protected function title()
    {
        return "模块管理";
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Module());
        $grid->model()->where('status', '>', Module::STATUS_DELETED);

        $grid->column('id', 'ID')->sortable();
        $grid->column('project.name', '项目名')->label();
        $grid->column('name', '模块名');
        $grid->column('user.name', '负责人');
        $grid->column('backup_uids', '备选负责人')->display(function ($backup_uids) {
            return implode(',', $this->getUserList(explode(',', $backup_uids)));
        });
        $grid->enable_alarm('告警策略')->using(Module::$label_enable_alarm);
        $grid->status('状态')->using(Module::$label_status);
        $grid->create_time('创建时间')->sortable();
        // $grid->update_time('更新时间')->sortable();

        $grid->actions(function ($actions) {
            $actions->disableView();
        });

        $grid->filter(function ($filter) {
            $filter->column(1/3, function ($filter) {
                // 去掉默认的id过滤器
                $filter->disableIdFilter();
                $filter->like('name', '模块名');
                $filter->equal('project_id', '项目名')->select(Project::getList()->pluck('name', 'id')->toArray());
            });
            $filter->column(1/3, function ($filter) {
                $filter->where(function ($query) {
                    $query->whereHas('user', function ($query) {
                        $query->where('name', 'like', "%{$this->input}%");
                    });
                }, '负责人');
                $filter->equal('status', '状态')->select(Module::$label_status);
            });
            $filter->column(1/3, function ($filter) {
                $filter->equal('enable_alarm', '告警策略')->select(Module::$label_enable_alarm);
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
        $form = new Form(new Module());
        $form->tab('模块信息',function($form) {
            $form->display('id', 'ID');
            $form->select('project_id', '项目名')->options(Project::getList()->pluck('name', 'id')->toArray())->rules('required');
            $form->text('name', '模块名')->rules('required');
            $form->select('owner_uid', '负责人')->options(Module::getUserList());
            $form->multipleSelect('backup_uids', '备选负责人')->options(Module::getUserList());
            $form->select('status', '状态')->options(Module::$label_status);
            $form->textarea('intro', '简介');
            $form->display('create_time', '创建时间');
            $form->display('update_time', '更新时间');
        });
        $form->tab('告警设置',function($form) {
            $form->radio('enable_alarm', '告警策略')->options(Module::$label_enable_alarm)->default(Module::ALARM_DISABLE);
            $form->checkbox('alarm_types', '告警方式')->options(Module::$label_alarm_types);
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
