<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreConsentRequest;
use App\Http\Resources\ConsentResource;
use App\Models\Tenant;
use App\Models\VoiceConsent;
use App\Services\Voice\ConsentService;
use App\Services\Voice\VoiceModelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ConsentController extends Controller
{
    public function index(Tenant $tenant): JsonResponse
    {
        $consents = $tenant->load('voiceConsents.voiceModels')->voiceConsents ?? collect();

        return response()->json(['data' => ConsentResource::collection($consents)->resolve()]);
    }

    public function store(StoreConsentRequest $request, Tenant $tenant, ConsentService $consents): JsonResponse
    {
        $signedPath = $request->hasFile('signed_document')
            ? $request->file('signed_document')->store("consents/{$tenant->id}", 'documents')
            : null;

        $consent = $consents->grant($tenant, [
            'person_name' => (string) $request->string('person_name'),
            'purpose' => (string) $request->string('purpose'),
            'legal_basis' => $request->input('legal_basis'),
            'retention_until' => $request->date('retention_until'),
            'signed_document_path' => $signedPath,
        ]);

        return ConsentResource::make($consent)
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    /** Révocation : supprime les modèles vocaux liés puis révoque le consentement. */
    public function destroy(VoiceConsent $consent, ConsentService $consents, VoiceModelService $models): Response
    {
        $models->revokeForConsent($consent);
        $consents->revoke($consent);

        return response()->noContent();
    }
}
