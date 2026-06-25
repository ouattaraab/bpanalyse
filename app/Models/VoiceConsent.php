<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $person_name
 * @property string $purpose
 * @property string $legal_basis
 * @property string|null $signed_document_path
 * @property string $status
 * @property Carbon|null $granted_at
 * @property Carbon|null $retention_until
 * @property Carbon|null $revoked_at
 */
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
