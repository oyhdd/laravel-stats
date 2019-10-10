<?php

use Illuminate\Routing\Router;

Route::group([
    'prefix'        => config('admin.route.prefix')."",
    'middleware'    => config('admin.route.middleware'),
], function (Router $router) {
    //项目管理
    $router->resource('/stats/project', ProjectController::class);
    //接口管理
    $router->resource('/stats/api', ApiController::class);
    //模块管理
    $router->resource('/stats/module', ModuleController::class);
    //模调统计
    $router->get('/stats/index', 'StatsController@index');
    //模调接口详情
    $router->get('/stats/detail', 'StatsController@detail');
    //主调明细
    $router->get('/stats/client', 'StatsController@client');
    //被调明细
    $router->get('/stats/server', 'StatsController@server');
    //历史数据对比
    $router->get('/stats/history', 'StatsController@history');

    $router->resource('/stats/users', UserController::class);

    $router->get('/', 'HomeController@index')->name('admin.home');
});