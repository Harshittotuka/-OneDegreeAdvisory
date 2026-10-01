<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The four sections the planner grew beside its checklists, all small enough
 * to live on the plan itself rather than in tables of their own:
 *
 *   team       who is on this student's file — {key, role, name, contact, external}
 *   roles      designations the counsellor added to the standard list
 *   deadlines  university dates and ODA's own — {key, kind, what, who, date}
 *   meetings   calls and meetings — {key, title, date, time, minutes, mode, who, link, notes, done}
 *
 * Each row carries its own key so the page can address one without an id of
 * its own, the same way added tasks and stages already work.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_journey_plans', function (Blueprint $table): void {
            $table->json('team')->nullable()->after('custom_stages');
            $table->json('roles')->nullable()->after('team');
            $table->json('deadlines')->nullable()->after('roles');
            $table->json('meetings')->nullable()->after('deadlines');
        });
    }

    public function down(): void
    {
        Schema::table('crm_journey_plans', function (Blueprint $table): void {
            $table->dropColumn(['team', 'roles', 'deadlines', 'meetings']);
        });
    }
};
