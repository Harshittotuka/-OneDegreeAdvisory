<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Joins the two halves of "a partner": the CRM account they sign in with
     * (crm_users, role = partner) and the company plus tracking code their link
     * carries (crm_partner_codes).
     *
     * They were built as separate features and stayed separate records, which
     * meant a referral company was entered twice — once as an account, once as a
     * code — with nothing saying the two were the same company. One column fixes
     * that: a code now names the account it belongs to, so creating a partner is
     * a single act on a single form, and the Partner codes tab can show who signs
     * in for each link.
     *
     * Nullable, because the two halves can still exist alone: codes issued before
     * this migration name no account, and a company we only track (no login) never
     * will. Unique, because an account has at most one link — two links for one
     * partner would split their own referrals in the CRM they log into.
     *
     * nullOnDelete rather than cascade: deleting the account must not take the
     * code with it, or the leads it referred would lose their attribution.
     * CrmUserController::destroy pauses the orphaned code instead.
     */
    public function up(): void
    {
        Schema::table('crm_partner_codes', function (Blueprint $table): void {
            $table->foreignId('crm_user_id')->nullable()->after('id')
                ->constrained('crm_users')->nullOnDelete();
            $table->unique('crm_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('crm_partner_codes', function (Blueprint $table): void {
            // The foreign key leans on the unique index, so it goes first.
            $table->dropForeign(['crm_user_id']);
            $table->dropUnique(['crm_user_id']);
            $table->dropColumn('crm_user_id');
        });
    }
};
