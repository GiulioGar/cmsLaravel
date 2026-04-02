<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTRecruitmentReferralCostsTable extends Migration
{
    public function up()
    {
        Schema::table('t_recruitment_referral_costs', function (Blueprint $table) {
            $table->unsignedBigInteger('referral_id')->after('id');
            $table->unsignedSmallInteger('year')->after('referral_id');
            $table->decimal('annual_cost', 10, 2)->default(0)->after('year');
            $table->boolean('is_active')->default(1)->after('annual_cost');

            $table->index(['referral_id', 'year'], 'idx_recruitment_referral_costs_referral_year');
            $table->index(['year', 'is_active'], 'idx_recruitment_referral_costs_year_active');
        });
    }

    public function down()
    {
        Schema::table('t_recruitment_referral_costs', function (Blueprint $table) {
            $table->dropIndex('idx_recruitment_referral_costs_referral_year');
            $table->dropIndex('idx_recruitment_referral_costs_year_active');

            $table->dropColumn([
                'referral_id',
                'year',
                'annual_cost',
                'is_active',
            ]);
        });
    }
}
