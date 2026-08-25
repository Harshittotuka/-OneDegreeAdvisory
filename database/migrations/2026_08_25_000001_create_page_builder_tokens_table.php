<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Expiring access tokens for the Page Builder's machine interface (the MCP
 * connector used from a claude.ai Project, and the REST API).
 *
 * Only the SHA-256 hash is stored, so a leaked database row cannot be replayed
 * as a credential and the plaintext is unrecoverable after it is shown once.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_builder_tokens', function (Blueprint $table): void {
            $table->id();
            $table->string('label', 120);
            // sha256 hex — unique so a lookup is an exact-match index hit.
            $table->string('token_hash', 64)->unique();
            // First 8 chars of the plaintext, shown in the UI so an admin can
            // tell two active tokens apart without seeing either in full.
            $table->string('hint', 16);
            $table->timestamp('expires_at')->index();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->unsignedInteger('use_count')->default(0);
            $table->string('created_by', 60)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_builder_tokens');
    }
};
