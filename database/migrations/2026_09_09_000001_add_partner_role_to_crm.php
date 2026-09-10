<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The Partner role: an outside referral partner who signs in to watch the
     * students they sent us, and sees nothing else in the workspace.
     *
     * Two columns carry it. crm_users.partner_access is the read / edit switch a
     * super admin sets, and stays null on a counsellor or super admin, whose
     * access comes from their role alone. crm_leads.partner_id is the "Partner
     * name" field on a lead: it names the partner the lead belongs to, and is
     * the only thing a partner's workspace is filtered by.
     */
    public function up(): void
    {
        Schema::table('crm_users', function (Blueprint $table): void {
            $table->string('partner_access', 10)->nullable()->after('role');
        });

        Schema::table('crm_leads', function (Blueprint $table): void {
            $table->foreignId('partner_id')->nullable()->after('assigned_to')
                ->constrained('crm_users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('crm_leads', function (Blueprint $table): void {
            $table->dropForeign(['partner_id']);
            $table->dropColumn('partner_id');
        });

        Schema::table('crm_users', function (Blueprint $table): void {
            $table->dropColumn('partner_access');
        });
    }
};
