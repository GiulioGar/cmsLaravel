<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFrozenSnapshotToTPanelControl extends Migration
{
    public function up()
    {
        Schema::table('t_panel_control', function (Blueprint $table) {
            $table->timestamp('frozen_at')->nullable()->after('panel_enabled_changed_at');
            $table->unsignedInteger('frozen_abilitati')->nullable()->after('frozen_at');
            $table->unsignedInteger('frozen_contatti_interactive')->nullable()->after('frozen_abilitati');
            $table->unsignedInteger('frozen_complete_interactive')->nullable()->after('frozen_contatti_interactive');
        });
    }

    public function down()
    {
        Schema::table('t_panel_control', function (Blueprint $table) {
            $table->dropColumn([
                'frozen_at',
                'frozen_abilitati',
                'frozen_contatti_interactive',
                'frozen_complete_interactive',
            ]);
        });
    }
}
