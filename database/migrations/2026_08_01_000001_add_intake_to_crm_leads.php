<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The intake a lead is aiming for, typed by the counsellor.
     *
     * Deliberately free text rather than a fixed list: intakes are named
     * differently per destination and institution ("Sep 2026", "Fall 2026",
     * "January 2027 intake", "Spring '27"), and a closed list would go stale
     * every year and force counsellors to record the wrong one.
     */
    public function up(): void
    {
        Schema::table('crm_leads', function (Blueprint $table): void {
            $table->string('intake', 60)->nullable()->after('backlogs');
        });
    }

    public function down(): void
    {
        Schema::table('crm_leads', function (Blueprint $table): void {
            $table->dropColumn('intake');
        });
    }
};
