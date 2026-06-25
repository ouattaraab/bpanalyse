<?php

declare(strict_types=1);

namespace App\Services\Voice;

use App\Models\Tenant;
use App\Models\VoiceConsent;
use App\Models\VoiceModel;
use App\Services\AI\TtsManager;
use App\Services\Voice\Exceptions\ConsentException;

/**
 * Gouvernance du consentement au clonage vocal (Epic 6).
 * Garde-fou central : aucune opération vocale clonée sans consentement valide.
 */
final class ConsentService
{
    public function __construct(private readonly TtsManager $tts)
    {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function grant(Tenant $tenant, array $data): VoiceConsent
    {
        return VoiceConsent::create([
            'tenant_id' => $tenant->id,
            'person_name' => $data['person_name'],
            'purpose' => $data['purpose'],
            'legal_basis' => $data['legal_basis'] ?? 'consentement_ecrit',
            'signed_document_path' => $data['signed_document_path'] ?? null,
            'retention_until' => $data['retention_until'] ?? null,
            'granted_at' => now(),
            'status' => 'active',
        ]);
    }

    public function revoke(VoiceConsent $consent): void
    {
        $consent->update(['status' => 'revoked', 'revoked_at' => now()]);
    }

    /** @throws ConsentException */
    public function assertActive(VoiceConsent $consent): void
    {
        if (! $consent->isActive()) {
            throw new ConsentException('Consentement absent, expiré ou révoqué : opération vocale interdite.');
        }
    }

    /**
     * Vérifie qu'un modèle vocal peut être utilisé pour la synthèse clonée.
     *
     * @throws ConsentException
     */
    public function assertCanUse(VoiceModel $model): void
    {
        if (! $this->tts->requiresConsentForCloning()) {
            return;
        }

        if (! $model->isActive()) {
            throw new ConsentException('Modèle vocal révoqué : synthèse interdite.');
        }

        $this->assertActive($model->consent);
    }
}
