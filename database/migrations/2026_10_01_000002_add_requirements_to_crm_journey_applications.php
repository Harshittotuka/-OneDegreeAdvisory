<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What each university actually asks for, kept beside the checklist that
 * works through it: the tests it wants, the documents it wants, any entry
 * requirement worth writing down, and the date its application closes.
 *
 * The 22-activity checklist records progress; these four record the brief.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_journey_applications', function (Blueprint $table): void {
            $table->string('tests_required', 190)->nullable()->after('fit');
            $table->string('documents_required', 300)->nullable()->after('tests_required');
            $table->text('requirements')->nullable()->after('documents_required');
            $table->date('deadline')->nullable()->after('requirements');
        });
    }

    public function down(): void
    {
        Schema::table('crm_journey_applications', function (Blueprint $table): void {
            $table->dropColumn(['tests_required', 'documents_required', 'requirements', 'deadline']);
        });
    }
};
