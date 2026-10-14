<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One entry in a document's edit history, with the file that came with it
 * when there was one. See App\Support\JourneyDocuments.
 */
class CrmJourneyDocumentEdit extends Model
{
    protected $fillable = ['document_id', 'version', 'note', 'path', 'original_name', 'mime', 'size', 'by_student', 'created_by'];

    protected function casts(): array
    {
        return ['by_student' => 'boolean', 'version' => 'integer', 'size' => 'integer'];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(CrmJourneyDocument::class, 'document_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(CrmUser::class, 'created_by');
    }

    public function hasFile(): bool
    {
        return (string) $this->path !== '';
    }
}
