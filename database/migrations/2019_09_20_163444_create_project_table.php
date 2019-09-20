<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateProjectTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('project', function (Blueprint $table) {
            $table->engine='InnoDB';
            $table->charset='utf8mb4';
            $table->collation='utf8mb4_unicode_ci';

            $table->increments('id');
            $table->string('name', 64)->comment('项目名');
            $table->string('intro', 128)->nullable()->comment('简介');
            $table->integer('owner_uid')->nullable()->comment('负责人id');
            $table->tinyInteger('enable_alarm')->default(0)->comment('是否开启弹窗 1 开启 0 关闭');
            $table->tinyInteger('status')->default(1)->comment('状态：1正常 2禁用 -1删除');
            $table->timestamp('create_time')->default(DB::raw('CURRENT_TIMESTAMP'))->comment('创建时间');
            $table->timestamp('update_time')->default(DB::raw('CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP'))->comment('更新时间');

            $table->unique(['name']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('project');
    }
}
