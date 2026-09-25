<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The student journey planner — ODA's Overseas Admission Journey Planner
 * workbook, one per enrolled student — and the student's own sign-in to it.
 *
 * A plan holds the one-time Core Journey; each university or programme the
 * student applies to is its own row with the standard application checklist.
 * Activity state lives in JSON keyed by the activity keys in
 * App\Support\JourneyPlanner, which also holds the reference text, so a row
 * stores only what the counsellor or student actually set.
 *
 * The student account is created by the counsellor when they start the
 * planner: the student signs in at /student with their email and a password.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_journey_plans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('crm_lead_id')->unique()->constrained('crm_leads')->cascadeOnDelete();
            $table->string('level', 40)->nullable();
            $table->string('intake', 60)->nullable();
            $table->string('focus', 150)->nullable();
            $table->json('core');
            $table->foreignId('created_by')->nullable()->constrained('crm_users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('crm_journey_applications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('plan_id')->constrained('crm_journey_plans')->cascadeOnDelete();
            $table->unsignedSmallInteger('position')->default(0);
            $table->string('university', 150);
            $table->string('country', 80)->nullable();
            $table->string('program', 190)->nullable();
            $table->string('fit', 10)->nullable();
            $table->string('offer_type', 20)->nullable();
            $table->json('activities');
            $table->timestamps();
        });

        Schema::create('crm_student_accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('crm_lead_id')->unique()->constrained('crm_leads')->cascadeOnDelete();
            $table->string('email', 190)->unique();
            $table->string('password');
            // Set whenever the counsellor issues a password, so the student
            // replaces the one they were sent with their own on first sign-in.
            $table->boolean('must_change_password')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->timestamp('password_changed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('crm_users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_student_accounts');
        Schema::dropIfExists('crm_journey_applications');
        Schema::dropIfExists('crm_journey_plans');
    }
};
