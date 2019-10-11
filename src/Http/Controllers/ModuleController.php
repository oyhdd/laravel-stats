<?php

namespace Oyhdd\StatsCenter\Http\Controllers;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Layout\Content;
use Oyhdd\StatsCenter\Models\Module;
use Oyhdd\StatsCenter\Models\Project;
use Illuminate\Validation\Rule;

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

        $routePrefix = config('admin.route.prefix');
        $grid->column('id', 'ID')->sortable();
        $grid->column('project.name', '项目名');
        $grid->column('name', '模块名');
        $grid->enable_alarm('告警策略')->switch([
            'on'  => ['value' => Module::ALARM_ENABLE, 'text' => 'ON'],
            'off' => ['value' => Module::ALARM_DISABLE, 'text' => 'OFF'],
        ]);
        $grid->column('user.name', '负责人');
        $grid->column('backup_uids', '备选负责人')->display(function ($backup_uids) {
            return implode(',', $this->getUserList(explode(',', $backup_uids)));
        });
        $grid->create_time('创建时间')->sortable();

        $grid->actions(function ($actions) use ($routePrefix) {
            $actions->disableView();
            $actions->prepend("<a href='/{$routePrefix}/stats/api?module_id={$this->getKey()}'>&nbsp;<span class='label label-success'>接口管理</span>&nbsp;</a>");
        });

        $grid->filter(function ($filter) {
            $filter->disableIdFilter();// 去掉默认的id过滤器
            $filter->column(1/3, function ($filter) {
                $filter->equal('id', '模块名')->select(Module::getList()->pluck('name', 'id')->toArray());
            });
            $filter->column(1/3, function ($filter) {
                $filter->equal('project_id', '项目名')->select(Project::getList()->pluck('name', 'id')->toArray());
            });
            $filter->column(1/3, function ($filter) {
                $filter->equal('enable_alarm', '告警策略')->select(Module::$label_enable_alarm);
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
        $params = $this->request->all();
        $form = new Form(new Module());
        $form->tab('模块信息',function($form) use ($params) {
            $form->display('id', 'ID');
            $form->select('project_id', '项目名')->options(Project::getList()->pluck('name', 'id')->toArray())->rules('required');
            $form->text('name', '模块名')->rules(function ($form) use ($params) {
                return [
                    'required',
                    Rule::unique('module')->where(function ($query) use ($params) {
                        $where = [];
                        if (isset($params['project_id'])) {
                            $where['project_id'] = $params['project_id'];
                        }
                        if (isset($params['name'])) {
                            $where['name'] = $params['name'];
                        }
                        return $query->where($where);
                    })
                    ->ignore($form->model()->id),
                ];
            });
            $form->select('owner_uid', '负责人')->options(Module::getUserList());
            $form->multipleSelect('backup_uids', '备选负责人')->options(Module::getUserList());
            $form->textarea('intro', '简介');
            $form->display('create_time', '创建时间');
            $form->display('update_time', '更新时间');
        });
        $form->tab('告警设置',function($form) {
            $form->switch('enable_alarm', '告警策略')->states([
                'on'  => ['value' => Module::ALARM_ENABLE, 'text' => 'ON'],
                'off' => ['value' => Module::ALARM_DISABLE, 'text' => 'OFF'],
            ]);
            $form->checkbox('alarm_types', '告警方式')->options(Module::$label_alarm_types);
            $form->multipleSelect('alarm_uids', '告警接收方')->options(Module::getUserList());
            $form->number('alarm_per_minute', '告警间隔时间(分钟)')->default(10)->min(1)->help('此间隔时间内相同的内容将不会告警');
            $form->number('success_rate', '成功率阀值')->default(0)->min(0)->max(100)->help('0-100，低于该阈值将会告警');
            $form->number('request_total_rate', '调用量报警阀值')->default(0)->min(0)->help('0表示不开启，低于该阈值将会告警');
            $form->number('request_wave_rate', '调用量波动阀值')->default(0)->min(0)->max(100)->help('0-100，0表示不开启，高于该阈值将会告警（今天与昨天的调用量波动值）');
            $form->number('avg_time_rate', '平均耗时报警阀值(ms),')->default(0)->min(0)->help('0表示不开启，高于该阈值将会告警');
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
