<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTUserQualityTable extends Migration
{
    public function up()
    {
        Schema::create('t_user_quality', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('prj', 45);
            $table->string('sid', 30);
            $table->string('iid', 30);
            $table->string('uid', 120);
            $table->string('panel', 120)->nullable();
            $table->tinyInteger('quality_score')->unsigned()->nullable();
            $table->string('quality_tier', 20)->nullable()->comment('alta, accettabile, bassa');
            $table->tinyInteger('quality_risk_total')->unsigned()->nullable();
            $table->boolean('cap_applied')->default(false);
            $table->timestamp('computed_at')->nullable();
            $table->timestamps();

            $table->unique(['prj', 'sid', 'iid'], 'uq_prj_sid_iid');
            $table->index('uid');
            $table->index(['prj', 'sid']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('t_user_quality');
    }
}
