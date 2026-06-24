# Active Context

## État actuel
Étapes 0, 1 et 2 du BOOTSTRAP faites. Le **squelette est en place et vérifié** :
- Backend : Laravel 11.54 (PHP 8.4), PostgreSQL 17 + pgvector 0.8.3, Reverb, routing API, Pest.
- Bindings IA câblés via `AiServiceProvider` : managers (`LlmManager` par feature, `SttManager`,
  `TtsManager`, `EmbeddingManager` + bascule souveraine) → providers concrets dans
  `app/Services/AI/Providers/`. Providers LLM (Groq/Anthropic/OpenAI-compat) fonctionnels ;
  STT/TTS/Embeddings en stubs explicites (« à implémenter — story X »).
- Front : React 18 + Vite + Tailwind 3 + Reveal.js + Echo. `npm run build` OK.
- Tests : `./vendor/bin/pest` → 7 passés (dont routage LLM + bascule souveraine + page d'accueil).
- Fichiers du kit préservés (CLAUDE.md, config/ai.php, app/Services/**/Contracts, docs, memory-bank).

Base de données locale : user `aboubakarouattara` (Homebrew), base `bp_explorer` créée, migrations Laravel + pgvector appliquées.

## Prochaine action
**Phase 0 — Epic 1, story 1.2** (parsing Docling) via `/implement-story 1.2` :
`DoclingParser` (wrapper, derrière une interface `DocumentParser` mockable),
`ParseDocumentJob` (statut parsing → parsed), migration `document_slides`
(slide_index, title, section, image_path, raw_markdown). Tests : tableau Markdown préservé.

Story 1.1 livrée : tenants/documents, disque privé `documents` (isolé par tenant),
upload validé (pdf/pptx, 50 Mo), réponse sans chemin interne. Tests globaux : 13 passés.
Base de test dédiée : `bp_explorer_testing` (pgsql + pgvector) configurée dans phpunit.xml.

## Note environnement
Git non initialisé dans le repo — proposer un `git init` + premier commit avant d'attaquer les stories
(BOOTSTRAP recommande 1 commit/story).

## Décisions ouvertes
- Tester le function calling de Groq sur de vrais tableaux avant de lui confier l'outil de calcul (sinon router vers Claude).
- Front React + Reveal.js vs Flutter Web : trancher avant la Phase 2.

## Rappels
- Ne PAS implémenter l'Epic 7 (DocumentProfile) au MVP.
- Chiffres déterministes, jamais le LLM.
