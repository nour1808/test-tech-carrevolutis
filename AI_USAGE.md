# AI Usage

Outils IA utilisés

ChatGPT (GPT-5.1)

Utilisé pour :
• Générer la structure complète du projet (API + Front + Docker).
• Proposer le code des endpoints /apply et /stats.
• Générer le formulaire Next.js et le composant “Toast”.
• Écrire un docker-compose.yml cohérent.
• Rédiger le README initial.
• Rédiger ce fichier AI_USAGE.md.

GitHub Copilot

Utilisé ponctuellement pour :
• Compléter automatiquement quelques imports.
• Proposer de petites corrections syntaxiques dans les fichiers JS/PHP.
• Générer des snippets simples lors de l’écriture du code (ex : déclarations d’états React, boucles PHP).

⸻

🧠 Prompts clés utilisés

Voici les prompts ayant réellement influencé la génération du code :

Prompt 1 — Spécification complète

“Construire une mini-fonctionnalité : ‘Candidater à une offre’ avec API Slim (POST /apply, GET /stats, idempotence, validation JSON), front Next.js avec un formulaire /offers/[id], toasts, logs JSON, métriques simples, docker-compose, README, AI_USAGE.md, tests, limitations.”

Prompt 2 — Génération du code API

“Écris le code complet pour un endpoint POST /apply en PHP Slim avec : validation, erreurs 422/400/500, idempotence via contrainte UNIQUE (offer_id + email), métriques stockées en base, logs JSON, réponse structurée.”

Prompt 3 — Génération de la page Next.js

“Crée une page Next.js /offers/[id] avec un formulaire (email + cv_url), appel fetch POST /apply, gestion loading, toast succès / erreur.”

Prompt 4 — Docker & DX

“Génère un docker-compose.yml complet avec API + front + PostgreSQL + migrations automatiques, et deux Dockerfiles compatibles.”

Code généré (extraits principaux) :
- `api/src/Controller/ApplicationController.php`, `api/src/Repository/ApplicationRepository.php`, `api/src/Validator/ApplicationValidator.php`, `api/src/Service/LoggerFactory.php`, routing dans `api/src/app.php`.
- Endpoint liste `/applications` + page `web/pages/applications.js`.
- Tests : `api/tests/ApplicationValidatorTest.php`, `ApplicationControllerTest.php`, `ApplicationsRouteTest.php`.
- Devops : mise à jour `docker-compose.yml` (volumes api/web, phpMyAdmin, commande dev).

Ce qui a été corrigé ou simplifié manuellement :
- Ajustements d’URL API pour éviter les hostnames Docker côté navigateur.
- Mise à jour des montages volumes pour recharger le code sans rebuild.
- Correction des erreurs de routing (405) en forçant le code local dans le conteneur API.
- Ajustement du code Slim pour éviter les warnings sur $response->getBody()->write().
- Correction des imports Slim dans index.php.
- Correction du test PHPUnit (ChatGPT avait inversé un assert).
- Vérification et correction de quelques erreurs de syntaxe HTML dans le formulaire.

Tests à exécuter :
- Backend : `cd api && ./vendor/bin/phpunit`
- Frontend : lint/tests non fournis dans la base, à lancer si ajoutés.

