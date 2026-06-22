<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Public checkout is now DIRECT (no customer OTP), so the OTP columns on
 * payment_attempts are obsolete. Drop them on any database that already ran the
 * original create migration. Guarded with hasColumn so a fresh install (whose
 * create migration no longer defines these columns) is a no-op.
 */
return new class extends Migration
{
    private array $columns = ['otp_hash', 'otp_expires_at', 'otp_verified_at', 'otp_attempts'];

    public function up(): void
    {
        Schema::table('payment_attempts', function (Blueprint $table): void {
            foreach ($this->columns as $column) {
                if (Schema::hasColumn('payment_attempts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('payment_attempts', function (Blueprint $table): void {
            if (! Schema::hasColumn('payment_attempts', 'otp_hash')) {
                $table->string('otp_hash', 64)->nullable();
            }
            if (! Schema::hasColumn('payment_attempts', 'otp_expires_at')) {
                $table->timestamp('otp_expires_at')->nullable();
            }
            if (! Schema::hasColumn('payment_attempts', 'otp_verified_at')) {
                $table->timestamp('otp_verified_at')->nullable();
            }
            if (! Schema::hasColumn('payment_attempts', 'otp_attempts')) {
                $table->unsignedTinyInteger('otp_attempts')->default(0);
            }
        });
    }
};
