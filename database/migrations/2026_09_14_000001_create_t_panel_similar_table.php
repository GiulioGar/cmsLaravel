<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTPanelSimilarTable extends Migration
{
    public function up()
    {
        Schema::create('t_panel_similar', function (Blueprint $table) {
            $table->id();
            $table->string('prj', 50);
            $table->string('sid', 50);
            $table->string('uid', 20);
            $table->text('similar_to'); // UIDs separati da punto e virgola
            $table->dateTime('flagged_at')->useCurrent();

            $table->index(['prj', 'sid']);
            $table->index('uid');
        });
    }

    public function down()
    {
        Schema::dropIfExists('t_panel_similar');
    }
}
