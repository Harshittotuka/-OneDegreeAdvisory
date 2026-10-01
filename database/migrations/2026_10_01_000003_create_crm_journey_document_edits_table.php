<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The edit history of one document or essay: who changed it, when, and what
 * changed. Rows are written by the planner itself (a draft saved, an essay
 * sent for review, feedback given) and by hand, when someone edits a document
 * outside the planner and wants the change recorded against it.
 *
 * `version` on the document is the number the student and counsellor talk
 * about ("version 3"); it moves only when the document's content does.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_journey_documents', function (Blueprint $table): void {
            $table->unsignedSmallInteger('version')->default(1)->after('category');
        });

        Schema::create('crm_journey_document_edits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('document_id')->constrained('crm_journey_documents')->cascadeOnDelete();
            $table->unsignedSmallInteger('version')->default(1);
            $table->string('note', 300);
            $table->boolean('by_student')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('crm_users')->nullOnDelete();
            $table->timestamps();
            $table->index(['document_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_journey_document_edits');
        Schema::table('crm_journey_documents', function (Blueprint $table): void {
            $table->dropColumn('version');
        });
    }
};
