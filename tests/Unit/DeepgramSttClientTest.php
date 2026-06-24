<?php

declare(strict_types=1);

use App\Services\AI\Providers\DeepgramSttClient;
use Illuminate\Support\Facades\Http;

function tempAudio(): string
{
    $path = tempnam(sys_get_temp_dir(), 'aud').'.wav';
    file_put_contents($path, 'RIFF....WAVEfmt ');

    return $path;
}

it('transcrit l\'audio via Deepgram et renvoie texte + mots', function () {
    Http::fake([
        'api.deepgram.com/*' => Http::response([
            'results' => ['channels' => [['alternatives' => [[
                'transcript' => 'quelle est la marge 2026',
                'words' => [['word' => 'quelle', 'start' => 0.0, 'end' => 0.3]],
            ]]]]],
        ], 200),
    ]);

    $audio = tempAudio();
    $result = (new DeepgramSttClient('clef-test'))->transcribe($audio);

    expect($result['text'])->toBe('quelle est la marge 2026')
        ->and($result['words'][0]['word'])->toBe('quelle')
        ->and($result['words'][0]['end'])->toBe(0.3);

    Http::assertSent(fn ($request) => str_contains($request->url(), 'language=fr')
        && $request->hasHeader('Authorization', 'Token clef-test'));

    @unlink($audio);
});

it('lève une exception si Deepgram échoue', function () {
    Http::fake(['api.deepgram.com/*' => Http::response('error', 500)]);

    $audio = tempAudio();

    expect(fn () => (new DeepgramSttClient('clef-test'))->transcribe($audio))
        ->toThrow(RuntimeException::class);

    @unlink($audio);
});

it('lève si le fichier audio est introuvable', function () {
    expect(fn () => (new DeepgramSttClient('clef-test'))->transcribe('/inexistant.wav'))
        ->toThrow(RuntimeException::class);
});
