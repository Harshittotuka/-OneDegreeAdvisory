<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_mock_interview_invites', function (Blueprint $table): void {
            $table->id();
            $table->string('token', 64)->unique();
            $table->string('recipient_name', 150);
            $table->string('recipient_email', 190)->nullable();
            $table->string('recipient_phone', 20)->nullable();
            // 15 / 20 / 39 — validated against MockInterviewQuestions::INVITE_COUNTS.
            $table->unsignedSmallInteger('question_count');
            $table->unsignedTinyInteger('max_uses')->default(3);
            $table->unsignedTinyInteger('uses_count')->default(0);
            $table->string('destination', 120)->nullable();
            $table->string('notes', 500)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('crm_users')->nullOnDelete();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });

        // One row per interview actually started through an invite. This is what
        // makes "used 2 of 3" auditable, and it carries the score back so the
        // counsellor can see how the student did without asking them.
        Schema::create('crm_mock_interview_attempts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invite_id')->constrained('crm_mock_interview_invites')->cascadeOnDelete();
            // Lets a mid-interview refresh resume the same attempt instead of
            // silently burning another one of the three.
            $table->string('session_key', 64)->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->unsignedSmallInteger('questions_planned');
            $table->unsignedSmallInteger('questions_answered')->nullable();
            $table->decimal('overall_score', 4, 2)->nullable();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_mock_interview_attempts');
        Schema::dropIfExists('crm_mock_interview_invites');
    }
};
