<?php

declare(strict_types=1);

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\LlmClient;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Client Claude (API Anthropic, endpoint /v1/messages).
 * Utilisé pour le débat du board et la vérification critique des chiffres
 * (raisonnement multi-étapes, détection des calculs faux).
 *
 * Le rôle "system" des messages est extrait vers le paramètre `system`
 * d'Anthropic ; seuls les rôles user/assistant restent dans `messages`.
 */
final class AnthropicClient implements LlmClient
{
    private const API_URL = 'https://api.anthropic.com/v1/messages';

    private const API_VERSION = '2023-06-01';

    public function __construct(
        private readonly string $apiKey,
        private readonly string $model,
    ) {}

    public function complete(array $messages, array $options = []): string
    {
        [$system, $chat] = $this->splitSystem($messages);

        $response = $this->request([
            'model' => $this->model,
            'max_tokens' => $options['max_tokens'] ?? 1024,
            'temperature' => $options['temperature'] ?? 0.2,
            'system' => $system,
            'messages' => $chat,
        ]);

        return $this->firstText($response);
    }

    public function completeWithTools(array $messages, array $tools, array $options = []): array
    {
        [$system, $chat] = $this->splitSystem($messages);

        $response = $this->request([
            'model' => $this->model,
            'max_tokens' => $options['max_tokens'] ?? 1024,
            'temperature' => $options['temperature'] ?? 0.2,
            'system' => $system,
            'messages' => $chat,
            'tools' => $tools,
        ]);

        $toolCalls = [];
        $text = null;
        foreach ($response['content'] ?? [] as $block) {
            if (($block['type'] ?? null) === 'tool_use') {
                $toolCalls[] = [
                    'id' => $block['id'] ?? null,
                    'name' => $block['name'] ?? null,
                    'arguments' => $block['input'] ?? [],
                ];
            } elseif (($block['type'] ?? null) === 'text') {
                $text = ($text ?? '').($block['text'] ?? '');
            }
        }

        return ['content' => $text, 'tool_calls' => $toolCalls];
    }

    public function provider(): string
    {
        return 'claude';
    }

    /**
     * Sépare les messages "system" du reste.
     *
     * @param  array<int, array{role:string, content:string}>  $messages
     * @return array{0:string, 1:array<int, array{role:string, content:string}>}
     */
    private function splitSystem(array $messages): array
    {
        $system = [];
        $chat = [];
        foreach ($messages as $message) {
            if ($message['role'] === 'system') {
                $system[] = $message['content'];
            } else {
                $chat[] = $message;
            }
        }

        return [implode("\n\n", $system), $chat];
    }

    /** @param array<string, mixed> $response */
    private function firstText(array $response): string
    {
        foreach ($response['content'] ?? [] as $block) {
            if (($block['type'] ?? null) === 'text') {
                return (string) ($block['text'] ?? '');
            }
        }

        return '';
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function request(array $payload): array
    {
        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'anthropic-version' => self::API_VERSION,
        ])->timeout(120)->acceptJson()->post(self::API_URL, $payload);

        if ($response->failed()) {
            throw new RuntimeException(
                "Appel LLM (claude) échoué : HTTP {$response->status()} {$response->body()}"
            );
        }

        return $response->json() ?? [];
    }
}
