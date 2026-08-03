<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Split the single "counselling and shortlisting" answer into the two things
     * it always stood for, so each can be recorded, shown and filtered on its own.
     *
     * Both stay nullable for the same reason the combined column was: "not
     * recorded yet" is a real third state, and defaulting to "no" would have the
     * CRM assert something nobody has checked.
     *
     * Backfill: the old answer covered both, so it seeds both. Where nobody had
     * recorded anything, counselling falls back to the rule the leads table used
     * to derive its "Counselling Done" tick from — those leads keep the tick they
     * already showed instead of resetting to blank.
     */
    public function up(): void
    {
        Schema::table('crm_leads', function (Blueprint $table): void {
            $table->string('counselling', 3)->nullable()->after('intake');
            $table->string('shortlisting', 3)->nullable()->after('counselling');
        });

        DB::table('crm_leads')->update([
            'counselling' => DB::raw('counselling_shortlisting'),
            'shortlisting' => DB::raw('counselling_shortlisting'),
        ]);

        DB::table('crm_leads')
            ->whereNull('counselling')
            ->where(function (QueryBuilder $query): void {
                $query->whereNotNull('follow_up_completed_at')
                    ->orWhere('is_student', true)
                    ->orWhereIn('status', ['interested', 'converted']);
            })
            ->update(['counselling' => 'yes']);

        Schema::table('crm_leads', function (Blueprint $table): void {
            $table->dropColumn('counselling_shortlisting');
        });
    }

    public function down(): void
    {
        Schema::table('crm_leads', function (Blueprint $table): void {
            $table->string('counselling_shortlisting', 3)->nullable()->after('intake');
        });

        // Shortlisting is the answer the combined column used to display, so it
        // is the one that survives the merge back.
        DB::table('crm_leads')->update(['counselling_shortlisting' => DB::raw('shortlisting')]);

        Schema::table('crm_leads', function (Blueprint $table): void {
            $table->dropColumn(['counselling', 'shortlisting']);
        });
    }
};
