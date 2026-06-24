# Plan d'implémentation — BP Explorer

> Produit à l'Étape 1 du BOOTSTRAP. Détaille, par phase et par user story : objectif,
> migrations, services/fichiers, endpoints/front, tests Pest, et Definition of Done (DoD).
> Aucune ligne de code applicatif n'est encore écrite à ce stade.
>
> Règles transverses appliquées à CHAQUE story (CLAUDE.md §4) :
> - Aucun chiffre produit par le LLM → tout passe par `FinancialQueryService` (déterministe).
> - Aucune clé API côté front → tous les appels IA via le backend, derrière les interfaces.
> - Voix clonée uniquement avec consentement vérifié.
> - Toute réponse utilisateur tracée (question + sources + slides + horodatage).
> - Sessions éphémères, purge programmée. Jamais CinetPay.

---

## Modèle de données global (vue d'ensemble)

Tables introduites au fil des phases (détail dans chaque story) :

| Table | Phase | Rôle |
|---|---|---|
| `tenants` | 0 | Isolation multi-tenant (stockage + données) |
| `documents` | 0 | BP téléversé : statut, chemin, métadonnées |
| `document_slides` | 0 | 1 ligne / slide : image, titre, section |
| `chunks` | 0 | Chunks sémantiques + `embedding vector(1024)` (pgvector) |
| `financial_tables` | 0 | Tableaux financiers extraits (markdown source + légende) |
| `financial_metrics` | 0 | Données chiffrées en format long (label, période, valeur, unité, source) |
| `explorer_sessions` | 1 | Session éphémère (uuid, expiration, purge) |
| `interactions` | 1 | Échanges question/réponse, tous modes |
| `audit_logs` | 1 | Trace : question, sources, modèle, latence, horodatage |
| `presentations` | 2 | Script narré JSON `[{slide_id, narration, duree}]` |
| `debates` + `debate_turns` | 3 | Débat tour-par-tour, sources + chiffres vérifiés |
| `pinned_items` | 4 | Épinglage de session |
| `voice_consents` + `voice_models` | 4 | Gouvernance biométrique (consentement ↔ modèle révocable) |

Note d'architecture : au MVP le comportement « BP » est **codé en dur dans des services dédiés**.
On n'implémente PAS l'abstraction `DocumentProfile` (Epic 7) — extraction au 2e type de document seulement.

---

## PHASE 0 — Socle technique + ingestion (Epic 1)

### Phase 0a — Scaffolding (BOOTSTRAP Étape 2, pré-requis)

**Objectif.** Squelette Laravel 11 par-dessus les fichiers existants, sans les écraser
(`CLAUDE.md`, `docs/`, `memory-bank/`, `.claude/`, `app/Services/**/Contracts`, `config/ai.php`).

**Fichiers / actions :**
- `composer.json`, structure Laravel 11 (`artisan`, `bootstrap/`, `routes/`, `database/`).
- Extension PHP `pdo_pgsql` ; package `pgvector/pgvector` (ou migration SQL `CREATE EXTENSION vector`).
- `config/database.php` : connexion `pgsql` (déjà dans `.env.example`).
- `app/Providers/AiServiceProvider.php` : binde `LlmClient`/`SttClient`/`TtsClient`/`EmbeddingClient`
  vers les managers (résolution par feature via `config/ai.php`).
- Managers : `app/Services/AI/LlmManager.php`, `SttManager.php`, `TtsManager.php`, `EmbeddingManager.php`
  (méthode `for(string $feature)` / `default()` + bascule `sovereign`).
- Providers concrets (squelettes) : `app/Services/AI/Providers/{GroqClient, AnthropicClient,
  OpenAiCompatibleLlmClient, DeepgramSttClient, WhisperSttClient, ElevenLabsTtsClient,
  XttsTtsClient, BgeM3EmbeddingClient}.php`.
