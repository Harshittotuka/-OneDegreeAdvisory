<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Whether counselling and shortlisting have been done for a lead.
     *
     * Nullable on purpose: "not recorded yet" is a real third state, and it is
     * the one every existing lead is in. Defaulting to "no" would have the CRM
     * assert something nobody has checked.
     */
    public function up(): void
    {
        Schema::table('crm_leads', function (Blueprint $table): void {
            $table->string('counselling_shortlisting', 3)->nullable()->after('intake');
        });
    }

    public function down(): void
    {
        Schema::table('crm_leads', function (Blueprint $table): void {
            $table->dropColumn('counselling_shortlisting');
        });
    }
};
