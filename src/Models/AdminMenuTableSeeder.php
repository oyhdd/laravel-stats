<?php

namespace Oyhdd\StatsCenter\Models;

use Illuminate\Database\Seeder;
use Encore\Admin\Auth\Database\Menu;

class AdminMenuTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * php artisan db:seed --class='Oyhdd\StatsCenter\Models\AdminMenuTableSeeder'
     *
     * @return void
     */
    public function run()
    {
        // 添加模调系统菜单
        Menu::insert([
            [
                'parent_id' => 0,
                'order' => 1,
                'title' => '模调系统',
                'icon' => 'fa-modx',
                'uri' => '/stats/index',
                'permission' => 'auth.login',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],[
                'parent_id' => 0,
                'order' => 1,
                'title' => '接口管理',
                'icon' => 'fa-bars',
                'uri' => '/stats/api',
                'permission' => 'auth.login',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],[
                'parent_id' => 0,
                'order' => 1,
                'title' => '模块管理',
                'icon' => 'fa-cubes',
                'uri' => '/stats/module',
                'permission' => 'auth.login',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],[
                'parent_id' => 0,
                'order' => 1,
                'title' => '项目管理',
                'icon' => 'fa-sitemap',
                'uri' => '/stats/project',
                'permission' => 'auth.login',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],[
                'parent_id' => 0,
                'order' => 1,
                'title' => '用户管理',
                'icon' => 'fa-users',
                'uri' => '/stats/users',
                'permission' => 'auth.login',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ]);
    }
}
