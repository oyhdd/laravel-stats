<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stats', function (Blueprint $table) {
            $table->engine='InnoDB';
            $table->charset='utf8mb4';
            $table->collation='utf8mb4_unicode_ci';

            $table->increments('id');
            $table->date('date_key')->comment('日期');
            $table->integer('time_key')->comment('统计时间间隔min');
            $table->integer('module_id')->comment('模块id');
            $table->integer('interface_id')->comment('接口id');
            $table->integer('total_count')->comment('调用次数');
            $table->integer('fail_count')->comment('失败次数');
            $table->double('total_time', 8, 2)->comment('总调用时间');
            $table->double('total_fail_time', 8, 2)->comment('总失败调用时间');
            $table->double('max_time', 8, 2)->comment('最大响应时间');
            $table->double('min_time', 8, 2)->comment('最小响应时间');
            $table->double('avg_time', 8, 2)->comment('平均响应时间');
            $table->double('avg_fail_time', 8, 2)->comment('平均失败时间');
            $table->text('total_server')->comment('服务端统计');
            $table->text('succ_server')->comment('服务端成功统计');
            $table->text('fail_server')->comment('服务端失败统计');
            $table->text('total_client')->comment('客户端统计');
            $table->text('succ_client')->comment('客户端成功统计');
            $table->text('fail_client')->comment('客户端失败统计');
            $table->text('ret_code')->comment('返回码统计');
            $table->text('cost_time')->comment('耗时统计');
            $table->text('succ_ret_code')->comment('成功返回码统计');

            $table->index(['date_key', 'module_id', 'interface_id']);
            $table->index('time_key');
            $table->index('module_id');
            $table->index('interface_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stats');
    }
}
