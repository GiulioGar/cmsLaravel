<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAgeMinToTRecruitmentReferralCosts extends Migration
{
    public function up()
    {
        Schema::table('t_recruitment_referral_costs', function (Blueprint $table) {
            $table->tinyInteger('age_min')->unsigned()->nullable()->after('cpi');
        });
    }

    public function down()
    {
        Schema::table('t_recruitment_referral_costs', function (Blueprint $table) {
            $table->dropColumn('age_min');
        });
    }
}
