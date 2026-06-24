<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DebateTurn extends Model
{
    protected $fillable = [
        'debate_id',
        'turn_index',
        'persona',
        'persona_name',
        'content',
        'sources',
        'verified_figures',
    ];

    protected function casts(): array
    {
        return [
            'turn_index' => 'integer',
            'sources' => 'array',
            'verified_figures' => 'array',
        ];
    }

    /** @return BelongsTo<Debate, $this> */
    public function debate(): BelongsTo
    {
        return $this->belongsTo(Debate::class);
    }
}
