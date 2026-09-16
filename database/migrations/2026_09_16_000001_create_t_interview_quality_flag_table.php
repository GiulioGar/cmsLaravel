<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTInterviewQualityFlagTable extends Migration
{
    public function up()
    {
        Schema::create('t_interview_quality_flag', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('prj', 50);
            $table->string('sid', 50);
            $table->string('iid', 30);
            $table->string('uid', 120);
            $table->integer('points_revoked')->unsigned();
            $table->string('flagged_by', 120)->nullable();
            $table->dateTime('flagged_at');
            $table->string('unflagged_by', 120)->nullable();
            $table->dateTime('unflagged_at')->nullable();
            $table->boolean('is_active')->default(true);

            $table->unique(['prj', 'sid', 'iid']);
            $table->index('uid');
        });
    }

    public function down()
    {
        Schema::dropIfExists('t_interview_quality_flag');
    }
}
