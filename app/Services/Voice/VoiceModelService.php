<?php

declare(strict_types=1);

namespace App\Services\Voice;

use App\Models\VoiceConsent;
use App\Models\VoiceModel;
use App\Services\AI\TtsManager;
use Throwable;

/**
 * Cycle de vie du modèle vocal cloné : création (uniquement sous consentement
 * valide) et révocation (= suppression chez le provider + tombstone local).
 */
final class VoiceModelService
{
    public function __construct(
        private readonly ConsentService $consent,
        private readonly TtsManager $tts,
    ) {
    }

    /**
     * @param  array<int, string>  $samplePaths
     */
    public function cloneFromSamples(VoiceConsent $consent, array $samplePaths): VoiceModel
    {
        // Garde-fou : pas de clonage sans consentement valide.
        $this->consent->assertActive($consent);

        $client = $this->tts->default();
        $externalVoiceId = $client->cloneVoice($samplePaths, (string) $consent->id);

        return $consent->voiceModels()->create([
            'provider' => $client->provider(),
            'external_voice_id' => $externalVoiceId,
            'status' => 'active',
        ]);
    }

    /** Révocation d'un modèle : suppression chez le provider puis marquage local. */
    public function revoke(VoiceModel $model): void
    {
        try {
            $this->tts->make($model->provider)->deleteVoice($model->external_voice_id);
        } catch (Throwable) {
            // best effort : on marque révoqué localement même si l'appel provider échoue.
        }

        $model->update(['status' => 'revoked', 'revoked_at' => now()]);
    }

    /** Révoque tous les modèles actifs d'un consentement (cascade de révocation). */
    public function revokeForConsent(VoiceConsent $consent): void
    {
        foreach ($consent->voiceModels()->where('status', 'active')->get() as $model) {
            $this->revoke($model);
        }
    }
}
