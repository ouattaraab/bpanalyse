<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\TranscribeAudioRequest;
use App\Models\ExplorerSession;
use App\Services\AI\Contracts\SttClient;
use Illuminate\Http\JsonResponse;

/**
 * Transcription d'une question orale (story 2.2). Le texte renvoyé est affiché
 * puis réutilisé comme une question écrite (endpoint /chat).
 *
 * SttClient est résolu sur le provider par défaut (Deepgram, ou whisper en
 * mode souverain). L'audio transite par le backend ; aucune clé côté front.
 */
class TranscriptionController extends Controller
{
    public function store(TranscribeAudioRequest $request, ExplorerSession $session, SttClient $stt): JsonResponse
    {
        $result = $stt->transcribe(
            $request->file('audio')->getRealPath(),
            ['language' => 'fr'],
        );

        return response()->json([
            'data' => [
                'text' => $result['text'],
                'words' => $result['words'] ?? [],
            ],
        ]);
    }
}
