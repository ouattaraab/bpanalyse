<?php

declare(strict_types=1);

use App\Services\AI\Providers\ElevenLabsTtsClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

function tempSample(): string
{
    $path = tempnam(sys_get_temp_dir(), 'spl').'.wav';
    file_put_contents($path, 'RIFF....WAVE');

    return $path;
}

it('clone une voix et renvoie le voice_id', function () {
    Http::fake(['api.elevenlabs.io/v1/voices/add' => Http::response(['voice_id' => 'vid_123'], 200)]);

    $sample = tempSample();
    $voiceId = (new ElevenLabsTtsClient('clef'))->cloneVoice([$sample], 'consent-1');

    expect($voiceId)->toBe('vid_123');
    Http::assertSent(fn ($request) => $request->hasHeader('xi-api-key', 'clef'));

    @unlink($sample);
});

it('supprime une voix (révocation chez le provider)', function () {
    Http::fake(['api.elevenlabs.io/*' => Http::response('', 200)]);

    (new ElevenLabsTtsClient('clef'))->deleteVoice('vid_123');

    Http::assertSent(fn ($request) => $request->method() === 'DELETE'
        && str_contains($request->url(), 'voices/vid_123'));
});

it('synthétise et enregistre un fichier audio', function () {
    Storage::fake('local');
    Http::fake(['api.elevenlabs.io/*' => Http::response('OCTETS_AUDIO', 200)]);

    $path = (new ElevenLabsTtsClient('clef'))->synthesize('Bonjour le board', 'vid_123');

    expect($path)->toContain('tts/')
        ->and(Storage::disk('local')->files('tts'))->toHaveCount(1);
});
