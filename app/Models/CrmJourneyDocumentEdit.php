<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One entry in a document's edit history. See App\Support\JourneyDocuments. */
class CrmJourneyDocumentEdit extends Model
{
    protected $fillable = ['document_id', 'version', 'note', 'by_student', 'created_by'];

    protected function casts(): array
    {
        return ['by_student' => 'boolean', 'version' => 'integer'];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(CrmJourneyDocument::class, 'document_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(CrmUser::class, 'created_by');
    }
}
