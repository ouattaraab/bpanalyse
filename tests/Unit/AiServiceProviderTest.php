<?php

declare(strict_types=1);

use App\Services\AI\Contracts\EmbeddingClient;
use App\Services\AI\Contracts\LlmClient;
use App\Services\AI\Contracts\SttClient;
use App\Services\AI\Contracts\TtsClient;
use App\Services\AI\LlmManager;
use App\Services\AI\SttManager;
use App\Services\AI\TtsManager;

it('route chaque feature LLM vers le bon provider', function () {
    $llm = app(LlmManager::class);

    expect($llm->for('chat')->provider())->toBe('groq')
        ->and($llm->for('presentation')->provider())->toBe('groq')
        ->and($llm->for('summary')->provider())->toBe('groq')
        ->and($llm->for('debate')->provider())->toBe('claude')
        ->and($llm->for('financial_check')->provider())->toBe('claude');
});

it('bascule tout sur vllm/whisper/xtts en mode souverain', function () {
    config(['ai.sovereign' => true]);

    $llm = new LlmManager(config('ai'));
    $stt = new SttManager(config('ai'));
    $tts = new TtsManager(config('ai'));

    expect($llm->resolveProviderKey('chat'))->toBe('vllm')
        ->and($llm->resolveProviderKey('debate'))->toBe('vllm')
        ->and($stt->default()->provider())->toBe('whisper')
        ->and($tts->default()->provider())->toBe('xtts');
});

it('binde les interfaces mono-provider sur leur défaut', function () {
    expect(app(LlmClient::class))->toBeInstanceOf(LlmClient::class)
        ->and(app(SttClient::class)->provider())->toBe('deepgram')
        ->and(app(TtsClient::class)->provider())->toBe('elevenlabs')
        ->and(app(EmbeddingClient::class)->provider())->toBe('bge_m3')
        ->and(app(EmbeddingClient::class)->dimensions())->toBe(1024);
});

it('lève une erreur claire pour une feature LLM inconnue', function () {
    app(LlmManager::class)->for('inexistante');
})->throws(InvalidArgumentException::class);

it('expose le garde-fou de consentement pour le clonage vocal', function () {
    expect(app(TtsManager::class)->requiresConsentForCloning())->toBeTrue();
});
