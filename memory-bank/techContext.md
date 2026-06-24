# Tech Context

## Stack
- Backend : Laravel 11 (PHP 8.3), API REST + WebSocket (Reverb).
- Front : React 18 + Vite + Tailwind + Reveal.js (variante Flutter Web possible).
- Base : PostgreSQL 16 + pgvector.
- Queue : database queue / Redis.
- LLM : Groq (llama-3.3-70b-versatile) + Claude Sonnet 4.6 ; souverain Qwen 2.5 7B / vLLM.
- STT : Deepgram Nova ; souverain faster-whisper large-v3.
- TTS : ElevenLabs ; souverain XTTS-v2 / F5-TTS.
- Embeddings : bge-m3 / e5-large (FR).
- Parsing : Docling.

## Conventions
- PSR-12, declare(strict_types=1), services dans app/Services, pas de logique métier dans les contrôleurs.
- Appels IA derrière interfaces (app/Services/AI/Contracts).
- Tests : Pest (PHP, services IA mockés), Vitest (front).
- Tout texte UI en français.

## Variables d'environnement
GROQ_API_KEY, ANTHROPIC_API_KEY, DEEPGRAM_API_KEY, ELEVENLABS_API_KEY, AI_SOVEREIGN, VLLM_BASE_URL, EMBEDDING_BASE_URL.

## Commandes
php artisan serve | queue:work | migrate:fresh --seed | bp:ingest {file} ; npm run dev ; ./vendor/bin/pest
