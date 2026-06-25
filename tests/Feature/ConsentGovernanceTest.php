<?php

declare(strict_types=1);

use App\Models\Tenant;
use App\Models\VoiceModel;
use App\Services\AI\Contracts\TtsClient;
use App\Services\AI\TtsManager;
use App\Services\Voice\ConsentService;
use App\Services\Voice\Exceptions\ConsentException;
use App\Services\Voice\VoiceModelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

/** Remplace TtsManager par un mock et renvoie le client TTS mocké. */
function bindFakeTts(): TtsClient
{
    $client = Mockery::mock(TtsClient::class);

    $manager = Mockery::mock(TtsManager::class);
    $manager->shouldReceive('default')->andReturn($client);
    $manager->shouldReceive('make')->andReturn($client);
    $manager->shouldReceive('requiresConsentForCloning')->andReturn(true);
    app()->instance(TtsManager::class, $manager);

    return $client;
}

it('liste les consentements d\'un tenant avec leurs modèles vocaux', function () {
    $tenant = Tenant::factory()->create();
    $consent = app(ConsentService::class)->grant($tenant, ['person_name' => 'DG', 'purpose' => 'Narration']);
    $consent->voiceModels()->create(['provider' => 'elevenlabs', 'external_voice_id' => 'vid_9', 'status' => 'active']);

    $this->getJson("/api/tenants/{$tenant->id}/voice-consents")
        ->assertOk()
        ->assertJsonPath('data.0.person_name', 'DG')
        ->assertJsonPath('data.0.voice_models.0.provider', 'elevenlabs');
});

it('enregistre un consentement (finalité + durée) via l\'API', function () {
    $tenant = Tenant::factory()->create();

    $this->postJson("/api/tenants/{$tenant->id}/voice-consents", [
        'person_name' => 'Mme la DG',
        'purpose' => 'Narration du BP au séminaire',
        'retention_until' => now()->addYear()->toDateString(),
    ])->assertCreated()
        ->assertJsonPath('data.active', true)
        ->assertJsonPath('data.purpose', 'Narration du BP au séminaire')
        ->assertJsonPath('data.legal_basis', 'consentement_ecrit');
});

it('refuse le clonage sans consentement actif', function () {
    $client = bindFakeTts();
    $client->shouldReceive('cloneVoice')->never();

    $tenant = Tenant::factory()->create();
    $consent = app(ConsentService::class)->grant($tenant, ['person_name' => 'DG', 'purpose' => 'X']);
    app(ConsentService::class)->revoke($consent);

    $this->postJson("/api/voice-consents/{$consent->id}/voice-model", [
        'samples' => [UploadedFile::fake()->create('s.wav', 10, 'audio/wav')],
    ])->assertStatus(403);

    expect(VoiceModel::count())->toBe(0);
});

it('clone une voix sous consentement actif (modèle isolé, lié au consentement)', function () {
    $client = bindFakeTts();
    $client->shouldReceive('cloneVoice')->once()->andReturn('voice_abc');
    $client->shouldReceive('provider')->andReturn('elevenlabs');

    $tenant = Tenant::factory()->create();
    $consent = app(ConsentService::class)->grant($tenant, ['person_name' => 'DG', 'purpose' => 'X']);

    $this->postJson("/api/voice-consents/{$consent->id}/voice-model", [
        'samples' => [UploadedFile::fake()->create('s.wav', 10, 'audio/wav')],
    ])->assertCreated()
        ->assertJsonPath('data.provider', 'elevenlabs')
        ->assertJsonPath('data.active', true);

    $model = VoiceModel::firstOrFail();
    expect($model->external_voice_id)->toBe('voice_abc')
        ->and($model->voice_consent_id)->toBe($consent->id);
});

it('révoque le consentement : supprime le modèle et bloque toute synthèse', function () {
    $client = bindFakeTts();
    $client->shouldReceive('cloneVoice')->andReturn('voice_xyz');
    $client->shouldReceive('provider')->andReturn('elevenlabs');
    $client->shouldReceive('deleteVoice')->once()->with('voice_xyz');

    $tenant = Tenant::factory()->create();
    $consent = app(ConsentService::class)->grant($tenant, ['person_name' => 'DG', 'purpose' => 'X']);
    $model = app(VoiceModelService::class)->cloneFromSamples($consent, [__FILE__]);

    $this->deleteJson("/api/voice-consents/{$consent->id}")->assertNoContent();

    expect($model->refresh()->status)->toBe('revoked')
        ->and($consent->refresh()->status)->toBe('revoked');

    // Toute synthèse ultérieure est interdite.
    expect(fn () => app(ConsentService::class)->assertCanUse($model->fresh()))
        ->toThrow(ConsentException::class);
});
