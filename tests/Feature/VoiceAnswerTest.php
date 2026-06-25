<?php

declare(strict_types=1);

use App\Models\Document;
use App\Models\Tenant;
use App\Services\AI\Contracts\TtsClient;
use App\Services\AI\TtsManager;
use App\Services\Session\SessionService;
use App\Services\Voice\ConsentService;
use App\Services\Voice\VoiceModelService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function bindVoiceTts(): TtsClient
{
    $client = Mockery::mock(TtsClient::class);

    $manager = Mockery::mock(TtsManager::class);
    $manager->shouldReceive('make')->andReturn($client);
    $manager->shouldReceive('requiresConsentForCloning')->andReturn(true);
    app()->instance(TtsManager::class, $manager);

    return $client;
}

function activeVoiceSetup(): array
{
    $tenant = Tenant::factory()->create();
    $consent = app(ConsentService::class)->grant($tenant, ['person_name' => 'DG', 'purpose' => 'Réponses']);
    $model = $consent->voiceModels()->create([
        'provider' => 'elevenlabs',
        'external_voice_id' => 'vid_1',
        'status' => 'active',
    ]);

    $document = Document::factory()->create(['tenant_id' => $tenant->id]);
    $session = app(SessionService::class)->start($document);
    $interaction = $session->interactions()->create([
        'document_id' => $document->id,
        'role' => 'assistant',
        'mode' => 'chat',
        'question' => 'q',
        'answer' => 'Réponse à restituer en voix.',
    ]);

    return [$model, $interaction];
}

it('restitue la réponse en voix clonée sous consentement valide', function () {
    $client = bindVoiceTts();
    $audio = tempnam(sys_get_temp_dir(), 'aud').'.mp3';
    file_put_contents($audio, 'OCTETS_AUDIO');
    $client->shouldReceive('synthesize')->once()->andReturn($audio);

    [$model, $interaction] = activeVoiceSetup();

    $this->post("/api/interactions/{$interaction->id}/voice", ['voice_model_id' => $model->id])
        ->assertOk()
        ->assertDownload('reponse.mp3');
});

it('refuse la synthèse en voix clonée si le modèle est révoqué', function () {
    $client = bindVoiceTts();
    $client->shouldReceive('deleteVoice');
    $client->shouldReceive('synthesize')->never();

    [$model, $interaction] = activeVoiceSetup();
    app(VoiceModelService::class)->revoke($model);

    $this->post("/api/interactions/{$interaction->id}/voice", ['voice_model_id' => $model->id])
        ->assertStatus(403);
});
