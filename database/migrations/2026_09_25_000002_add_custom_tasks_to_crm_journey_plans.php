<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tasks a counsellor adds to a student's core journey on top of ODA's
 * standard ones. Each entry is the task's definition (stage, name, text);
 * its status, dates and notes live in `core` beside the standard tasks.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_journey_plans', function (Blueprint $table): void {
            $table->json('custom_tasks')->nullable()->after('core');
        });
    }

    public function down(): void
    {
        Schema::table('crm_journey_plans', function (Blueprint $table): void {
            $table->dropColumn('custom_tasks');
        });
    }
};
