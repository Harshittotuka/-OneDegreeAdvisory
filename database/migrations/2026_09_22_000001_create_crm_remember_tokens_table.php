<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Keep me signed in" credentials for the CRM — one row per remembered device,
 * so a counsellor can stay signed in on a laptop and a phone at once and
 * signing out of one leaves the other alone. Only the hash of the cookie's
 * secret is stored, so a leaked row cannot be replayed as a login.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_remember_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('crm_user_id')->constrained('crm_users')->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at')->index();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_remember_tokens');
    }
};
