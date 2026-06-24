<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DocumentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    /** @use HasFactory<\Database\Factories\DocumentFactory> */
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'title',
        'type',
        'original_filename',
        'original_path',
        'mime',
        'size_bytes',
        'status',
        'page_count',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'status' => DocumentStatus::class,
            'meta' => 'array',
            'size_bytes' => 'integer',
            'page_count' => 'integer',
        ];
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
