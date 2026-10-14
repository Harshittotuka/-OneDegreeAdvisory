<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A file on an entry in a document's edit history: the new copy of an
 * uploaded file (which then becomes the document's current version, the
 * earlier copies staying downloadable from their own entries), or a file
 * someone attached while noting a change to an essay. Stored like the
 * documents themselves: on the private disk, served only to people signed in
 * to the plan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_journey_document_edits', function (Blueprint $table): void {
            $table->string('path')->nullable()->after('note');
            $table->string('original_name', 190)->nullable()->after('path');
            $table->string('mime', 120)->nullable()->after('original_name');
            $table->unsignedBigInteger('size')->nullable()->after('mime');
        });
    }

    public function down(): void
    {
        Schema::table('crm_journey_document_edits', function (Blueprint $table): void {
            $table->dropColumn(['path', 'original_name', 'mime', 'size']);
        });
    }
};
