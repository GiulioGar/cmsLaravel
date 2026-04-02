<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTRecruitmentReferralsTable extends Migration
{
    public function up()
    {
        Schema::table('t_recruitment_referrals', function (Blueprint $table) {
            $table->unsignedInteger('legacy_id')->nullable()->after('id');
            $table->string('code', 50)->unique()->after('legacy_id');
            $table->string('title', 255)->after('code');
            $table->string('icon', 150)->nullable()->after('title');
            $table->text('source_codes')->nullable()->after('icon');
            $table->string('group_type', 30)->default('standard')->after('source_codes');
            $table->integer('sort_order')->default(0)->after('group_type');
            $table->boolean('is_active')->default(1)->after('sort_order');

            $table->index(['is_active', 'sort_order'], 'idx_recruitment_referrals_active_sort');
            $table->index('group_type', 'idx_recruitment_referrals_group_type');
        });
    }

    public function down()
    {
        Schema::table('t_recruitment_referrals', function (Blueprint $table) {
            $table->dropIndex('idx_recruitment_referrals_active_sort');
            $table->dropIndex('idx_recruitment_referrals_group_type');

            $table->dropColumn([
                'legacy_id',
                'code',
                'title',
                'icon',
                'source_codes',
                'group_type',
                'sort_order',
                'is_active',
            ]);
        });
    }
}
