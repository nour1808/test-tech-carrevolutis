# Candidater à une offre

Mini-stack : API Slim PHP + Next.js + MySQL. Le tout tourne via `docker compose up`.

## Démarrage rapide (≤5 min)
- Prérequis : Docker + Docker Compose.
- Lancer le tout : `docker compose up --build`.
- Frontend : http://localhost:3000  
  Naviguer vers `/offers/123` (n’importe quel id numérique).
- API : http://localhost:8080 (healthcheck sur `/health`).
- Base MySQL exposée sur `localhost:3306` (user `app` / password `app`).

## API
- `POST /apply`  
  Body JSON : `{ "offer_id": number, "email": string, "cv_url": string }`  
  - Validation : 400 pour JSON invalide, 422 pour champs manquants/mal formés (email, url, entier).  
  - Idempotence : contrainte unique `(offer_id, email)` + `INSERT IGNORE` ; rejouer la requête renvoie 200 avec le même enregistrement.
  - Réponse succès : `201` (créé) ou `200` (déjà existant) avec `{ application, message }`.
- `GET /stats`  
  Retourne `{"applies": N, "success_calls": X, "failed_calls": Y}`.
- `GET /health` pour vérification rapide.

## Observabilité
- Logs structurés JSON (Monolog vers stdout) : chaque tentative sur `/apply` log le statut, l’email, l’id d’offre et l’issue.
- Métriques simplifiées stockées en base (`metrics` table) : compteur `success` et `failed`, incrémentés à chaque appel `/apply`.

## Environnements & variables
- API : `DB_HOST` (par défaut `db` en conteneur, `127.0.0.1` hors Docker), `DB_PORT` (3306), `DB_NAME` (`carrevolutis`), `DB_USER` (`app`), `DB_PASSWORD` (`app`).
- Front : `NEXT_PUBLIC_API_BASE_URL` (par défaut `http://localhost:8080`, fixé à `http://api` en Docker).

## Dév local sans Docker
- API :  
  - `cd api && composer install`  
  - Lancer MySQL avec le schéma `api/migrations/001_init.sql`.  
  - `composer start` lance le serveur PHP sur `0.0.0.0:8080`.
- Front :  
  - `cd web && npm install`  
  - `npm run dev` (port 3000), puis configurer `NEXT_PUBLIC_API_BASE_URL`.

## Structure
- `api/` : Slim + Monolog, routes `/apply`, `/stats`, `/health`, migrations SQL.
- `web/` : Next.js page `/offers/[id]` avec formulaire + toast.
- `docker-compose.yml` : MySQL + API + Front, initialisation de la base avec `001_init.sql`.