- Laravel Reverb (WebSocket) + `database` queue installés et configurés.
- Front : `npm` + Vite + React 18 + Tailwind + Reveal.js + Laravel Echo (squelette `resources/js`).

**Tests :**
- `tests/Unit/AiServiceProviderTest.php` : `LlmManager::for('debate')` → provider `claude` ;
  `for('chat')` → `groq` ; `AI_SOVEREIGN=true` → tout sur `vllm`.
- Smoke test `php artisan` démarre, `migrate` passe avec l'extension vector.

**DoD :** `php artisan serve` + `npm run dev` démarrent ; bindings résolus ; aucun fichier existant écrasé.

---

### Story 1.1 — Téléverser un BP (PDF/PPTX) · P0
**Objectif.** Upload sécurisé, isolé par tenant, formats validés.

- **Migrations :** `tenants` (id, name, slug, timestamps) ; `documents` (id, tenant_id FK,
  title, type default `business_plan`, original_path, mime, status enum
  `uploaded|parsing|parsed|indexed|failed`, page_count nullable, meta jsonb, timestamps).
- **Services/fichiers :**
  - `app/Models/{Tenant, Document}.php`.
  - `app/Http/Controllers/DocumentController@store`.
  - `app/Http/Requests/StoreDocumentRequest.php` (règles : mimes pdf/pptx, taille max, tenant requis).
  - Stockage isolé : disque `documents` configuré par tenant (`storage/app/tenants/{id}/...`).
  - `app/Services/Ingestion/DocumentIntakeService.php` (persiste fichier + crée `Document` à `uploaded`).
