<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTPanelControlCostsTable extends Migration
{
    public function up()
    {
        Schema::create('t_panel_control_costs', function (Blueprint $table) {
            $table->id();
            $table->string('prj', 45);
            $table->string('sur_id', 20);
            $table->string('panel_name', 100);
            $table->decimal('cpi', 10, 2)->nullable();
            $table->decimal('costo_totale', 10, 2)->nullable();
            $table->string('referente', 100)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['prj', 'sur_id', 'panel_name']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('t_panel_control_costs');
    }
}
