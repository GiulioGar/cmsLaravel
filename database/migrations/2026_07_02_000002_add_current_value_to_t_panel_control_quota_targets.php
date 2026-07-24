<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCurrentValueToTPanelControlQuotaTargets extends Migration
{
    public function up()
    {
        Schema::table('t_panel_control_quota_targets', function (Blueprint $table) {
            $table->integer('current_value')->nullable()->default(0)->after('target_value');
        });
    }

    public function down()
    {
        Schema::table('t_panel_control_quota_targets', function (Blueprint $table) {
            $table->dropColumn('current_value');
        });
    }
}
