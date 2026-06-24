---
description: Audit des règles de sûreté IA sur le code modifié
---

Passe en revue le code récemment modifié et vérifie le respect strict des règles de CLAUDE.md :

1. **Chiffres** : aucun montant/projection/ratio produit par le LLM. Tout vient de StructuredDataService (déterministe). Signale toute génération de chiffre par un prompt.
2. **Clés API** : aucune clé exposée côté front ; tous les appels IA passent par le backend.
3. **Voix** : aucun appel de clonage/synthèse de voix clonée sans consentement vérifié (config tts.require_consent_for_cloning).
4. **Audit** : toute réponse utilisateur trace question + sources + horodatage.
5. **Données perso** : rien dans les query strings ; sessions purgées.
6. **Paiement** : aucune référence à CinetPay.

Pour chaque manquement : fichier, ligne, et correction proposée.
