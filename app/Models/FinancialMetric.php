<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $financial_table_id
 * @property int $document_id
 * @property string $label
 * @property string|null $category
 * @property string|null $period_label
 * @property int|null $period_year
 * @property float $value
 * @property string|null $unit
 * @property array<string, mixed>|null $source_ref
 */
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
