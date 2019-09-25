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

        $grid->column('id', 'ID')->sortable();
        $grid->column('project.name', '项目名')->label();
        $grid->column('name', '模块名');
        $grid->enable_alarm('告警策略')->using(Module::$label_enable_alarm);
        $grid->column('user.name', '负责人');
        $grid->column('backup_uids', '备选负责人')->display(function ($backup_uids) {
            return implode(',', $this->getUserList(explode(',', $backup_uids)));
        });
        $grid->create_time('创建时间')->sortable();

        $grid->actions(function ($actions) {
            $actions->disableView();
        });

        $grid->filter(function ($filter) {
            $filter->disableIdFilter();// 去掉默认的id过滤器
            $filter->column(1/2, function ($filter) {
                $filter->equal('id', '模块名')->select(Module::getList()->pluck('name', 'id')->toArray());
                $filter->equal('project_id', '项目名')->select(Project::getList()->pluck('name', 'id')->toArray());
            });
            $filter->column(1/2, function ($filter) {
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
            $form->radio('enable_alarm', '告警策略')->options(Module::$label_enable_alarm)->default(Module::ALARM_DISABLE);
            $form->checkbox('alarm_types', '告警方式')->options(Module::$label_alarm_types);
            $form->number('alarm_per_minute', '告警间隔时间(分钟)')->default(10);
            $form->number('success_rate', '成功率阀值(0-100)')->default(0);
            $form->number('request_wave_rate', '调用量波动阀值(0-100)')->default(0);
            $form->number('request_total_rate', '调用量报警阀值(0-100)')->default(0);
            $form->number('avg_time_rate', '平均耗时报警阀值(ms),0表示不开启')->default(0);
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
