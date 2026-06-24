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
**Phase 0 — Epic 1, story 0-x** (orchestration) via `/implement-story` : commande
`php artisan bp:ingest {file} {--tenant=}` + `IngestionPipeline` chaînant intake →
ParseDocumentJob → ChunkDocumentJob → EmbedChunksJob → ExtractFinancialsJob.
Test : pipeline complet sur fixture (statuts uploaded→indexed, comptes attendus).
➡️ Clôt Epic 1 / Phase 0. Ensuite Phase 1 (FinancialQueryService + chat RAG).

Story 1.5 livrée : extraction financière **déterministe (aucun LLM)**.
- `FinancialValueParser` (FR/EN, %, négatifs, parenthèses, milliers ; valeur verbatim, échelle = unité),
  `FinancialTableExtractor` (grille → ParsedMetric), `ExtractFinancialsJob` (ignore tableaux non
  financiers, trace source_ref), migrations+modèles `financial_tables`/`financial_metrics`.
- Tests : 18 (dataset parseur, extracteur, job + garde « LlmClient jamais sollicité »).
  Validé sur le PRD réel : 13 tableaux → 84 mesures. Tests globaux : 50 passés.

Note 1.4 : modèle réel bge-m3 — validation runtime du download (~2,2 Go) lancée en arrière-plan.

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
