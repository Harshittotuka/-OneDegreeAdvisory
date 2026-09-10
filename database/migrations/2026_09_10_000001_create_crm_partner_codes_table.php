<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Partner codes: the referral companies we hand a tracking code to.
     *
     * Distinct from the Partner ROLE added in 2026_09_09_000001. That is a CRM
     * account a referral partner signs in with; this is a company plus the code
     * that travels in a public link (/profiler?partner=CODE). A company can have
     * one without the other — most have only a code — so they are separate
     * records rather than more columns on crm_users.
     *
     * crm_leads.partner_code_id is the attribution: which code was on the URL
     * when this lead's form was submitted. It is set once, on first capture, so
     * a later submission never rewrites who the referral belongs to.
     */
    public function up(): void
    {
        Schema::create('crm_partner_codes', function (Blueprint $table): void {
            $table->id();
            $table->string('company_name', 150);
            // Stored upper-cased, which is what makes ?partner= case-insensitive
            // without a second lookup column: the resolver upper-cases the query
            // value and compares directly.
            $table->string('code', 40)->unique();
            $table->string('email', 190);
            $table->string('phone', 30)->nullable();
            $table->string('contact_name', 120)->nullable();
            $table->string('company_link', 255)->nullable();
            // Switched off rather than deleted, so the leads it already brought in
            // keep their attribution while the link stops notifying anyone.
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('crm_users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('crm_leads', function (Blueprint $table): void {
            $table->foreignId('partner_code_id')->nullable()->after('partner_id')
                ->constrained('crm_partner_codes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('crm_leads', function (Blueprint $table): void {
            $table->dropForeign(['partner_code_id']);
            $table->dropColumn('partner_code_id');
        });

        Schema::dropIfExists('crm_partner_codes');
    }
};
