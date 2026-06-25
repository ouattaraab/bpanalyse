<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\VoiceModelResource;
use App\Models\VoiceConsent;
use App\Models\VoiceModel;
use App\Services\Voice\Exceptions\ConsentException;
use App\Services\Voice\VoiceModelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class VoiceModelController extends Controller
{
    public function store(Request $request, VoiceConsent $consent, VoiceModelService $models): JsonResponse
    {
        $validated = $request->validate([
            'samples' => ['required', 'array', 'min:1'],
            'samples.*' => ['file', 'mimetypes:audio/wav,audio/x-wav,audio/mpeg,audio/mp4,audio/webm,audio/ogg,audio/flac'],
        ]);

        $paths = array_map(static fn ($file): string => $file->getRealPath(), $validated['samples']);

        try {
            $model = $models->cloneFromSamples($consent, $paths);
        } catch (ConsentException $e) {
            // Garde-fou gouvernance : pas de clonage sans consentement valide.
            abort(HttpResponse::HTTP_FORBIDDEN, $e->getMessage());
        }

        return VoiceModelResource::make($model)
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function destroy(VoiceModel $voiceModel, VoiceModelService $models): Response
    {
        $models->revoke($voiceModel);

        return response()->noContent();
    }
}
