# BP Explorer

[![CI](https://github.com/ouattaraab/bpanalyse/actions/workflows/ci.yml/badge.svg)](https://github.com/ouattaraab/bpanalyse/actions/workflows/ci.yml)

Assistant IA qui rend un **business plan** complexe (~150 pages, tableaux financiers) intelligible et
interrogeable pour des dirigeants en séminaire de direction. Le chat n'est pas le cœur : la valeur est
dans la **présentation express auto-pilotée** et le **débat multi-agents** — qui traque les chiffres faux.

> MVP complet (Phases 0→4). Backend Laravel 11 + front React 18. Pile IA souveraine (locale) ou cloud,
> commutable par configuration. Tests : **98 backend (Pest) + 6 front (Vitest)**.

## Les capacités

1. **Chat vocal RAG** — question (oral/écrit) → réponse **sourcée** (slide/section). Les chiffres ne
   sont jamais inventés : ils proviennent d'un outil de calcul déterministe (`FinancialQueryService`).
2. **Présentation express** *(différenciateur)* — à partir d'une question : sélection de 3-6 slides +
   narration synchronisée (Reveal.js + voix navigateur).
3. **Débat du board** *(différenciateur)* — 4 personas (DG, Investisseur, DAF, Commerciale) débattent
   tour par tour ; chaque réplique est sourcée et ses **chiffres sont vérifiés** contre les données.
4. **Compte rendu one-shot** — épinglage des réponses, export **DOCX/PDF**, puis purge de la session.

Plus la **gouvernance du clonage vocal** : consentement écrit, limité, révocable (Loi 2013-450 / ARTCI) ;
révocation = suppression du modèle vocal.

## Stack

| Couche | Cloud (défaut) | Souverain (local) |
|---|---|---|
| Backend | Laravel 11 (PHP 8.3+), API REST + Reverb (WebSocket) | — |
| Front | React 18 + Vite + Tailwind 3 + Reveal.js | — |
| Base | PostgreSQL 16 + `pgvector` | idem |
| LLM (routage par feature) | Groq (chat/présentation/résumé), Claude (débat/vérif chiffres) | Qwen 2.5 / vLLM |
| STT | Deepgram Nova | faster-whisper |
| TTS (voix clonée) | ElevenLabs | XTTS |
| Embeddings | bge-m3 (process Python local) | idem |
| Parsing | Docling (process Python local) | idem |

Le **routage LLM par feature** est dans `config/ai.php`. `AI_SOVEREIGN=true` bascule tout sur la pile
auto-hébergée via une seule variable.

## Démarrage

### Prérequis
PHP 8.3+, Composer, Node 20+, PostgreSQL 16 + `pgvector`, Python 3 (Docling + bge-m3 locaux).

```bash
# Dépendances
composer install
npm install

# Outils Python souverains (parsing + embeddings)
python3 -m venv tools/docling/.venv     && tools/docling/.venv/bin/pip install -r tools/docling/requirements.txt
python3 -m venv tools/embeddings/.venv  && tools/embeddings/.venv/bin/pip install -r tools/embeddings/requirements.txt

# Base + config
cp .env.example .env && php artisan key:generate
createdb bp_explorer && psql -d bp_explorer -c 'CREATE EXTENSION IF NOT EXISTS vector;'
php artisan migrate

# Clés cloud (optionnel — la pile locale fonctionne sans) : renseigner
# GROQ_API_KEY, ANTHROPIC_API_KEY, DEEPGRAM_API_KEY, ELEVENLABS_API_KEY dans .env
```

### Lancer
```bash
php artisan serve        # API + front (assets buildés)
php artisan queue:work   # pipeline d'ingestion + jobs IA (NE PAS lancer l'ingestion en sync)
php artisan reverb:start # WebSocket (temps réel)
npm run dev              # front en dev (HMR)
```

### Ingérer un BP
```bash
php artisan bp:ingest mon_bp.pdf      # met les jobs en file
php artisan queue:work                 # parse (Docling) → chunk → embeddings → extraction financière
```
> ⚠ Lancer l'ingestion via la file (`queue:work`), **pas** en `QUEUE_CONNECTION=sync` : on éviterait
> d'empiler Docling et bge-m3 (torch) en mémoire dans un seul process.

## API (extrait)

```
POST   /api/documents                        Téléverser un BP (déclenche l'ingestion)
GET    /api/documents/{id}                   Statut d'ingestion
POST   /api/documents/{id}/sessions          Démarrer une session
POST   /api/sessions/{uuid}/chat             Question écrite → réponse sourcée
POST   /api/sessions/{uuid}/transcribe       Question orale (STT) → texte
POST   /api/sessions/{uuid}/presentations    Présentation express
POST   /api/sessions/{uuid}/debates          Lancer un débat du board
GET    /api/debates/{id}                     Suivi du débat (polling)
POST   /api/sessions/{uuid}/pins             Épingler une réponse
GET    /api/sessions/{uuid}/export?format=   Export DOCX/PDF du compte rendu
GET    /api/sessions/{uuid}/audit            Trace d'audit
POST   /api/tenants/{id}/voice-consents      Consentement de clonage vocal
POST   /api/voice-consents/{id}/voice-model  Créer un modèle vocal (sous consentement)
POST   /api/interactions/{id}/voice          Réponse en voix clonée (gardée)
```

## Règles non négociables (voir `CLAUDE.md`)

- **Clé API jamais exposée au front** — tous les appels IA passent par le backend.
- **Chiffres jamais générés par le LLM** — calcul déterministe (`FinancialQueryService`).
- **Clonage vocal consenti/révocable** (biométrie) — aucun clonage sans consentement valide.
- **Toute réponse auditée** (question, sources, modèle, horodatage).
- **Sessions éphémères** — purge programmée (`sessions:purge`). Paiement : jamais CinetPay.

## Tests

```bash
./vendor/bin/pest    # backend (98) — base de test pgsql dédiée bp_explorer_testing
npm run test         # front (Vitest)
```

## Structure

```
app/
  Console/Commands/        bp:ingest, sessions:purge, bp:validate-cloud
  Http/Controllers/        Document, Chat, Transcription, Presentation, Debate, Pin, Export, Audit, Consent, VoiceModel, VoiceAnswer
  Jobs/                    Parse, Chunk, EmbedChunks, ExtractFinancials, RunDebate
  Models/                  Document, DocumentSlide, Chunk, FinancialTable/Metric, ExplorerSession, Interaction, AuditLog, Presentation, Debate/Turn, PinnedItem, VoiceConsent/Model
  Services/
    AI/                    Managers (routage par feature) + Providers (Groq, Anthropic, Deepgram, ElevenLabs, bge-m3...)
    Ingestion/             DoclingParser, SemanticChunker, FinancialTableExtractor, IngestionPipeline
    Rag/                   Retriever, SourceFormatter, RagService
    Document/              FinancialQueryService (StructuredDataService)
    Presentation/ Debate/ Voice/ Session/ Audit/ Export/
config/ai.php              Routage LLM par feature + providers
tools/{docling,embeddings} Process Python souverains (venvs gitignorés)
resources/js/              Front React (features/, lib/api.js, components/App)
```

## Extension (post-MVP)

BP Explorer est un cas particulier d'un moteur générique d'explication de documents (contrats, normes,
rapports…). L'abstraction `DocumentProfile` (Epic 7) est cadrée mais **délibérément non implémentée** :
elle sera extraite au moment d'ajouter le 2e type de document, pas avant.
