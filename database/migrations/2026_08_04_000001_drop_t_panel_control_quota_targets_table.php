<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropTPanelControlQuotaTargetsTable extends Migration
{
    public function up()
    {
        Schema::dropIfExists('t_panel_control_quota_targets');
    }

    public function down()
    {
        Schema::create('t_panel_control_quota_targets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('panel_control_id')->nullable();
            $table->string('sur_id', 20)->nullable();
            $table->string('prj', 45)->nullable();
            $table->tinyInteger('gender')->nullable();
            $table->smallInteger('age_min')->nullable();
            $table->smallInteger('age_max')->nullable();
            $table->integer('area')->nullable();
            $table->float('target_percent')->nullable();
            $table->integer('target_value')->nullable();
            $table->string('quota_status_name', 100)->nullable();
            $table->integer('current_value')->nullable()->default(0);
            $table->string('quota_dimension', 30)->nullable();
            $table->string('quota_label', 100)->nullable();
            $table->integer('quota_status_id')->nullable();
            $table->tinyInteger('enabled')->default(1);
            $table->timestamps();
        });
    }
}
