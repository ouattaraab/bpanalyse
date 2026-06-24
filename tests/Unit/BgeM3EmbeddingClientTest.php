<?php

declare(strict_types=1);

use App\Services\AI\Exceptions\EmbeddingException;
use App\Services\AI\Providers\BgeM3EmbeddingClient;

function python3OrSkipEmbed(): string
{
    $bin = trim((string) shell_exec('command -v python3'));
    if ($bin === '') {
        test()->markTestSkipped('python3 absent de cet environnement.');
    }

    return $bin;
}

it('renvoie un vecteur de dimension 1024 par texte', function () {
    $client = new BgeM3EmbeddingClient(
        python: python3OrSkipEmbed(),
        script: base_path('tests/Fixtures/embed_stub.py'),
        model: 'stub',
        dimensions: 1024,
        timeout: 30,
    );

    $vectors = $client->embed(['bonjour', 'projections financières']);

    expect($vectors)->toHaveCount(2)
        ->and($vectors[0])->toHaveCount(1024)
        ->and($client->dimensions())->toBe(1024)
        ->and($client->provider())->toBe('bge_m3');
});

it('renvoie un tableau vide sans lancer de process pour une entrée vide', function () {
    $client = new BgeM3EmbeddingClient('python3', 'inexistant.py', 'stub', 1024, 5);

    expect($client->embed([]))->toBe([]);
});

it('rejette une dimension non conforme', function () {
    $client = new BgeM3EmbeddingClient(
        python: python3OrSkipEmbed(),
        script: base_path('tests/Fixtures/embed_stub_baddim.py'),
        dimensions: 1024,
        timeout: 30,
    );

    expect(fn () => $client->embed(['x']))->toThrow(EmbeddingException::class);
});

it('lève EmbeddingException quand le process échoue', function () {
    $client = new BgeM3EmbeddingClient(
        python: python3OrSkipEmbed(),
        script: base_path('tests/Fixtures/docling_stub_fail.py'),
        dimensions: 1024,
        timeout: 30,
    );

    expect(fn () => $client->embed(['x']))->toThrow(EmbeddingException::class);
});
