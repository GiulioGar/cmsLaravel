<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class MakePanelControlIdNullableInQuotaTargets extends Migration
{
    public function up()
    {
        DB::statement('ALTER TABLE t_panel_control_quota_targets MODIFY panel_control_id INT UNSIGNED NULL');
    }

    public function down()
    {
        DB::statement('ALTER TABLE t_panel_control_quota_targets MODIFY panel_control_id INT UNSIGNED NOT NULL');
    }
}
