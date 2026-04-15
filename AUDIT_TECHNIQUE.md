# AUDIT TECHNIQUE — PROJET SYMFONY *GNUT 06*

**Date :** 27 Décembre 2025

**Version auditée :** Symfony 7.1 / PHP 8.2  

---

## SYNTHÈSE

Le projet **Gnut 06** est une application Symfony 7.1 destinée à la gestion d’une association (adhésions, commandes, notifications, intégrations avec des services tiers).  
L’application est **fonctionnelle et exploitable en production**, mais l’audit met en évidence plusieurs **points de fragilité structurants**, en particulier sur les volets **sécurité**, **architecture** et **qualité du code**.

Les risques les plus critiques concernent :
- l’exposition de **secrets sensibles en clair** dans la configuration,
- la présence de **fichiers techniques accessibles publiquement** (ex. script `phpinfo`),
- une architecture très orientée contrôleurs, avec une **logique métier peu isolée**.

L’absence de **tests automatisés** et de **pipeline CI/CD** augmente fortement le risque lors des évolutions et déploiements.  
Une phase rapide de sécurisation, suivie d’un refactor progressif des services, est fortement recommandée afin d’améliorer la robustesse et la maintenabilité du projet.

---

## ÉVALUATION GLOBALE

| Domaine | Note / 10 | Commentaire |
| :--- | :---: | :--- |
| **Architecture** | 4/10 | Logique métier trop concentrée dans les contrôleurs, séparation des responsabilités insuffisante. |
| **Qualité du code** | 5/10 | Base saine, mais typage incomplet et dette technique visible. |
| **Sécurité** | 3/10 | Plusieurs failles critiques nécessitant une correction immédiate. |
| **Tests & CI/CD** | 0/10 | Aucun test automatisé ni intégration continue. |
| **Developer Experience** | 6/10 | Docker fonctionnel, mais peu d’outils de contrôle qualité. |
| **Performance** | 7/10 | Bon socle Symfony, mais risques de lenteur sur certaines requêtes et assets. |

---

## PRINCIPAUX PROBLÈMES IDENTIFIÉS

| # | Problème | Priorité | Effort | Correctif rapide |
| :-: | :--- | :---: | :---: | :---: |
| 1 | Secrets stockés en clair dans `.env` (`APP_SECRET`, clés API) | **P0** | S | Oui |
| 2 | Script `public/phpInfos.php` accessible publiquement | **P0** | S | Oui |
| 3 | Adresse IP HelloAsso codée en dur dans un contrôleur | **P1** | S | Oui |
| 4 | `client_id` HelloAsso et URLs legacy codés en dur dans Twig | **P1** | S | Oui |
| 5 | Absence totale de tests unitaires et d’intégration | **P1** | L | Non |
| 6 | Logique métier trop présente dans les contrôleurs | **P2** | M | Non |
| 7 | Typage incomplet (pas de `strict_types`, retours non typés) | **P2** | M | Non |
| 8 | Gestion des assets hétérogène (AssetMapper / Webpack / fichiers directs) | **P2** | M | Non |
| 9 | Utilisation de Guzzle au lieu du client HTTP Symfony | **P3** | S | Oui |
| 10 | Absence de linter et d’analyse statique | **P1** | S | Oui |

---

## 1. PRÉSENTATION GÉNÉRALE

- **Type d’application :** Application web de gestion associative.
- **Stack technique :** PHP 8.2, Symfony 7.1, MariaDB/MySQL, Docker (Apache).
- **Dépendances majeures :** Doctrine ORM, Twig, Symfony Mailer/Messenger, Guzzle, OAuth2 (KnpU), Mailjet, HelloAsso API.
- **Organisation du dépôt :**
  - `app/src/Controller` : volume important, responsabilités étendues.
  - `app/src/Service` : intégrations API et logique technique.
  - `app/public` : assets et points d’entrée HTTP (contient des éléments sensibles).
  - Présence de fichiers `.sql` à la racine, suggérant une gestion manuelle partielle de la base.

---

