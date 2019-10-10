<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterAdminUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('admin_users', function (Blueprint $table) {
            $table->string('mobile', 20)->nullable()->comment('电话');
            $table->string('email', 64)->nullable()->comment('邮箱');
            $table->string('open_id', 32)->nullable()->comment('微信open_id');
            $table->string('qy_wechat_uid', 64)->nullable()->comment('企业微信用户id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('admin_users', function (Blueprint $table) {
            //
        });
    }
}
