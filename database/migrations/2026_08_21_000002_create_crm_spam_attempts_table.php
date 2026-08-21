<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Submissions caught by the honeypot field on a public lead form: real
 * processing (lead capture, mail) is skipped entirely, but the attempt is
 * logged here — IP, user agent, and whatever was posted — so repeat bot
 * traffic is visible in the CRM instead of just silently disappearing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_spam_attempts', function (Blueprint $table): void {
            $table->id();
            $table->string('source', 60)->index();
            $table->string('ip_address', 45)->nullable()->index();
            $table->string('user_agent', 255)->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_spam_attempts');
    }
};
