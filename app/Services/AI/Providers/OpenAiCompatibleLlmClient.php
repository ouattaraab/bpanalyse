<?php

declare(strict_types=1);

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\LlmClient;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Client LLM pour toute API compatible OpenAI (Groq, vLLM, ...).
 * Endpoint : POST {base_url}/chat/completions.
 *
 * RÈGLE : ne sert qu'à commenter/raisonner. Les chiffres financiers ne sont
 * jamais demandés au modèle — ils viennent de StructuredDataService.
 */
class OpenAiCompatibleLlmClient implements LlmClient
{
    public function __construct(
        protected readonly string $provider,
        protected readonly string $apiKey,
        protected readonly string $model,
        protected readonly string $baseUrl,
    ) {
    }

    public function complete(array $messages, array $options = []): string
    {
        $response = $this->request([
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => $options['temperature'] ?? 0.2,
            'max_tokens' => $options['max_tokens'] ?? 1024,
        ]);

        return (string) ($response['choices'][0]['message']['content'] ?? '');
    }

    public function completeWithTools(array $messages, array $tools, array $options = []): array
    {
        $response = $this->request([
            'model' => $this->model,
            'messages' => $messages,
            'tools' => $tools,
            'tool_choice' => $options['tool_choice'] ?? 'auto',
            'temperature' => $options['temperature'] ?? 0.2,
            'max_tokens' => $options['max_tokens'] ?? 1024,
        ]);

        $message = $response['choices'][0]['message'] ?? [];

        return [
            'content' => $message['content'] ?? null,
            'tool_calls' => $message['tool_calls'] ?? [],
        ];
    }

    public function provider(): string
    {
        return $this->provider;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function request(array $payload): array
    {
        $response = Http::baseUrl($this->baseUrl)
            ->withToken($this->apiKey)
            ->timeout(60)
            ->acceptJson()
            ->post('/chat/completions', $payload);

        if ($response->failed()) {
            throw new RuntimeException(
                "Appel LLM ({$this->provider}) échoué : HTTP {$response->status()} {$response->body()}"
            );
        }

        return $response->json() ?? [];
    }
}
