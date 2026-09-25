<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Documents and essays on a student's journey plan. One table for both:
 * a "file" row points at an upload on the private disk, an "essay" row holds
 * the text the student writes in the planner. Either can be tied to one of
 * the plan's universities. The student and the counsellor both see every row.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_journey_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('plan_id')->constrained('crm_journey_plans')->cascadeOnDelete();
            $table->foreignId('application_id')->nullable()->constrained('crm_journey_applications')->nullOnDelete();
            $table->string('kind', 10); // file | essay
            $table->string('title', 190);
            $table->string('category', 60);
            // Essays
            $table->longText('body')->nullable();
            $table->string('status', 20)->nullable(); // Draft | Submitted | Needs changes | Approved
            $table->text('feedback')->nullable();
            $table->foreignId('feedback_by')->nullable()->constrained('crm_users')->nullOnDelete();
            $table->timestamp('feedback_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            // Files
            $table->string('path')->nullable();
            $table->string('original_name', 190)->nullable();
            $table->string('mime', 120)->nullable();
            $table->unsignedInteger('size')->nullable();
            // Who added it: the student, or a counsellor.
            $table->boolean('by_student')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('crm_users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_journey_documents');
    }
};
