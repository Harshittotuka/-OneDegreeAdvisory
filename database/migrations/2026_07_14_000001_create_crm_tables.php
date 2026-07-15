<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_users', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->string('phone', 15)->unique();
            $table->string('role', 30)->default('counsellor')->index();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('crm_users')->nullOnDelete();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
        });

        Schema::create('crm_otp_codes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('crm_user_id')->constrained('crm_users')->cascadeOnDelete();
            $table->string('code_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at')->index();
            $table->timestamp('used_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();
        });

        Schema::create('crm_leads', function (Blueprint $table): void {
            $table->id();
            $table->string('lead_number', 20)->unique();
            $table->string('name', 150);
            $table->string('phone', 20)->index();
            $table->string('email', 190)->nullable()->index();
            $table->string('city', 120)->nullable();
            $table->string('course_interest', 180)->nullable();
            $table->string('country_interest', 120)->nullable();
            $table->string('category', 50)->nullable()->index();
            $table->string('priority', 30)->default('medium')->index();
            $table->string('source', 100)->nullable()->index();
            $table->string('status', 50)->default('new')->index();
            $table->foreignId('assigned_to')->nullable()->constrained('crm_users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('crm_users')->nullOnDelete();
            $table->timestamp('follow_up_at')->nullable()->index();
            $table->timestamp('follow_up_completed_at')->nullable();
            $table->timestamp('last_contacted_at')->nullable();
            $table->json('tags')->nullable();
            $table->json('profile')->nullable();
            $table->boolean('is_student')->default(false)->index();
            $table->string('student_stage', 60)->nullable()->index();
            $table->string('student_category', 60)->nullable();
            $table->unsignedBigInteger('enrollment_amount')->nullable();
            $table->date('enrollment_date')->nullable();
            $table->string('payment_reference', 150)->nullable();
            $table->text('conversion_remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('crm_lead_activities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('crm_lead_id')->constrained('crm_leads')->cascadeOnDelete();
            $table->foreignId('crm_user_id')->nullable()->constrained('crm_users')->nullOnDelete();
            $table->string('type', 40)->index();
            $table->text('body');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_lead_activities');
        Schema::dropIfExists('crm_leads');
        Schema::dropIfExists('crm_otp_codes');
        Schema::dropIfExists('crm_users');
    }
};
