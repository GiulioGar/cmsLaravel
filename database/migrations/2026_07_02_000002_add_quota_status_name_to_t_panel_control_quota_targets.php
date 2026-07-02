<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddQuotaStatusNameToTPanelControlQuotaTargets extends Migration
{
    public function up()
    {
        Schema::table('t_panel_control_quota_targets', function (Blueprint $table) {
            $table->string('quota_status_name', 100)->nullable()->after('target_value');
        });
    }

    public function down()
    {
        Schema::table('t_panel_control_quota_targets', function (Blueprint $table) {
            $table->dropColumn('quota_status_name');
        });
    }
}
