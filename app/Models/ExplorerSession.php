<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ExplorerSessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExplorerSession extends Model
{
    /** @use HasFactory<ExplorerSessionFactory> */
    use HasFactory;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'document_id',
        'status',
        'started_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /** @return BelongsTo<Document, $this> */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return HasMany<Interaction, $this> */
    public function interactions(): HasMany
    {
        return $this->hasMany(Interaction::class);
    }

    /** @return HasMany<PinnedItem, $this> */
    public function pinnedItems(): HasMany
    {
        return $this->hasMany(PinnedItem::class)->latest();
    }
}
