<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stretches of days blocked out on a student's calendar in one go — exams
 * from 1 to 15 March, a family holiday — each {key, what, from, to}. Small
 * enough to live on the plan beside its deadlines and meetings.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_journey_plans', function (Blueprint $table): void {
            $table->json('blocks')->nullable()->after('meetings');
        });
    }

    public function down(): void
    {
        Schema::table('crm_journey_plans', function (Blueprint $table): void {
            $table->dropColumn('blocks');
        });
    }
};
