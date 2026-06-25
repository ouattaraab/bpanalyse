<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Interaction;
use App\Models\VoiceModel;
use App\Services\Voice\Exceptions\ConsentException;
use App\Services\Voice\VoiceAnswerService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restitue la réponse d'une interaction en voix clonée (story 2.3),
 * uniquement sous consentement valide.
 */
class VoiceAnswerController extends Controller
{
    public function store(Request $request, Interaction $interaction, VoiceAnswerService $voice): BinaryFileResponse
    {
        $validated = $request->validate([
            'voice_model_id' => ['required', 'integer', 'exists:voice_models,id'],
        ]);

        $model = VoiceModel::findOrFail($validated['voice_model_id']);

        try {
            $path = $voice->synthesize($interaction, $model);
        } catch (ConsentException $e) {
            abort(Response::HTTP_FORBIDDEN, $e->getMessage());
        }

        return response()
            ->download($path, 'reponse.mp3', ['Content-Type' => 'audio/mpeg'])
            ->deleteFileAfterSend();
    }
}
