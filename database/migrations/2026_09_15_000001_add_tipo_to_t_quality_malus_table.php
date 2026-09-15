<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTipoToTQualityMalusTable extends Migration
{
    public function up()
    {
        Schema::table('t_quality_malus', function (Blueprint $table) {
            $table->string('tipo', 20)->default('qualita')->after('motivazione');
        });
    }

    public function down()
    {
        Schema::table('t_quality_malus', function (Blueprint $table) {
            $table->dropColumn('tipo');
        });
    }
}
