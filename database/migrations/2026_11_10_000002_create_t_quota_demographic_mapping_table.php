<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTQuotaDemographicMappingTable extends Migration
{
    public function up()
    {
        Schema::create('t_quota_demographic_mapping', function (Blueprint $table) {
            $table->id();
            $table->string('prj', 50);
            $table->string('sid', 50);
            $table->string('quota_name', 100);
            $table->string('tipo', 20);
            $table->json('filtro')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
            $table->unique(['sid', 'quota_name']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('t_quota_demographic_mapping');
    }
}
