<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTFornitoriPanelTable extends Migration
{
    public function up()
    {
Schema::create('t_fornitoriPanel', function (Blueprint $table) {
    $table->id();
    $table->integer('panel_code')->unsigned()->unique(); // codice panel (max 3 cifre, univoco)
    $table->string('name', 100);
    $table->string('red_3', 255)->nullable();
    $table->string('red_4', 255)->nullable();
    $table->string('red_5', 255)->nullable();
    $table->integer('complete')->default(0);
    $table->decimal('spesa', 10, 2)->default(0.00);
    $table->timestamps();
});
    }

    public function down()
    {
        Schema::dropIfExists('t_fornitoriPanel');
    }
}
