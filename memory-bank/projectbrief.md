# Project Brief — BP Explorer

## Objectif
Assistant IA qui rend un business plan complexe (~150 pages, tableaux financiers) intelligible et interrogeable pour des dirigeants en séminaire de direction. Le chat n'est PAS le cœur ; la valeur est dans la présentation express auto-pilotée et le débat multi-agents.

## Cas d'usage d'origine
Un DG de filiale voulait faire comprendre l'importance de l'IA à ses pairs, autour de leur vrai BP (matière de travail du séminaire). L'outil a servi à challenger le BP — les agents ont trouvé des calculs faux.

## Portée
- MVP : un seul type de document (le BP).
- Extension : tout document complexe (contrats, normes, rapports, AO) via DocumentProfile (Epic 7), après validation du MVP.

## Livrables de référence
- docs/PRD.md (BMAD), docs/PRD_BP_Explorer_BMAD.docx, docs/architecture.svg
- CLAUDE.md (règles et conventions)

## Contraintes clés
- Chiffres jamais générés par le LLM (calcul déterministe).
- Clonage vocal consenti/révocable (Loi 2013-450 / ARTCI).
- Clé API jamais exposée au front.
- Paiement (si crédits) : Paystack/PawaPay/Wave/OM/MoMo. Jamais CinetPay.
