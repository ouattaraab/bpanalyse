# BP Explorer — Kit de démarrage

Assistant IA d'explication de documents complexes. Premier cas d'usage : le business plan (BP) en séminaire de direction. Architecture extensible à tout type de document (contrats, normes, rapports) via `DocumentProfile`.

## Ce que contient ce kit

```
bp-explorer/
├── CLAUDE.md                  # Guide projet (lu en premier par Claude Code)
├── BOOTSTRAP.md               # Prompts à donner à Claude Code pour démarrer
├── README.md                  # Ce fichier
├── docs/
│   ├── PRD.md                 # PRD BMAD (Markdown, lu par Claude Code)
│   ├── PRD_BP_Explorer_BMAD.docx  # PRD (version Word)
│   └── architecture.svg       # Diagramme d'architecture
├── memory-bank/               # Memory Bank BMAD (contexte persistant)
│   ├── projectbrief.md
│   ├── productContext.md
│   ├── systemPatterns.md
│   ├── techContext.md
│   ├── activeContext.md
│   └── progress.md
├── .claude/
│   └── commands/              # Commandes slash réutilisables
│       ├── implement-story.md
│       ├── new-document-profile.md
│       └── review-ai-safety.md
├── app/Services/AI/           # Interfaces des services IA (à implémenter)
│   ├── Contracts/
│   └── Providers/
├── app/Services/Document/     # Profils de document + données structurées
│   ├── Contracts/
│   └── Profiles/
└── config/
    └── ai.php                 # Config de routage LLM par feature
```

## Démarrage en 3 étapes

1. **Dézippe ce kit à la racine de ton repo** (ou crée le repo Laravel d'abord, puis dépose ces fichiers par-dessus).
2. **Ouvre le dossier dans Claude Code** : `claude` dans le terminal, ou ouvre le dossier dans ton IDE avec l'extension.
3. **Suis `BOOTSTRAP.md`** : il contient les prompts exacts, dans l'ordre, pour lancer l'analyse puis l'implémentation phase par phase.

## Prérequis (à installer)

- PHP 8.3+, Composer
- Node 20+, npm
- PostgreSQL 16 + extension `pgvector`
- Clés API : `GROQ_API_KEY`, `ANTHROPIC_API_KEY`, `DEEPGRAM_API_KEY`, `ELEVENLABS_API_KEY`
- (Souverain, optionnel) vLLM, faster-whisper, XTTS

## Règles non négociables (rappel)

- Clé API jamais exposée au front.
- Les chiffres ne sont jamais générés par le LLM — calcul déterministe.
- Clonage vocal uniquement avec consentement écrit, révocable.
- Jamais CinetPay. Paiement : Paystack / PawaPay / Wave / Orange Money / MTN MoMo.

Détails complets dans `CLAUDE.md`.
