<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $explorer_session_id
 * @property int $document_id
 * @property string $question
 * @property string $status
 * @property array<string, mixed>|null $stop_condition
 */
class Debate extends Model
{
    protected $fillable = [
        'explorer_session_id',
        'document_id',
        'question',
        'status',
        'stop_condition',
    ];

    protected function casts(): array
    {
        return [
            'stop_condition' => 'array',
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

    /** @return HasMany<DebateTurn, $this> */
    public function turns(): HasMany
    {
        return $this->hasMany(DebateTurn::class)->orderBy('turn_index');
    }
}
