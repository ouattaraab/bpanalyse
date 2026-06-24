<?php

declare(strict_types=1);

use App\Models\Document;
use App\Services\AI\Contracts\SttClient;
use App\Services\Session\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

it('transcrit une question orale et renvoie le texte', function () {
    $document = Document::factory()->create();
    $session = app(SessionService::class)->start($document);

    $stt = Mockery::mock(SttClient::class);
    $stt->shouldReceive('transcribe')->once()->andReturn([
        'text' => 'quelle est la marge en 2026',
        'words' => [],
    ]);
    app()->instance(SttClient::class, $stt);

    $audio = UploadedFile::fake()->create('question.wav', 50, 'audio/wav');

    $this->postJson("/api/sessions/{$session->uuid}/transcribe", ['audio' => $audio])
        ->assertOk()
        ->assertJsonPath('data.text', 'quelle est la marge en 2026');
});

it('refuse un fichier non audio', function () {
    $document = Document::factory()->create();
    $session = app(SessionService::class)->start($document);

    $file = UploadedFile::fake()->create('notes.txt', 10, 'text/plain');

    $this->postJson("/api/sessions/{$session->uuid}/transcribe", ['audio' => $file])
        ->assertStatus(422)
        ->assertJsonValidationErrorFor('audio');
});
