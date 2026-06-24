<?php

declare(strict_types=1);

namespace App\Services\AI\Contracts;

/**
 * Speech-to-text. Provider concret : Deepgram (cloud) ou faster-whisper (souverain).
 */
interface SttClient
{
    /**
     * Transcrit un fichier ou flux audio en texte (français par défaut).
     *
     * @param  string  $audioPath  chemin du fichier audio
     * @param  array<string, mixed>  $options  ex: ['language' => 'fr']
     * @return array{text:string, words?:array<int, array{word:string, start:float, end:float}>}
     */
    public function transcribe(string $audioPath, array $options = []): array;

    public function provider(): string;
}
