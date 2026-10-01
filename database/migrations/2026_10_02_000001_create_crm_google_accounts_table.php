<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * One Google account per CRM user, connected so the planner can create real
 * Google Meet rooms in it. Tokens are stored encrypted with the app key.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_google_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crm_user_id')->unique()->constrained('crm_users')->cascadeOnDelete();
            $table->string('google_email', 190)->nullable();
            $table->text('refresh_token');
            $table->text('access_token')->nullable();
            $table->timestamp('access_token_expires_at')->nullable();
            $table->text('scopes')->nullable();
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('last_refreshed_at')->nullable();
            // Set when Google refuses the refresh token: the counsellor has to
            // connect again. Cleared on the next successful connection.
            $table->timestamp('needs_reconnect_at')->nullable();
            $table->string('last_error', 300)->nullable();
            // Rooms made for a meeting dialog and not yet saved onto a meeting.
            $table->json('pending_rooms')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_google_accounts');
    }
};
