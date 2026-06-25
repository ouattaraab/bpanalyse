<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $explorer_session_id
 * @property int $document_id
 * @property string $question
 * @property array<int, array{slide_id: int, narration: string, duree: int}> $script
 * @property string $status
 * @property int|null $duration_total
 */
class Presentation extends Model
{
    protected $fillable = [
        'explorer_session_id',
        'document_id',
        'question',
        'script',
        'status',
        'duration_total',
    ];

    protected function casts(): array
    {
        return [
            'script' => 'array',
            'duration_total' => 'integer',
        ];
    }

    /** @return BelongsTo<ExplorerSession, $this> */
    public function session(): BelongsTo
    {
        return $this->belongsTo(ExplorerSession::class, 'explorer_session_id');
    }

    /** @return BelongsTo<Document, $this> */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
