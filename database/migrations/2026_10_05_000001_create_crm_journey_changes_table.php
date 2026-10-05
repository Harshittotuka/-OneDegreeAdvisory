<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Every change made on a journey plan, by the team or the student: what was
 * touched, what happened to it, who did it and when. The planner dashboard's
 * "Recent changes" reads from here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_journey_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('crm_journey_plans')->cascadeOnDelete();
            $table->string('actor_type', 10); // team | student
            $table->foreignId('actor_id')->nullable()->constrained('crm_users')->nullOnDelete();
            $table->string('actor_name', 120);
            $table->string('section', 60);
            $table->string('subject', 190);
            $table->string('what', 300);
            // Team-only matters (the team list, the student's login) that the
            // student's own dashboard leaves out.
            $table->boolean('internal')->default(false);
            $table->timestamps();
            $table->index(['plan_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_journey_changes');
    }
};