## 2. ARCHITECTURE & CONCEPTION

L’architecture repose sur un MVC Symfony classique, mais dérive vers une approche **“fat controllers”**.  
Les contrôleurs gèrent à la fois :
- la validation,
- la logique métier,
- les appels aux services externes,
- et parfois la persistance via Doctrine.

La configuration repose largement sur le fichier `.env`, sans distinction claire entre configuration et secrets.  
La journalisation est minimale : plusieurs blocs `try/catch` ne font que retourner un message générique, sans traçabilité exploitable.

---

## 3. QUALITÉ DU CODE & BONNES PRATIQUES

- **SOLID / DRY :** Plusieurs violations identifiées (duplication de logique, responsabilités multiples).
- **Typage :** Propriétés typées (PHP 8.2), mais retours de méthodes souvent absents ou trop génériques (`mixed`).
- **Dette technique :**
  - Instanciation manuelle de `GuzzleHttp\Client`.
  - Faible usage des abstractions Symfony (HttpClient, clients scopés).
- **Validation :** Formulaires Symfony correctement utilisés, mais validation métier parfois dispersée.

---

## 4. DESIGN PATTERNS & ANTI-PATTERNS

- **Patterns identifiés :**
  - Repository (Doctrine),
  - Services applicatifs pour les intégrations API,
  - Authenticator OAuth2.
- **Anti-patterns :**
  - Contrôleurs “fourre-tout”.
  - Configuration codée en dur.
- **Amélioration possible :**
  - Extraire la logique des webhooks vers un handler dédié.
  - Utiliser Messenger pour le traitement asynchrone des notifications.

---

## 5. SÉCURITÉ

- **Secrets :** `APP_SECRET` identique entre environnements, stocké en clair.
- **Exposition :** `public/phpInfos.php` constitue une fuite d’informations critique.
- **Sécurité réseau :** Vérification d’IP par comparaison directe, fragile en environnement proxy.
- **OAuth :** Implémentation correcte, mais manque de contrôles supplémentaires (ex. restrictions de domaine).
- **Fichiers publics :** Présence de fichiers texte contenant des URLs internes.

---

## 6. PERFORMANCE & SCALABILITÉ

- **Assets :** Mélange de solutions pouvant générer des conflits ou du surpoids.

---

## 7. TESTS & CI/CD

- **Tests :** Absents. Aucun test unitaire, fonctionnel ou d’intégration identifié.
- **CI/CD :** Aucun pipeline automatisé.
- **Risque :** Régressions silencieuses, notamment sur les parcours de paiement et d’adhésion.

---

## 8. DONNÉES & BASE DE DONNÉES

- **Modélisation :** Classique et cohérente.
- **Migrations :** Présentes mais incomplètes historiquement.

---

## 9. FRONTEND

- **Technologie :** Twig + Bootstrap.
- **UX / Performance :** Assets lourds (vidéos, modèles 3D) directement exposés dans `public`.

---

## 10. DOCUMENTATION & DX

- **Docker :** Image complète et fonctionnelle (PHP 8.2, Apache, Node).
- **README :** Présent mais peu détaillé.
- **Manques :**
  - Documentation d’architecture.
  - Règles de qualité et de revue de code.

---

## A FAIRE

### Phase 1 — Immédiat
- Supprimer `public/phpInfos.php`.
- Externaliser les secrets (Symfony Secrets ou variables d’environnement).
- Centraliser les IP, URLs et identifiants dans la configuration.
- Installer PHP-CS-Fixer et PHPStan (niveau bas initial).

### Phase 2 — Rapidement
- Migrer Guzzle vers le HttpClient Symfony.
- Extraire la logique métier hors des contrôleurs.
- Mettre en place des tests d’intégration sur les flux critiques.
- Unifier la stratégie de gestion des assets.

### Phase 3 — à moyen terme
- Compléter le typage (ajout de `strict_types`, retours typés).
- Mise en place d’une CI (GitHub Actions ou équivalent).
- Optimisation de la base (indexation, requêtes).
- Supervision des webhooks et alerting.

---

