<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ChunkType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Chunk extends Model
{
    /** @use HasFactory<\Database\Factories\ChunkFactory> */
    use HasFactory;

    protected $fillable = [
        'document_id',
        'document_slide_id',
        'section',
        'type',
        'content',
        'caption',
        'metadata',
    ];

    // La colonne pgvector `embedding` est hors $fillable : peuplée à part (story 1.4).

    protected function casts(): array
    {
        return [
            'type' => ChunkType::class,
            'metadata' => 'array',
        ];
    }

    /** @return BelongsTo<Document, $this> */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /** @return BelongsTo<DocumentSlide, $this> */
    public function slide(): BelongsTo
    {
        return $this->belongsTo(DocumentSlide::class, 'document_slide_id');
    }
}
