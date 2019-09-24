<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStatsSumTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stats_sum', function (Blueprint $table) {
            $table->engine='InnoDB';
            $table->charset='utf8mb4';
            $table->collation='utf8mb4_unicode_ci';

            $table->increments('id');
            $table->date('date_key')->comment('日期');
            $table->integer('module_id')->comment('模块id');
            $table->integer('interface_id')->comment('接口id');
            $table->string('interface_name', 128)->comment('接口名');
            $table->string('module_name', 64)->comment('模块名');
            $table->integer('total_count')->comment('调用次数');
            $table->integer('fail_count')->comment('失败次数');
            $table->integer('succ_count')->comment('成功次数');
            $table->string('total_time', 20)->comment('总调用时间');
            $table->string('total_fail_time', 20)->comment('总失败调用时间');
            $table->double('max_time', 8, 2)->comment('最大响应时间');
            $table->double('min_time', 8, 2)->comment('最小响应时间');
            $table->double('avg_time', 8, 2)->comment('平均响应时间');
            $table->double('succ_rate', 8, 2)->comment('平均响应时间');
            $table->double('avg_fail_time', 8, 2)->comment('平均失败时间');

            $table->index(['date_key', 'module_id', 'interface_id']);
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
        Schema::dropIfExists('stats_sum');
    }
}
