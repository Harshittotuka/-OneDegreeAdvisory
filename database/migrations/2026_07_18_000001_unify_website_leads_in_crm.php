<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_leads', function (Blueprint $table): void {
            $table->string('phone', 20)->nullable()->change();
        });

        Schema::create('crm_website_submissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('crm_lead_id')->constrained('crm_leads')->cascadeOnDelete();
            $table->string('external_id', 190)->nullable()->unique();
            $table->string('source', 60)->index();
            $table->string('source_label', 120);
            $table->string('degree', 160)->nullable();
            $table->json('sections')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('submitted_at')->index();
            $table->timestamps();
        });

        Schema::table('payment_attempts', function (Blueprint $table): void {
            $table->foreignId('crm_lead_id')->nullable()->after('id')->constrained('crm_leads')->nullOnDelete();
        });

    }

    public function down(): void
    {
        Schema::table('payment_attempts', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('crm_lead_id');
        });
        Schema::dropIfExists('crm_website_submissions');
        DB::table('crm_leads')->whereNull('phone')->update(['phone' => '']);
        Schema::table('crm_leads', function (Blueprint $table): void {
            $table->string('phone', 20)->nullable(false)->change();
        });
    }
};
