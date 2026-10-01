<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTRecruitmentActionsSyncTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('t_recruitment_actions_sync', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('last_history_id')->default(0);
            $table->timestamp('last_run_at')->nullable();
            $table->unsignedInteger('last_run_rows')->default(0);
            $table->unsignedInteger('last_run_users')->default(0);
            $table->unsignedInteger('last_run_duration_ms')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('t_recruitment_actions_sync');
    }
}
