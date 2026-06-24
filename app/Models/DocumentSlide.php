<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentSlide extends Model
{
    /** @use HasFactory<\Database\Factories\DocumentSlideFactory> */
    use HasFactory;

    protected $fillable = [
        'document_id',
        'slide_index',
        'title',
        'section',
        'image_path',
        'raw_markdown',
    ];

    protected function casts(): array
    {
        return [
            'slide_index' => 'integer',
        ];
    }

    /** @return BelongsTo<Document, $this> */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
