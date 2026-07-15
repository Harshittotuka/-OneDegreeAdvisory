<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_users', function (Blueprint $table): void {
            $table->string('email', 190)->nullable()->unique()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('crm_users', function (Blueprint $table): void {
            $table->dropUnique(['email']);
            $table->dropColumn('email');
        });
    }
};
