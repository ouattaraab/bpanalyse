<?php

declare(strict_types=1);

namespace App\Services\AI\Contracts;

/**
 * Text-to-speech, avec support de voix clonée.
 * Provider : ElevenLabs (cloud) ou XTTS/F5-TTS (souverain).
 *
 * GOUVERNANCE : le clonage vocal traite des données biométriques.
 * Aucun usage d'une voix clonée sans consentement écrit, limité et révocable
 * (Loi 2013-450 / ARTCI). La révocation supprime le modèle vocal.
 */
interface TtsClient
{
    /**
     * Synthétise du texte en audio.
     *
     * @param  string|null  $voiceId  identifiant de voix (clonée ou standard)
     * @param  array<string, mixed>  $options
     * @return string chemin du fichier audio généré
     */
    public function synthesize(string $text, ?string $voiceId = null, array $options = []): string;

    /**
     * Clone une voix à partir d'échantillons. À n'appeler qu'après vérification
     * d'un consentement valide (voir App\Services\Voice\ConsentService).
     *
     * @param  array<int, string>  $samplePaths
     * @param  string  $consentReference  référence du consentement signé
     * @return string voiceId du modèle créé (stocké isolé, lié au consentement)
     */
    public function cloneVoice(array $samplePaths, string $consentReference): string;

    /**
     * Supprime un modèle vocal chez le provider. Appelé lors de la révocation
     * du consentement (la révocation supprime le modèle vocal).
     */
    public function deleteVoice(string $voiceId): void;

    public function provider(): string;
}
