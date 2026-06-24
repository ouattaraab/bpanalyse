<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\AI\Contracts\EmbeddingClient;
use App\Services\AI\Contracts\LlmClient;
use App\Services\AI\Contracts\SttClient;
use App\Services\AI\Contracts\TtsClient;
use App\Services\AI\EmbeddingManager;
use App\Services\AI\LlmManager;
use App\Services\AI\SttManager;
use App\Services\AI\TtsManager;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

/**
 * Câble les interfaces IA vers leurs providers concrets via config/ai.php.
 *
 * - Les managers résolvent le provider par feature / défaut (+ bascule souveraine).
 * - Les interfaces "mono-provider" (STT/TTS/Embedding) sont bindées sur le défaut.
 * - LlmClient est ambigu (résolu par feature) : par défaut → feature 'chat'.
 *   Les services qui ont besoin d'une autre feature injectent LlmManager et
 *   appellent ->for('debate' | 'financial_check' | ...).
 *
 * Règle absolue : aucune clé API ne sort du backend.
 */
final class AiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LlmManager::class, fn (Application $app) => new LlmManager($app['config']->get('ai')));
        $this->app->singleton(SttManager::class, fn (Application $app) => new SttManager($app['config']->get('ai')));
        $this->app->singleton(TtsManager::class, fn (Application $app) => new TtsManager($app['config']->get('ai')));
        $this->app->singleton(EmbeddingManager::class, fn (Application $app) => new EmbeddingManager($app['config']->get('ai')));

        $this->app->bind(LlmClient::class, fn (Application $app) => $app->make(LlmManager::class)->for('chat'));
        $this->app->bind(SttClient::class, fn (Application $app) => $app->make(SttManager::class)->default());
        $this->app->bind(TtsClient::class, fn (Application $app) => $app->make(TtsManager::class)->default());
        $this->app->bind(EmbeddingClient::class, fn (Application $app) => $app->make(EmbeddingManager::class)->default());
    }
}
