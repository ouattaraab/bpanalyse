<?php

declare(strict_types=1);

namespace App\Services\Voice;

use App\Models\Interaction;
use App\Models\VoiceModel;
use App\Services\AI\TtsManager;

/**
 * Synthèse de la réponse d'une interaction en voix clonée (story 2.3).
 * GARDÉ par le consentement : aucune synthèse si le modèle vocal ou son
 * consentement est révoqué/expiré (ConsentService::assertCanUse).
 */
final class VoiceAnswerService
{
    public function __construct(
        private readonly ConsentService $consent,
        private readonly TtsManager $tts,
    ) {
    }

    /** @return string chemin du fichier audio généré */
    public function synthesize(Interaction $interaction, VoiceModel $model): string
    {
        $this->consent->assertCanUse($model);

        return $this->tts
            ->make($model->provider)
            ->synthesize((string) $interaction->answer, $model->external_voice_id);
    }
}
