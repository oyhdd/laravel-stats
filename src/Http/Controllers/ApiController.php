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
        $grid->enable_alarm('告警策略')->using(Api::$label_enable_alarm);
        $grid->column('success_rate', '成功率阀值');
        $grid->column('request_total_rate', '调用量波动阀值');
        $grid->column('user.name', '负责人');
        $grid->create_time('创建时间')->sortable();

        $grid->disableCreation();
        $grid->actions(function ($actions) {
            $actions->disableView();
        });

        $grid->filter(function ($filter) {
            $filter->disableIdFilter();// 去掉默认的id过滤器
            $filter->column(1/2, function ($filter) {
                $moduleList = Module::getList()->pluck('name', 'id')->toArray();
                array_walk($moduleList, function (&$module, $module_id){
                    $module = $module_id.' : '.$module;
                });
                $interfaceList = Api::getList()->pluck('name', 'id')->toArray();
                array_walk($interfaceList, function (&$interface, $interface_id){
                    $interface = $interface_id.' : '.$interface;
                });
                $filter->equal('id', '接口名')->select($interfaceList);
                $filter->equal('module_id', '模块名')->select($moduleList);
            });
            $filter->column(1/2, function ($filter) {
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
            $form->radio('enable_alarm', '告警策略')->options(Api::$label_enable_alarm)->default(Api::ALARM_DISABLE);
            $form->checkbox('alarm_types', '告警方式')->options(Api::$label_alarm_types);
            $form->multipleSelect('alarm_uids', '告警接收方')->options(Api::getUserList());
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

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if ($this->form()->destroy($id)) {
            $data = [
                'status'  => true,
                'message' => trans('admin.delete_succeeded'),
            ];
        } else {
            $data = [
                'status'  => false,
                'message' => trans('admin.delete_failed'),
            ];
        }

        return response()->json($data);
    }
}
