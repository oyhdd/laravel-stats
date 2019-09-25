<?php

namespace Oyhdd\StatsCenter\Http\Controllers;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Layout\Content;
use Oyhdd\StatsCenter\Models\Project;
use Illuminate\Validation\Rule;

/**
 * 项目管理
 */
class ProjectController extends BaseController
{

    /**
     * {@inheritdoc}
     */
    protected function title()
    {
        return "项目管理";
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Project());
        $grid->model()->where('status', '>', Project::STATUS_DELETED);

        $grid->column('id', 'ID')->sortable();
        $grid->column('name', '项目名')->display(function ($name) {
            $routePrefix = config('admin.route.prefix');
            return "<a href='/{$routePrefix}/stats/module/?project_id={$this->id}'>{$name}</a>";
        });
        $grid->column('user.name', '负责人');
        $grid->enable_alarm('告警策略')->using(Project::$label_enable_alarm);
        $grid->create_time('创建时间')->sortable();

        $grid->actions(function ($actions) {
            $actions->disableView();

            $routePrefix = config('admin.route.prefix');
            $actions->prepend("<a href='/{$routePrefix}/stats/module/?project_id={$this->getKey()}'>
                &nbsp;<span class='label label-success'>模块管理</span>&nbsp;</a>");
        });

        $grid->filter(function ($filter) {
            $filter->disableIdFilter();// 去掉默认的id过滤器
            $filter->column(1/2, function ($filter) {
                $filter->equal('id', '项目名')->select(Project::getList()->pluck('name', 'id')->toArray());
            });
            $filter->column(1/2, function ($filter) {
                $filter->equal('enable_alarm', '告警策略')->select(Project::$label_enable_alarm);
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
        $form = new Form(new Project());

        $form->display('id', 'ID');
        $form->text('name', '项目名')->rules(function ($form) {
            return [
                'required',
                Rule::unique('project', 'name')->ignore($form->model()->id),
            ];
        });
        $form->textarea('intro', '简介');
        $form->select('owner_uid', '负责人')->options(Project::getUserList());
        $form->radio('enable_alarm', '告警策略')->options(Project::$label_enable_alarm)->default(Project::ALARM_DISABLE);
        // $form->select('status', '状态')->options(Project::$label_status)->default(Project::STATUS_EFFECTIVE);
        $form->display('create_time', '创建时间');
        $form->display('update_time', '更新时间');

        $form->tools(function (Form\Tools $tools) {
            $tools->disableView();
        });
        $form->footer(function ($footer) {
            $footer->disableViewCheck()->disableEditingCheck()->disableCreatingCheck();
        });
        return $form;
    }
}
