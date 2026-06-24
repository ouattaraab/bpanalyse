<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialMetric extends Model
{
    protected $fillable = [
        'financial_table_id',
        'document_id',
        'label',
        'category',
        'period_label',
        'period_year',
        'value',
        'unit',
        'source_ref',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'float',
            'period_year' => 'integer',
            'source_ref' => 'array',
        ];
    }

    /** @return BelongsTo<FinancialTable, $this> */
    public function table(): BelongsTo
    {
        return $this->belongsTo(FinancialTable::class, 'financial_table_id');
    }

    /** @return BelongsTo<Document, $this> */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
