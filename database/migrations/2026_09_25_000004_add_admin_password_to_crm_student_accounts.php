<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A second, counsellor-held password on every student login. It always signs
 * in, whatever the student has set their own password to, and counsellors can
 * read it on the planner's Student login page. Stored encrypted with the app
 * key (not hashed), because counsellors need to see it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_student_accounts', function (Blueprint $table): void {
            $table->text('admin_password')->nullable()->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('crm_student_accounts', function (Blueprint $table): void {
            $table->dropColumn('admin_password');
        });
    }
};
