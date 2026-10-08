<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTUserNotesTable extends Migration
{
    public function up()
    {
        Schema::create('t_user_notes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('uid', 20);
            $table->text('nota');
            $table->string('autore', 120)->nullable();
            $table->timestamps();

            $table->index('uid');
        });
    }

    public function down()
    {
        Schema::dropIfExists('t_user_notes');
    }
}
