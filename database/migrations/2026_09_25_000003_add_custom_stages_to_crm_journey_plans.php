<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stages a counsellor adds to a student's journey beside ODA's seven. Each
 * entry is {key, name, timeline, after}: `after` is the stage it follows
 * (null = at the end). The stage's tasks are ordinary added tasks
 * (custom_tasks) whose phase is the stage's key.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_journey_plans', function (Blueprint $table): void {
            $table->json('custom_stages')->nullable()->after('custom_tasks');
        });
    }

    public function down(): void
    {
        Schema::table('crm_journey_plans', function (Blueprint $table): void {
            $table->dropColumn('custom_stages');
        });
    }
};
