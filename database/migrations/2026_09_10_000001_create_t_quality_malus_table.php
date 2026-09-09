<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTQualityMalusTable extends Migration
{
    public function up()
    {
        Schema::create('t_quality_malus', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('uid', 120);
            $table->decimal('quality_score_snapshot', 5, 1)->nullable();
            $table->integer('valore')->unsigned();
            $table->string('motivazione', 255);
            $table->string('assigned_by', 120)->nullable();
            $table->boolean('email_sent')->default(false);
            $table->timestamp('email_sent_at')->nullable();
            $table->timestamps();

            $table->index('uid');
        });
    }

    public function down()
    {
        Schema::dropIfExists('t_quality_malus');
    }
}
