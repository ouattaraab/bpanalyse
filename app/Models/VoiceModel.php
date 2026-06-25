<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoiceModel extends Model
{
    protected $fillable = [
        'voice_consent_id',
        'provider',
        'external_voice_id',
        'status',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'revoked_at' => 'datetime',
        ];
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->revoked_at === null;
    }

    /** @return BelongsTo<VoiceConsent, $this> */
    public function consent(): BelongsTo
    {
        return $this->belongsTo(VoiceConsent::class, 'voice_consent_id');
    }
}
