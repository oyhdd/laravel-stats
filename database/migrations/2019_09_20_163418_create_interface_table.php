<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateInterfaceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('interface', function (Blueprint $table) {
            $table->engine='InnoDB';
            $table->charset='utf8mb4';
            $table->collation='utf8mb4_unicode_ci';

            $table->increments('id');
            $table->integer('module_id')->comment('模块id');
            $table->string('name', 128)->comment('接口名');
            $table->string('intro', 128)->nullable()->comment('简介');
            $table->integer('owner_uid')->nullable()->comment('负责人id');
            $table->string('backup_uids', 128)->nullable()->comment('备选负责人');
            $table->tinyInteger('enable_alarm')->default(0)->comment('告警策略 1 开启 0 关闭');
            $table->string('alarm_uids', 255)->nullable()->comment('告警uids');
            $table->integer('alarm_per_minute')->default(0)->comment('报警间隔时间(分钟),为0时以module中的alarm_per_minute为主');
            $table->string('alarm_types', 128)->nullable()->comment('报警类型 1 微信 2 短信 3 邮件');
            $table->tinyInteger('success_rate')->default(0)->comment('成功率阀值(0-100),0表示不开启');
            $table->integer('request_total_rate')->default(0)->comment('调用量报警阀值,0表示不开启');
            $table->tinyInteger('request_wave_rate')->default(0)->comment('调用量波动阀值(0-100),0表示不开启');
            $table->integer('avg_time_rate')->default(0)->comment('平均耗时报警阀值(ms),0表示不开启');
            $table->tinyInteger('status')->default(1)->comment('状态：1正常 2禁用 -1删除');
            $table->timestamp('create_time')->default(DB::raw('CURRENT_TIMESTAMP'))->comment('创建时间');
            $table->timestamp('update_time')->default(DB::raw('CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP'))->comment('更新时间');

            $table->unique(['name', 'module_id']);
        });

        DB::statement("ALTER TABLE interface AUTO_INCREMENT=100000");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('interface');
    }
}
