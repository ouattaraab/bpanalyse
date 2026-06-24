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

## État : PHASES 0 et 1 (backend) COMPLÈTES ✅
- **Phase 0 (ingestion)** : `bp:ingest` → parse (Docling) → chunk → embeddings bge-m3 (pgvector,
  modèle réel validé) → extraction financière déterministe.
- **Phase 1 (chat RAG + outil de calcul)** : socle session/audit/retriever, `FinancialQueryService`
  (StructuredDataService déterministe), chat RAG sourcé (2.1), STT Deepgram (2.2).
- API REST : `POST /documents`, `/documents/{id}/sessions`, `/sessions/{uuid}/chat`, `/sessions/{uuid}/transcribe`.
- Tests globaux : **71 passés**. Tout poussé sur `main`.

## Front chat vocal livré ✅
`resources/js/` : `lib/api.js`, `features/upload/UploadPanel`, `features/chat/{ChatPanel, useChat,
useAudioRecorder, SourceList, messages}`, `components/App` (orchestration upload→session→chat +
suivi statut ingestion). Backend : upload déclenche le pipeline, `tenant_id` optionnel (tenant
`default`), nouvel endpoint `GET /documents/{id}`. Vitest (jsdom + RTL) configuré : 5 tests verts.
Tests : 72 backend + 5 front. Build front OK.

Pour une démo complète : `php artisan queue:work` (dérouler l'ingestion) + `npm run dev` + `php artisan serve`.

## Prochaine action — au choix
**Phase 2 (présentation express, différenciateur)** : `SlideSelector` (3-6 slides), `NarrationGenerator`
(JSON `[{slide_id, narration, duree}]`, routage `presentation`→Groq), `PresentationService` + jobs,
table `presentations`, TTS par slide, front Reveal.js synchronisé.
**Ou Phase 3 (débat du board)** : orchestrateur 4 personas + vérification chiffres (function calling → Claude).

Story 1.2 livrée : intégration Docling en **Python via process**.
- `tools/docling/parse.py` (venv dédié `tools/docling/.venv`, requirements.txt) → JSON structuré.
- PHP : interface `DocumentParser` + DTOs `ParsedDocument`/`ParsedSlide`, `DoclingParser`
  (Symfony Process), `ParseDocumentJob` (parsing → parsed/failed), config `config/ingestion.php`,
  migration + modèle `document_slides`. Tests : 6 (wrapper via stub Python, job avec parser mocké).
- ✓ VALIDÉ RUNTIME : Docling 2.x installé dans le venv ; `parse.py` puis la chaîne complète
  PHP→Python (`app(DocumentParser::class)->parse(...)`) testés sur un vrai document (docx) →
  JSON conforme, tableaux préservés (pipes présents). Note : un docx donne page_count=1
  (non paginé) → repli 1 slide ; la segmentation multi-pages s'exprimera sur PDF/PPTX.
Tests globaux : 19 passés.

## Note environnement
Git non initialisé dans le repo — proposer un `git init` + premier commit avant d'attaquer les stories
(BOOTSTRAP recommande 1 commit/story).

## Décisions ouvertes
- Tester le function calling de Groq sur de vrais tableaux avant de lui confier l'outil de calcul (sinon router vers Claude).
- Front React + Reveal.js vs Flutter Web : trancher avant la Phase 2.

## Rappels
- Ne PAS implémenter l'Epic 7 (DocumentProfile) au MVP.
- Chiffres déterministes, jamais le LLM.
