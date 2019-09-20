<?php

/**
 * Laravel-admin - admin builder based on Laravel.
 * @author z-song <https://github.com/z-song>
 *
 * Bootstraper for Admin.
 *
 * Here you can remove builtin form field:
 * Encore\Admin\Form::forget(['map', 'editor']);
 *
 * Or extend custom form field:
 * Encore\Admin\Form::extend('php', PHPEditor::class);
 *
 * Or require js and css assets:
 * Admin::css('/packages/prettydocs/css/styles.css');
 * Admin::js('/packages/prettydocs/js/main.js');
 *
 */

use Encore\Admin\Grid;

Encore\Admin\Form::forget(['map', 'editor']);

// 全局设置表格
Grid::init(function (Grid $grid) {

    $grid->disableRowSelector();

    $grid->disableColumnSelector();

    $grid->disableExport();

    $grid->paginate(15);

    // $grid->expandFilter();
});