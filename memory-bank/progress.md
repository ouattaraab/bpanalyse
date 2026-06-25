# Progress

## Fait
- Cadrage produit, PRD BMAD (8 sections, 7 epics, ~32 user stories).
- Diagramme d'architecture.
- Décisions techniques : routage LLM hybride Groq/Claude ; abstraction DocumentProfile cadrée pour plus tard.
- Kit de démarrage : CLAUDE.md, docs, interfaces IA, config/ai.php, memory-bank.

## En cours
- Scaffolding + **Phase 0 (Epic 1) COMPLÈTE** : Laravel 11.54, PostgreSQL 17 + pgvector 0.8.3,
  Reverb, routing API, Pest ; bindings IA (managers + providers) ; front React 18/Vite/Tailwind/
  Reveal.js/Echo. Pipeline d'ingestion bout en bout (`bp:ingest`) : intake → Docling → chunking →
  embeddings bge-m3 souverains → extraction financière déterministe. **53 tests verts.** Poussé sur `main`.
- Outils Python souverains via process : `tools/docling/` (parsing) et `tools/embeddings/` (bge-m3),
  venvs gitignorés.
- Prochaine action : **Phase 1** (chat RAG sourcé + FinancialQueryService + STT).

## À faire (par phase — détail dans implementationPlan.md)

### Phase 0 — Socle + ingestion (Epic 1)
- [x] 0a — Scaffolding Laravel 11 + bindings interfaces (managers + providers concrets)
- [x] 1.1 — Upload BP (PDF/PPTX), isolé tenant `[tenants, documents]` — 6 tests verts
- [x] 1.2 — Parsing Docling (Python via process), tableaux préservés `[document_slides]` — 6 tests verts
      ✓ validé runtime : Docling 2.x installé (venv), parse.py + chaîne PHP→Python OK sur un vrai doc (tableaux préservés)
- [x] 1.3 — Chunking sémantique (1 tableau = 1 chunk) `[chunks]` — 7 tests verts ;
      validé sur le PRD réel (13 chunks tableau + 19 texte)
- [x] 1.4 — Embeddings bge-m3 (Python via process, souverain) → pgvector + index HNSW cosine — 6 tests verts
      ✓ modèle réel bge-m3 validé : 2 textes → vecteurs dim 1024 (valeurs réelles)
- [x] 1.5 — Extraction tableaux financiers en SQL `[financial_tables, financial_metrics]` — 18 tests verts ;
      validé sur le PRD réel (13 tableaux → 84 mesures, 100% déterministe, aucun LLM)
- [x] 0-x — Commande `bp:ingest` + `IngestionPipeline` (chaîne parse→chunk→embed→financials) — 3 tests verts
      ✅ **PHASE 0 / EPIC 1 COMPLÈTE** — pipeline d'ingestion de bout en bout (uploaded → indexed)

### Phase 1 — Chat RAG + outil de calcul (Epics 2.1-2.2, 1.5)
- [x] Socle session/audit/retriever `[explorer_sessions, interactions, audit_logs]` — 10 tests verts
      (SessionService, AuditLogger, Retriever cosine pgvector, SourceFormatter)
- [x] 1.5b — `FinancialQueryService` (StructuredDataService : list_metrics/get_metric/compare_periods,
      croissance déterministe, capabilities) — couvert par les 10 tests socle
- [x] 2.1 — Question écrite → réponse sourcée (RAG, chiffres déterministes) — 3 tests verts
      (RagService, ChatController + SessionController, AnswerResource ; prompt anti-calcul, audit)
- [x] 2.2 — Question orale (STT Deepgram, transcription) — 5 tests verts
      (DeepgramSttClient HTTP réel + fakeable, TranscriptionController, validation audio)
      ✅ **PHASE 1 COMPLÈTE** — chat RAG sourcé + STT + outil de calcul déterministe
- [x] Front React chat vocal : UploadPanel → session → ChatPanel (question écrite/orale, réponse
      sourcée, suivi statut d'ingestion). api.js, useChat, useAudioRecorder, SourceList.
      Upload déclenche le pipeline (tenant `default` si absent). Vitest : 5 tests verts ; build OK.

### Phase 2 — Présentation express (Epic 3) · différenciateur
- [x] 3.1 — Sélection 3-6 slides (`SlideSelector` via Retriever) — tests verts
- [x] 3.2 — Narration JSON `[{slide_id, narration, duree}]` `[presentations]` — durée déterministe
      (`NarrationGenerator` Groq, `PresentationService`, API `POST /sessions/{uuid}/presentations`)
- [x] 3.3 — Défilement synchronisé voix : front `PresentationPlayer` (Reveal.js) + `useNarration`
      (SpeechSynthesis fr-FR) ; la slide avance à la fin de la narration. Build OK.
- [x] 3.4 — Narration affichée à l'écrit (sous la slide courante)
      Note : voix de narration = SpeechSynthesis navigateur (fr-FR) ; voix clonée (ElevenLabs) → Phase 4.
      ✅ **PHASE 2 COMPLÈTE** — présentation express de bout en bout (backend + front).

### Phase 3 — Débat du board (Epic 4)
- [x] 4.1 — Orchestrateur tour-par-tour, 4 personas `[debates, debate_turns]` — DebatePersonas + DebateOrchestrator + RunDebateJob
- [x] 4.2 — Chaque agent cite ses sources (Retriever → sources par réplique)
- [x] 4.3 — Vérification des chiffres : `FinancialVerifier` **déterministe** (chiffres confrontés à
      financial_metrics via StructuredDataService) ; chiffres réels injectés en contexte (anti-invention)
- [x] 4.4 — Arrêt propre : condition N tours (`stop_condition.max_rounds`)
      Tests : 4 (verifier verifie/a_verifier/année ; API débat 4 personas + 150 vérifié / 999 signalé + audit).
- [x] Front débat : `DebateView` (répliques par persona, sources, badges chiffres ✓/⚠), bouton
      « Débat du board » dans le chat, polling jusqu'à completed. Build OK.
      ✅ **PHASE 3 COMPLÈTE** — débat du board de bout en bout (backend + front).

### Phase 4 — Voix / gouvernance / session / compte rendu (Epics 2.3, 5, 6)
- [x] 6.1 — Consentement écrit avant clonage `[voice_consents]` — ConsentService + API
- [x] 6.2 — Modèle vocal isolé/révocable `[voice_models]` — VoiceModelService (clone gardé, révoque = delete provider + tombstone)
- [x] 6.3 — Conformité Loi 2013-450/ARTCI : registre (finalité, durée, base légale) sur voice_consents
      `TtsClient::deleteVoice` ajouté ; ElevenLabs clone/synthèse/delete (HTTP réel, fakeable). 7 tests verts.
- [ ] 2.3 — Réponse en voix clonée (P1, sous consentement)
- [ ] 5.1 — Épinglage de session `[pinned_items]`
- [ ] 5.2 — Export compte rendu DOCX/PDF
- [ ] 5.3 — Audit de toute réponse (couverture tous modes)
- [ ] 5.4 — Session éphémère + purge programmée (P1)

### Phase 5 (post-MVP) — Epic 7 DocumentProfile
- [ ] 7.1→7.6 — Extraction de l'abstraction au 2e type de document (PAS avant)

## Problèmes connus / décisions ouvertes
- Fiabilité function calling Groq à valider sur de vrais tableaux (sinon vérif chiffres → Claude, déjà défaut).
- Front React + Reveal.js vs Flutter Web : à trancher avant la Phase 2.
- Stratégie d'extraction `financial_metrics` (mapping périodes/unités) à éprouver sur un vrai BP.
