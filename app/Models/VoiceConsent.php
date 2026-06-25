<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VoiceConsent extends Model
{
    protected $fillable = [
        'tenant_id',
        'person_name',
        'purpose',
        'legal_basis',
        'signed_document_path',
        'granted_at',
        'retention_until',
        'revoked_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'granted_at' => 'datetime',
            'revoked_at' => 'datetime',
            'retention_until' => 'date',
        ];
    }

    public function isActive(): bool
    {
        if ($this->status !== 'active' || $this->revoked_at !== null) {
            return false;
        }

        return $this->retention_until === null || ! $this->retention_until->isPast();
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return HasMany<VoiceModel, $this> */
    public function voiceModels(): HasMany
    {
        return $this->hasMany(VoiceModel::class);
    }
}
