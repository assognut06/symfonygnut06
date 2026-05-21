# Audit Controllers / Forms / Entities (Symfony)

Date : 2026-05-21

## Méthodologie
- Revue statique ciblée des dossiers `app/src/Controller`, `app/src/Form`, `app/src/Entity`.
- Vérifications automatiques basiques :
  - comptage des classes ;
  - présence de `declare(strict_types=1)` ;
  - lint PHP syntaxique.
- Revue manuelle de fichiers à risque (webhooks, CRUD admin, entités centrales).

## Résumé exécutif
- **Niveau global : Moyen** (base saine, mais plusieurs points sécurité/qualité à corriger).
- **Forces** : typage strict présent partout, syntaxe PHP valide sur le périmètre audit.
- **Points critiques** : webhook exposé sans contrôle effectif de signature/IP, contrôleurs admin peu protégés au niveau code, formulaire legacy incohérent avec l’entité.

## Résultats des vérifications automatiques
- 48 controllers, 19 forms, 23 entities.
- `declare(strict_types=1)` présent dans tout le périmètre.
- Lint PHP : pas d’erreur de syntaxe détectée.

## Constat détaillé

### 1) Sécurité webhook HelloAsso (priorité haute)
**Fichier** : `app/src/Controller/NotificationController.php`

Constats :
- Le contrôle d’IP attendu existe mais est **commenté**.
- Le endpoint `POST /notification/callback` peut donc être invoqué sans cette barrière.
- Le code journalise et traite le payload, persiste des données, et envoie des emails.

Risque :
- Injection de faux événements (fraude logique, pollution BDD, spam email).

Recommandations :
1. Réactiver un contrôle de provenance (IP si contractualisée et stable).
2. Préférer une **vérification cryptographique** (HMAC/signature d’en-tête) si disponible côté HelloAsso.
3. Ajouter une protection anti-rejeu (timestamp + nonce/idempotency key).
4. Limiter le volume (rate limiting) sur cette route.

---

### 2) Contrôle d’accès des controllers admin (priorité haute)
**Périmètre** : `app/src/Controller/*Admin*Controller.php` + autres routes sensibles.

Constats :
- Très peu de garde-fous explicites (`#[IsGranted]` / `denyAccessUnlessGranted`) détectés dans les controllers.
- Les contrôleurs admin devraient idéalement expliciter leur politique d’accès au niveau classe ou méthode.

Risque :
- Si la conf sécurité évolue (firewall/access_control), certaines routes peuvent devenir exposées involontairement.

Recommandations :
1. Ajouter `#[IsGranted('ROLE_ADMIN')]` sur les controllers admin (ou `ROLE_SUPER_ADMIN` selon modèle).
2. Conserver access_control en config, mais **ne pas dépendre uniquement** de la config globale.
3. Ajouter des tests fonctionnels d’accès (anonyme / user / admin).

---

### 3) Formulaire legacy AssoRecommander1Type incohérent (priorité moyenne)
**Fichiers** :
- `app/src/Form/AssoRecommander1Type.php`
- `app/src/Controller/AssosCrudController.php`

Constats :
- Le CRUD utilise `AssoRecommander1Type`.
- Ce form expose des champs `CreatedAt` / `UpdatedAt` (majuscules) qui ne correspondent pas aux propriétés usuelles camelCase (`createdAt` / `updatedAt`).
- Le form semble généré/legacy, peu contraint (quasi pas de validation explicite).

Risque :
- Mauvais binding de champs, dette technique, risque d’incohérences de données et de maintenance.

Recommandations :
1. Remplacer `AssoRecommander1Type` par `AssoRecommanderType` (ou fusionner proprement).
2. Ne pas exposer `createdAt`/`updatedAt` en saisie utilisateur.
3. Ajouter contraintes Symfony (`NotBlank`, `Url`, `Length`, etc.) selon métier.

---

### 4) Cohérence de modélisation dans User (priorité moyenne)
**Fichier** : `app/src/Entity/User.php`

Constats :
- Propriété `$profile_picture` en snake_case, non typée explicitement, alors que le reste est majoritairement camelCase et typé.
- Incohérence de style avec les autres propriétés (`googleId`, `azureId`, etc.).

Risque :
- Lisibilité/maintenabilité réduites, risque d’erreurs lors de refactorings automatiques.

Recommandations :
1. Renommer vers `$profilePicture` (avec migration Doctrine si nécessaire).
2. Taper explicitement la propriété (`?string`).
3. Harmoniser conventions de nommage dans toutes les entités.

## Plan d’action conseillé (ordre)
1. **Sécuriser webhook** (signature + anti-rejeu + rate limit).
2. **Durcir accès controllers admin** (`#[IsGranted]` + tests fonctionnels).
3. **Nettoyer forms legacy** (`AssoRecommander1Type`).
4. **Standardiser entités** (naming/typing cohérents).

## Quick wins (1-2 jours)
- Ajouter attributs `#[IsGranted('ROLE_ADMIN')]` sur classes admin.
- Décommenter temporairement filtre IP webhook (en attendant signature).
- Supprimer champs techniques du form `AssoRecommander1Type`.
- Ouvrir tickets de refactor `User::$profile_picture`.