- **Endpoint :** `POST /api/documents` (multipart).
- **Tests :** `Feature/DocumentUploadTest` : refus format invalide, isolation tenant (un tenant ne voit
  pas le fichier d'un autre), création en statut `uploaded`, pas de données perso en query string.
- **DoD :** upload PDF/PPTX OK, isolé, statut initial correct.

### Story 1.2 — Parser en préservant les tableaux · P0
**Objectif.** Docling → Markdown structuré, tableaux non cassés.

- **Services/fichiers :**
  - `app/Services/Ingestion/DoclingParser.php` (wrapper CLI/HTTP Docling → Markdown + structure slides).
  - `app/Services/Ingestion/Contracts/DocumentParser.php` (interface, pour mocker en test).
  - `app/Jobs/ParseDocumentJob.php` (queue ; statut `parsing` → `parsed`).
  - Migration : `document_slides` (id, document_id FK, slide_index, title, section, image_path nullable,
    raw_markdown, timestamps).
- **Tests :** `Unit/DoclingParserTest` (mock process, vérifie qu'un tableau Markdown reste intègre) ;
  `Feature/ParseDocumentJobTest` (statut transitions, slides créées).
- **DoD :** un BP de test produit des slides + Markdown ; aucun tableau cassé (assert structure pipe-table).

### Story 1.3 — Chunking sémantique · P0
**Objectif.** Découpage : **1 tableau = 1 chunk** ; texte par section ; métadonnées.

- **Services/fichiers :**
  - `app/Services/Ingestion/SemanticChunker.php` (règle absolue : un tableau = un chunk, légende incluse ;
    texte découpé par section avec recouvrement contrôlé).
  - `app/Jobs/ChunkDocumentJob.php`.
  - Migration : `chunks` (id, document_id FK, slide_id FK nullable, section, type enum `text|table`,
    content text, metadata jsonb {slide_id, section, type}, `embedding vector(1024)` nullable, timestamps).
- **Tests :** `Unit/SemanticChunkerTest` : un tableau d'entrée → exactement 1 chunk de type `table`
  contenant la légende ; texte multi-section → 1 chunk/section ; métadonnées présentes.
- **DoD :** invariant « 1 tableau = 1 chunk » garanti par test ; métadonnées peuplées.

### Story 1.4 — Indexer texte et slides en vecteurs · P0
**Objectif.** Embeddings FR en pgvector ; 1 image/slide indexée.

- **Services/fichiers :**
  - `app/Services/AI/Providers/BgeM3EmbeddingClient.php` (implémente `EmbeddingClient`, dim 1024).
  - `app/Jobs/EmbedChunksJob.php` (batch embeddings → `chunks.embedding`).
  - Migration : index pgvector sur `chunks.embedding` (HNSW ou IVFFlat, cosine) ; statut document → `indexed`.
  - (Option) embedding image/slide : colonne/table `slide_embeddings` si recherche visuelle prévue.
- **Tests :** `Unit/BgeM3EmbeddingClientTest` (mock HTTP, dim = `dimensions()`), `Feature/EmbedChunksJobTest`
  (embeddings écrits, dim conforme à la colonne).
- **DoD :** chunks vectorisés, recherche cosine fonctionnelle, document `indexed`.

### Story 1.5 — Extraire les tableaux financiers en SQL · P0  *(clé anti-hallucination)*
**Objectif.** Tables SQL dédiées requêtables → socle de `FinancialQueryService`.

- **Migrations :**
  - `financial_tables` (id, document_id FK, slide_id FK nullable, name, caption, raw_markdown, timestamps).
  - `financial_metrics` (format long : id, financial_table_id FK, document_id FK, label, category nullable,
    period_label, period_year nullable, value numeric, unit, source_ref jsonb {slide_id, table}, timestamps).
- **Services/fichiers :**
  - `app/Services/Ingestion/FinancialTableExtractor.php` (parse les chunks `table` → lignes `financial_metrics`,
    normalisation unités/périodes ; **déterministe**, pas de LLM pour les valeurs).
  - `app/Jobs/ExtractFinancialsJob.php`.
  - `app/Models/{FinancialTable, FinancialMetric, DocumentSlide, Chunk}.php`.
- **Tests :** `Unit/FinancialTableExtractorTest` : tableau markdown → lignes attendues (valeurs/périodes/unités
  exactes) ; aucun appel LLM (assert via mock que `LlmClient` n'est jamais sollicité).
- **DoD :** `financial_metrics` peuplée et requêtable ; traçabilité `source_ref` vers slide/tableau.

### Story 0-x — Orchestration `bp:ingest` (transverse Epic 1)
- **Fichiers :** `app/Console/Commands/IngestBpCommand.php` (`bp:ingest {file} {--tenant=}`) chaînant
  intake → `ParseDocumentJob` → `ChunkDocumentJob` → `EmbedChunksJob` → `ExtractFinancialsJob`.
  `app/Services/Ingestion/IngestionPipeline.php` (façade).
- **Tests :** `Feature/IngestCommandTest` (pipeline complet sur fixture, statuts, comptes attendus).
- **DoD :** `php artisan bp:ingest fixture.pdf` mène un BP de `uploaded` à `indexed` avec financials extraits.

---

## PHASE 1 — Chat RAG sourcé + outil de calcul (Epics 2.1-2.2 + 1.5 branché)

### Pré-requis transverses Phase 1
- **Migrations :** `explorer_sessions` (id, uuid, tenant_id FK, document_id FK, status, started_at,
  expires_at, timestamps) ; `interactions` (id, session_id FK, document_id FK, role, mode enum
  `chat|presentation|debate`, question text, answer text nullable, meta jsonb, timestamps) ;
  `audit_logs` (id, session_id FK, interaction_id FK nullable, document_id FK, question, sources jsonb,
  slides jsonb, model_used, latency_ms, created_at).
- **Services socle :**
  - `app/Services/Session/SessionService.php` (créer/charger session, fixer `expires_at`).
  - `app/Services/Audit/AuditLogger.php` (1 appel = 1 trace ; utilisé par TOUTES les réponses).
  - `app/Services/Rag/Retriever.php` (recherche cosine pgvector + filtre métadonnées → chunks sourcés).
  - `app/Services/Rag/SourceFormatter.php` (citations slide/tableau normalisées).

### Story 1.5 (branche service) — `FinancialQueryService`
**Objectif.** Implémenter `StructuredDataService` : requêtes déterministes whitelistées sur `financial_metrics`.

- **Fichiers :**
  - `app/Services/Document/FinancialQueryService.php` (implémente `StructuredDataService`).
    - `query()` : requêtes **paramétrées/validées** (jamais de SQL libre du LLM) sur `financial_metrics`.
    - `capabilities()` : expose les « outils » (ex. `get_metric`, `compare_periods`, `sum_category`,
      `growth_rate`) avec schéma de paramètres → utilisés en function calling.
  - `app/Services/Document/Queries/*` (une classe par requête whitelistée, testable isolément).
- **Tests :** `Unit/FinancialQueryServiceTest` : chaque capability rend des valeurs exactes issues de la base ;
  rejet d'une requête non whitelistée ; aucun LLM impliqué ; résultats traçables vers `source_ref`.
- **DoD :** valeurs/ratios/croissances calculés **par SQL**, jamais devinés ; capabilities exposables au LLM.

### Story 2.1 — Question écrite → réponse sourcée · P0
**Objectif.** RAG + citation slide/tableau, en français.

- **Fichiers :**
  - `app/Services/Rag/RagService.php` : retrieve → prompt FR (consigne stricte « ne calcule pas, commente les
    valeurs fournies ») → `LlmManager::for('chat')` (Groq) → réponse + citations ; trace via `AuditLogger`.
    Si la question implique des chiffres → valeurs injectées depuis `FinancialQueryService`, pas générées.
  - `app/Http/Controllers/ChatController@ask` + `app/Http/Requests/AskQuestionRequest.php`.
  - `app/Http/Resources/AnswerResource.php` (réponse + sources + interaction_id).
  - Front : `resources/js/features/chat/{ChatPanel.jsx, useChat.js, SourceList.jsx}`.
- **Endpoint :** `POST /api/sessions/{uuid}/chat`.
- **Tests :** `Feature/ChatRagTest` (mock `LlmClient` + Retriever) : réponse cite ≥1 source réelle ;
  audit créé ; **chiffre demandé provient de `FinancialQueryService`** (assert provenance, LLM mocké ne calcule pas) ;
  aucune clé API renvoyée au front.
- **DoD :** réponse FR sourcée, tracée, sans chiffre halluciné.

### Story 2.2 — Question à l'oral · P0
**Objectif.** STT, transcription affichée puis traitée comme une question écrite.

- **Fichiers :**
  - `app/Services/AI/Providers/DeepgramSttClient.php` (implémente `SttClient`, FR).
  - `app/Http/Controllers/TranscriptionController@store` (upload audio → `SttManager::default()->transcribe`).
  - Front : `resources/js/features/chat/useAudioRecorder.js` + affichage transcription éditable.
- **Endpoint :** `POST /api/sessions/{uuid}/transcribe` → renvoie texte (puis réutilise `/chat`).
- **Tests :** `Unit/DeepgramSttClientTest` (mock HTTP) ; `Feature/TranscribeTest` (audio → texte, FR).
- **DoD :** parole → transcription affichée → réutilisable comme question écrite.

**Sortie de Phase 1 :** mise à jour `progress.md` + `activeContext.md` (prochaine action : Phase 2).

---

## PHASE 2 — Présentation express (Epic 3) · *différenciateur*

### Story 3.1 — Question → présentation 1-2 min · P0
**Objectif.** Sélection 3-6 slides pertinentes, ordre logique.

- **Migration :** `presentations` (id, session_id FK, document_id FK, question, script jsonb, status enum
  `pending|generating|ready|failed`, duration_total nullable, timestamps).
- **Fichiers :** `app/Services/Presentation/SlideSelector.php` (recherche vectorielle sur `document_slides`/
  chunks → 3-6 slides + ordonnancement logique).
- **Tests :** `Unit/SlideSelectorTest` (retourne 3-6 slides pertinentes, ordonnées, sans doublon).
- **DoD :** sélection bornée 3-6, ordre cohérent.

### Story 3.2 — Narration 2-3 phrases / slide · P0
**Objectif.** Script JSON `[{slide_id, narration, duree}]`.

- **Fichiers :** `app/Services/Presentation/NarrationGenerator.php` (`LlmManager::for('presentation')` (Groq),
  consigne FR 2-3 phrases ; chiffres issus de `FinancialQueryService` si la slide en contient) ;
  `app/Services/Presentation/PresentationService.php` (orchestration) ; `app/Jobs/GeneratePresentationJob.php`.
- **Endpoint :** `POST /api/sessions/{uuid}/presentations` ; `GET /api/presentations/{id}` (script).
- **Tests :** `Unit/NarrationGeneratorTest` (mock LLM) : schéma JSON valide, FR, chiffres non générés ;
  `Feature/PresentationGenerationTest` (statut `ready`, latence < 20 s cible — mesurée).
- **DoD :** script JSON valide, conforme schéma, chiffres tracés.

### Story 3.3 — Défilement synchronisé avec la voix · P0
**Objectif.** Switch slide sur fin d'audio / timestamps.

- **Fichiers :**
  - `app/Services/Presentation/SynthesizeNarrationJob.php` (TTS par slide via `TtsManager`,
    voix standard ou clonée si consentement — cf. Phase 4) ; durées audio stockées dans le script.
  - Front : `resources/js/features/presentation/{PresentationPlayer.jsx (Reveal.js), useNarrationSync.js}`
    (avance la slide sur `ended`/timestamps) ; streaming progressif via Echo/Reverb.
- **Tests :** `Vitest` `useNarrationSync.test.js` (avance à la fin de l'audio) ;
  `Feature/NarrationAudioTest` (1 audio/slide, durées renseignées).
- **DoD :** lecture auto-pilotée slide↔voix synchronisée.

### Story 3.4 — Narration affichée à l'écrit · P1
- **Fichiers :** front `NarrationCaption.jsx` (texte sous chaque slide). **Tests :** Vitest rendu.
- **DoD :** texte de narration visible sous la slide courante.

---

## PHASE 3 — Débat du board (Epic 4)

### Story 4.1 — Lancer un débat · P0
**Objectif.** Orchestrateur tour-par-tour, 4 personas (DG, Investisseur, DAF, Commerciale).

- **Migrations :** `debates` (id, session_id FK, document_id FK, question, status, stop_condition jsonb,
  timestamps) ; `debate_turns` (id, debate_id FK, persona, turn_index, content text, sources jsonb,
  verified_figures jsonb, timestamps).
- **Fichiers :**
  - `app/Services/Debate/DebatePersonas.php` (4 personas BP **codés en dur** : postures + system prompts).
  - `app/Services/Debate/DebateOrchestrator.php` (boucle tour-par-tour, `LlmManager::for('debate')` (Claude)).
  - `app/Jobs/RunDebateJob.php` (émet chaque tour via Reverb pour affichage live).
  - `app/Http/Controllers/DebateController@start`.
- **Endpoint :** `POST /api/sessions/{uuid}/debates` ; canal WebSocket `debate.{id}`.
- **Tests :** `Unit/DebateOrchestratorTest` (mock LLM) : 4 personas s'expriment, ordre tour-par-tour respecté.
- **DoD :** débat lancé, tours diffusés en direct.

### Story 4.2 — Chaque agent cite ses sources · P0
- **Fichiers :** `app/Services/Debate/SourceBinder.php` (chaque réplique référence slide/tableau via Retriever).
- **Tests :** `Unit` : toute réplique contient ≥1 source valide (sinon rejet/retry).
- **DoD :** sources présentes et valides à chaque tour.

### Story 4.3 — Vérification des chiffres · P0  *(cœur de valeur)*
**Objectif.** Appels outil de calcul, fragilités signalées.

- **Fichiers :**
  - `app/Services/Debate/FinancialVerifier.php` : function calling (`LlmManager::for('financial_check')`
    (Claude)) → appelle `FinancialQueryService::capabilities()` → compare l'affirmation au chiffre réel →
    marque `verified_figures` (ok / fragile / incohérent).
  - Garde-fou : si le provider de débat est Groq, **tester son function calling** ; sinon router la vérif
    sur Claude (déjà le défaut `financial_check`).
- **Tests :** `Unit/FinancialVerifierTest` : un chiffre faux dans une réplique est détecté et signalé ;
  les valeurs de référence viennent de la base, pas du LLM.
- **DoD :** au moins les calculs faux d'un BP de test sont signalés ; chiffres de référence déterministes.

### Story 4.4 — Arrêt propre du débat · P1
- **Fichiers :** `DebateOrchestrator` : condition d'arrêt configurable (N tours / consensus / divergence).
- **Tests :** `Unit` : arrêt à N tours ; arrêt sur consensus simulé.
- **DoD :** le débat se termine proprement selon condition.

---

## PHASE 4 — Voix clonée + gouvernance + session/compte rendu (Epics 2.3, 5, 6)

### Gouvernance voix (Epic 6) — *à faire AVANT toute synthèse de voix clonée*

#### Story 6.1 — Consentement écrit avant clonage · P0
- **Migration :** `voice_consents` (id, tenant_id FK, person_name, scope, signed_document_path,
  granted_at, revoked_at nullable, status enum `active|revoked`, timestamps).
- **Fichiers :** `app/Services/Voice/ConsentService.php` (`assertValid(consentRef)` ; bloque clonage/synthèse
  si absent/révoqué — `config tts.require_consent_for_cloning`) ; `ConsentController` ; modèle `VoiceConsent`.
- **Tests :** `Unit/ConsentServiceTest` (clonage refusé sans consentement valide) ;
  `Feature/ConsentFlowTest`.
- **DoD :** aucun clonage/synthèse clonée possible sans consentement actif.

#### Story 6.2 — Modèle vocal isolé et révocable · P0
- **Migration :** `voice_models` (id, consent_id FK, provider, external_voice_id, status, created_at,
  revoked_at nullable).
- **Fichiers :** `app/Services/Voice/VoiceModelService.php` (`create()` lié au consentement ; `revoke()` =
  suppression du modèle chez le provider + tombstone local) ; `app/Jobs/CloneVoiceJob.php`.
  Implémentations TTS : `ElevenLabsTtsClient.php` (+ `XttsTtsClient.php` souverain) — `cloneVoice()`.
- **Tests :** `Unit/VoiceModelServiceTest` : révocation supprime le modèle et bloque toute synthèse ultérieure.
- **DoD :** modèle isolé, lié au consentement, révocation = suppression effective.

#### Story 6.3 — Conformité Loi 2013-450 / ARTCI · P1
- **Fichiers :** registre (finalité, durée, base légale) — table `voice_processing_register` ou colonnes
  sur `voice_consents` ; export du registre.
- **Tests :** `Feature/VoiceRegisterTest` (chaque modèle a finalité + durée tracées).
- **DoD :** registre consultable, finalité/durée renseignées.

#### Story 2.3 — Réponse en voix clonée · P1
- **Fichiers :** `app/Services/AI/Providers/ElevenLabsTtsClient@synthesize` (voix clonée si `voiceId` valide) ;
  branchement dans `RagService`/`PresentationService` (synthèse seulement après `ConsentService::assertValid`).
- **Tests :** `Feature/ClonedVoiceAnswerTest` : voix + texte synchronisés ; synthèse clonée refusée sans consentement.
- **DoD :** réponse audio en voix clonée, sous consentement, texte synchronisé.

### Session, audit, compte rendu (Epic 5)

#### Story 5.1 — Épingler les réponses pertinentes · P0
- **Migration :** `pinned_items` (id, session_id FK, interaction_id FK, note nullable, timestamps).
- **Fichiers :** `app/Services/Session/PinService.php` ; `PinController` ; front `PinnedPanel.jsx`.
- **Tests :** `Feature/PinTest` (épingler/désépingler, liste de session).
- **DoD :** liste épinglée par session.

#### Story 5.2 — Exporter un compte rendu IA · P0
- **Fichiers :** `app/Services/Export/ReportExporter.php` (synthèse des items épinglés → DOCX via PhpWord,
  PDF via DomPDF) ; `ExportController@download`.
- **Endpoint :** `GET /api/sessions/{uuid}/export?format=docx|pdf`.
- **Tests :** `Feature/ExportTest` (DOCX et PDF générés, contiennent les items épinglés + sources).
- **DoD :** livrable DOCX/PDF téléchargeable.

#### Story 5.3 — Audit de toute réponse · P0
- **Statut :** socle posé en Phase 1 (`AuditLogger`). Ici : vue/export d'audit + couverture de TOUS les modes
  (chat, présentation, débat). **Tests :** `Feature/AuditCoverageTest` (chaque mode produit une trace complète).
- **DoD :** 100 % des réponses tracées (question + sources + slides + horodatage).

#### Story 5.4 — Session éphémère · P1
- **Fichiers :** `app/Services/Session/SessionPurger.php` + `app/Console/Commands/PurgeSessionsCommand.php`
  (`sessions:purge`) planifié dans `routes/console.php` (scheduler).
- **Tests :** `Feature/SessionPurgeTest` (sessions expirées + données associées supprimées).
- **DoD :** purge programmée opérationnelle.

---

## PHASE 5 (post-MVP) — DocumentProfile / multi-types (Epic 7)

> À engager **seulement au 2e type de document**. C'est ici qu'on EXTRAIT l'abstraction
> (refactor du code BP vers `DocumentProfile`), pas avant. Slash-commande `/new-document-profile`.

- **7.1** Choix d'un profil à l'ingestion (le profil paramètre l'ingestion).
- **7.2** Personas du débat dépendants du profil (Contrat → Juriste/Parties/Risk ; Norme → Auditeur/Ops/DPO).
- **7.3** Extracteurs structurés abstraits par type (`StructuredDataService` : financier, clauses, exigences).
- **7.4** Rendu adapté aux documents sans slides (`renderMode = generated_sections`).
- **7.5 / 7.6** Questions/ton par profil ; nouveau profil par config/registry sans toucher au cœur RAG.

**Refactor cible :** `BusinessPlanProfile implements DocumentProfile` ; `FinancialQueryService` devient
le `StructuredDataService` du profil BP ; registre de profils en config.

---

## Stratégie de tests (transverse)

- **Pest (backend) :** services IA toujours **mockés** (aucun appel réseau en test). Tests dédiés aux règles :
  `NoLlmFigureGenerationTest` (les chiffres viennent de la base), `ApiKeyNotLeakedTest` (aucune clé au front),
  `ConsentEnforcedTest`, `AuditTrailTest`, `NoCinetPayTest`.
- **Vitest (front) :** sync narration, rendu sources, états de session.
- **Fixtures :** un petit BP de test (PDF + tableaux financiers connus) pour valider extraction et vérif chiffres.
- Slash-commande `/review-ai-safety` exécutée après chaque story touchant l'IA.

## Jalons / ordre d'exécution recommandé

1. Phase 0a (scaffolding + bindings) → 2. Epic 1 complet (`bp:ingest` vert) →
3. `FinancialQueryService` + Chat RAG (Phase 1) → 4. Présentation express (Phase 2) →
5. Débat + vérif chiffres (Phase 3) → 6. Voix/gouvernance + session/compte rendu (Phase 4).
Commits fréquents (1/story). MAJ `memory-bank/{progress,activeContext}.md` après chaque story.
