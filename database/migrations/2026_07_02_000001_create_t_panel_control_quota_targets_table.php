<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTPanelControlQuotaTargetsTable extends Migration
{
    public function up()
    {
        Schema::create('t_panel_control_quota_targets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('panel_control_id');
            $table->string('sur_id', 20)->nullable();
            $table->string('prj', 45)->nullable();
            $table->tinyInteger('gender')->nullable()->comment('1=M, 2=F, 3/null=entrambi');
            $table->smallInteger('age_min')->nullable();
            $table->smallInteger('age_max')->nullable();
            $table->integer('area')->nullable();
            $table->float('target_percent')->nullable();
            $table->integer('target_value')->nullable();
            $table->tinyInteger('enabled')->default(1);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('t_panel_control_quota_targets');
    }
}
