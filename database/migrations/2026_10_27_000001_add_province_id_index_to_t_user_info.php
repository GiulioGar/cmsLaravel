<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class AddProvinceIdIndexToTUserInfo extends Migration
{
    public function up()
    {
        Schema::table('t_user_info', function (Blueprint $table) {
            $table->index('province_id', 'idx_t_user_info_province_id');
        });
    }

    public function down()
    {
        Schema::table('t_user_info', function (Blueprint $table) {
            $table->dropIndex('idx_t_user_info_province_id');
        });
    }
}
