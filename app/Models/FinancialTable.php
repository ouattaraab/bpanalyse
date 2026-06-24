<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinancialTable extends Model
{
    protected $fillable = [
        'document_id',
        'document_slide_id',
        'chunk_id',
        'name',
        'caption',
        'raw_markdown',
    ];

    /** @return BelongsTo<Document, $this> */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /** @return HasMany<FinancialMetric, $this> */
    public function metrics(): HasMany
    {
        return $this->hasMany(FinancialMetric::class);
    }
}
