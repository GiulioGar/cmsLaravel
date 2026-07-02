<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToTPanelControlQuotaTargets extends Migration
{
    public function up()
    {
        Schema::table('t_panel_control_quota_targets', function (Blueprint $table) {
            $table->string('quota_dimension', 30)->nullable()->after('quota_status_name');
            $table->string('quota_label', 100)->nullable()->after('quota_dimension');
            $table->integer('quota_status_id')->nullable()->after('quota_label');
        });
    }

    public function down()
    {
        Schema::table('t_panel_control_quota_targets', function (Blueprint $table) {
            $table->dropColumn(['quota_dimension', 'quota_label', 'quota_status_id']);
        });
    }
}
