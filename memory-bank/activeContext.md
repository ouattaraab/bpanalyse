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

## Phases 2 et 3 COMPLÈTES ✅
- **Phase 2 (présentation express)** : SlideSelector + NarrationGenerator + PresentationService ;
  front PresentationPlayer (Reveal.js) + SpeechSynthesis.
- **Phase 3 (débat du board)** : DebatePersonas (4), DebateOrchestrator (Claude), FinancialVerifier
  (déterministe), RunDebateJob ; front DebateView (répliques + chiffres ✓/⚠). API
  `POST /sessions/{uuid}/debates`, `GET /debates/{id}`.
Tests : **81 backend + 5 front**. Builds OK. Tout poussé sur `main`.

Les 3 différenciateurs sont en place : chat RAG sourcé, présentation express, débat avec vérif chiffres.

## Prochaine action — Phase 4 (voix clonée + gouvernance + session/compte rendu)
Epics 6 (consentement/modèle vocal révocable, AVANT toute synthèse clonée), 2.3 (réponse en voix
clonée), 5 (épinglage, export DOCX/PDF, audit/couverture, purge sessions). C'est ici que la voix
clonée du dirigeant (ElevenLabs) s'implémente, avec sa gouvernance ARTCI/Loi 2013-450.

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
