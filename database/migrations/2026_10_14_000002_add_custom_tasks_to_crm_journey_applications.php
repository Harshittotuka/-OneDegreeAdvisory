<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tasks and events a counsellor adds to one university's checklist on top of
 * ODA's standard 22 — a campus visit, an info session. Each entry is the
 * task's definition (group, name, text); its status, dates and notes live in
 * `activities` beside the standard ones, as added core tasks do on the plan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_journey_applications', function (Blueprint $table): void {
            $table->json('custom_tasks')->nullable()->after('activities');
        });
    }

    public function down(): void
    {
        Schema::table('crm_journey_applications', function (Blueprint $table): void {
            $table->dropColumn('custom_tasks');
        });
    }
};
