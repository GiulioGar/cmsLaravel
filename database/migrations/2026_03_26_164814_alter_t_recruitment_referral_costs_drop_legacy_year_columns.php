<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTRecruitmentReferralCostsDropLegacyYearColumns extends Migration
{
    public function up()
    {
        Schema::table('t_recruitment_referral_costs', function (Blueprint $table) {
            if (Schema::hasColumn('t_recruitment_referral_costs', 'year')) {
                $table->dropColumn('year');
            }

            if (Schema::hasColumn('t_recruitment_referral_costs', 'month')) {
                $table->dropColumn('month');
            }
        });
    }

    public function down()
    {
        Schema::table('t_recruitment_referral_costs', function (Blueprint $table) {
            if (!Schema::hasColumn('t_recruitment_referral_costs', 'year')) {
                $table->integer('year')->nullable()->after('referral_id');
            }

            if (!Schema::hasColumn('t_recruitment_referral_costs', 'month')) {
                $table->integer('month')->nullable()->after('year');
            }
        });
    }
}
