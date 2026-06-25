<?php

declare(strict_types=1);

/**
 * Configuration des services IA et du routage LLM par feature.
 *
 * Le LLM est choisi par CAS D'USAGE, pas globalement. Groq pour le volume et
 * la latence ; Claude pour le raisonnement critique (débat, vérification des
 * chiffres). Le mode souverain bascule tout sur vLLM via AI_SOVEREIGN=true.
 */
return [

    // Bascule globale vers les providers auto-hébergés (souveraineté des données).
    'sovereign' => env('AI_SOVEREIGN', false),

    /*
     | Routage LLM par feature. Valeur = clé d'un provider défini plus bas.
     | En mode souverain, ces valeurs sont ignorées et tout passe sur 'vllm'.
     */
    'llm_routing' => [
        'chat' => env('LLM_CHAT', 'groq'),     // volume, faible latence
        'presentation' => env('LLM_PRESENTATION', 'groq'),
        'debate' => env('LLM_DEBATE', 'claude'), // détecte les calculs faux
        'financial_check' => env('LLM_FINANCIAL', 'claude'),
        'summary' => env('LLM_SUMMARY', 'groq'),
    ],

    'providers' => [
        'groq' => [
            'driver' => 'groq',
            'api_key' => env('GROQ_API_KEY'),
            'model' => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
            'base_url' => 'https://api.groq.com/openai/v1',
        ],
        'claude' => [
            'driver' => 'anthropic',
            'api_key' => env('ANTHROPIC_API_KEY'),
            'model' => env('CLAUDE_MODEL', 'claude-sonnet-4-6'),
        ],
        'vllm' => [
            'driver' => 'openai_compatible',
            'base_url' => env('VLLM_BASE_URL', 'http://localhost:8000/v1'),
            'model' => env('VLLM_MODEL', 'qwen2.5-7b-instruct'),
        ],
    ],

    'stt' => [
        'default' => env('STT_PROVIDER', 'deepgram'),
        'providers' => [
            'deepgram' => ['api_key' => env('DEEPGRAM_API_KEY'), 'model' => 'nova-2', 'language' => 'fr'],
            'whisper' => ['base_url' => env('WHISPER_BASE_URL', 'http://localhost:9000'), 'model' => 'large-v3'],
        ],
    ],

    'tts' => [
        'default' => env('TTS_PROVIDER', 'elevenlabs'),
        'providers' => [
            'elevenlabs' => ['api_key' => env('ELEVENLABS_API_KEY')],
            'xtts' => ['base_url' => env('XTTS_BASE_URL', 'http://localhost:8020')],
        ],
        // Garde-fou : interdit tout clonage sans consentement vérifié.
        'require_consent_for_cloning' => true,
    ],

    // Embeddings souverains (locaux) : bge-m3 via un process Python dédié.
    'embeddings' => [
        'default' => env('EMBEDDING_PROVIDER', 'bge_m3'),
        'providers' => [
            'bge_m3' => [
                'driver' => 'process',
                'python' => env('EMBEDDING_PYTHON', base_path('tools/embeddings/.venv/bin/python')),
                'script' => env('EMBEDDING_SCRIPT', base_path('tools/embeddings/embed.py')),
                'model' => env('EMBEDDING_MODEL', 'BAAI/bge-m3'),
                'dimensions' => 1024,
                // 1er chargement du modèle bge-m3 (~2,3 Go) parfois lent → marge large.
                'timeout' => (int) env('EMBEDDING_TIMEOUT', 1800),
            ],
        ],
    ],

];
