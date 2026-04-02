<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTRecruitmentReferralCostsTableAddPeriodAndCpi extends Migration
{
public function up()
{
    Schema::table('t_recruitment_referral_costs', function (Blueprint $table) {

        if (!Schema::hasColumn('t_recruitment_referral_costs', 'start_date')) {
            $table->date('start_date')->after('referral_id');
        }

        if (!Schema::hasColumn('t_recruitment_referral_costs', 'end_date')) {
            $table->date('end_date')->nullable()->after('start_date');
        }

        if (!Schema::hasColumn('t_recruitment_referral_costs', 'cpi')) {
            $table->decimal('cpi', 10, 4)->default(0)->after('end_date');
        }

        if (!Schema::hasColumn('t_recruitment_referral_costs', 'is_active')) {
            $table->boolean('is_active')->default(1)->after('cpi');
        }

    });
}

    public function down()
    {
        Schema::table('t_recruitment_referral_costs', function (Blueprint $table) {
            $table->dropIndex('idx_recruitment_costs_referral_start');
            $table->dropIndex('idx_recruitment_costs_period_active');

            $table->dropColumn([
                'referral_id',
                'start_date',
                'end_date',
                'cpi',
                'is_active',
            ]);
        });
    }
}
