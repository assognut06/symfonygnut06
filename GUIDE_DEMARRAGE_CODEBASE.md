# Guide néophyte — comprendre la base de code GNUT06

Ce document t’aide à **te repérer rapidement** dans le projet, même si tu découvres Symfony/PHP.

## 1) Vue d’ensemble en 30 secondes

- Le dépôt contient une application web de l’association GNUT06.
- Le cœur applicatif est dans le dossier `app/`.
- C’est une application **Symfony 7.1** (backend PHP) avec **Twig** pour les vues et un peu de JS/CSS côté front.
- Le projet tourne en local avec **Docker Compose**.

## 2) Structure générale du dépôt

## Racine du projet

- `README.md` : installation, démarrage et import des données.
- `docker-compose.yaml` : services Docker (app, DB, etc.).
- `DEPLOIEMENT_PRODUCTION.md` : notes de déploiement prod.
- `vhosts/` : configuration web serveur.

## Dossier `app/` (le plus important)

- `src/` : code PHP métier (contrôleurs, entités, services, sécurité…).
- `templates/` : vues Twig (pages HTML rendues serveur).
- `config/` : configuration Symfony (routes, sécurité, doctrine, mail, messenger…).
- `assets/` : JS/CSS source.
- `public/` : point d’entrée web (`index.php`), fichiers publics (images, uploads, css compilés).
- `migrations/` : migrations de base de données Doctrine.
- `composer.json` : dépendances PHP.
- `package.json` : dépendances front (Node/NPM).

## 3) Le “flux” d’une page (modèle mental utile)

Dans Symfony, une requête suit souvent ce chemin :

1. L’utilisateur ouvre une URL (ex: `/contact`).
2. Une route Symfony pointe vers un contrôleur dans `src/Controller/...`.
3. Le contrôleur appelle éventuellement un service (`src/Service/...`) ou un repository (`src/Repository/...`) pour récupérer/traiter des données.
4. Le contrôleur renvoie une vue Twig dans `templates/...`.
5. Les assets (CSS/JS) sont chargés depuis `assets/` (compilés puis servis via `public/`).

Si tu comprends ce trajet, tu peux déjà déboguer 70% des cas.

## 4) Dossiers clés à apprendre en premier

## `app/src/Controller`

- C’est la **porte d’entrée HTTP**.
- Tu y trouves les actions par page/fonctionnalité (`HomeController`, `ContactController`, `TihSearchController`, etc.).
- Premier réflexe quand une page bug : trouver le contrôleur correspondant.

## `app/src/Entity` + `app/src/Repository`

- `Entity` = structure des données (tables Doctrine/ORM).
- `Repository` = requêtes d’accès aux données.
- Quand un contenu de page est faux ou incomplet, c’est souvent ici que ça se joue.

## `app/src/Service`

- Logique métier réutilisable (API externes, emails, pagination, etc.).
- Bonne pratique : garder les contrôleurs minces, déplacer la logique ici.

## `app/templates`

- Couches visuelles Twig.
- `base.html.twig` + `_partials/` structurent le layout global.
- Les dossiers nommés par feature (`contact/`, `profil/`, `admin/...`) aident à la navigation.

## `app/config`

- `routes.yaml` : routage.
- `packages/security.yaml` : auth/permissions.
- `packages/doctrine.yaml` : DB/ORM.
- `packages/messenger.yaml`, `mailer.yaml` : messages et emails.

## 5) Particularités de cette base

Le projet ne suit pas uniquement une structure MVC “simple”.
Tu verras aussi des couches inspirées de la Clean Architecture :

- `src/Application/...` (DTO, Commands/Queries, handlers)
- `src/Domain/...` (interfaces, value objects, événements métier)
- `src/Infrastructure/...` (implémentations techniques)

Conseil néophyte :
- ne cherche pas à tout maîtriser d’un coup ;
- commence par la triade **Controller → Service/Repository → Template** ;
- puis explore Application/Domain/Infrastructure quand tu es à l’aise.

## 6) Sécurité, comptes et authentification

Repères utiles :

- `config/packages/security.yaml` : règles de sécurité.
- `src/Security/` : authentificateurs (Google, Outlook, etc.).
- `src/Controller/LoginController.php`, `RegistrationController.php`, `ResetPasswordController.php` : parcours utilisateur.

## 7) Données et migrations

- Les changements de schéma DB sont versionnés dans `app/migrations/`.
- Les classes `DataFixtures` dans `src/DataFixtures/` servent à charger des données de test/démo.
- Quand tu modifies une Entity, pense migration + éventuelles fixtures.

## 8) Frontend (niveau débutant)

- Entrées JS/CSS principales dans `app/assets/`.
- Twig inclut ces assets pour l’affichage.
- Pour un bug visuel : regarder d’abord `templates/...` puis `assets/styles/...`.

## 9) Commandes incontournables pour apprendre en pratique

Depuis la racine du dépôt :

```bash
docker compose up -d
```

Puis dans le conteneur applicatif (selon README) :

```bash
docker exec -it symfony_asso composer install --no-interaction
docker exec -it symfony_asso npm install
docker exec -it symfony_asso npm run build
docker exec -it symfony_asso php bin/console doctrine:migration:migrate
```

Commandes Symfony utiles :

```bash
php bin/console debug:router
php bin/console debug:container
php bin/phpunit
```

## 10) Parcours d’apprentissage conseillé (ordre)

1. **Lire `README.md`** et démarrer le projet localement.
2. Ouvrir une page simple (`/`) puis suivre son contrôleur + template.
3. Explorer une feature CRUD (ex: `assos_crud`) pour comprendre le cycle complet.
4. Étudier `Entity` + `Repository` liés à cette feature.
5. Ajouter une petite modif UI dans Twig/CSS.
6. Ensuite seulement : approfondir `Application/Domain/Infrastructure`.

## 11) Check-list “je suis perdu, je commence où ?”

- Quelle URL pose problème ?
- Quelle route la gère ? (`debug:router`)
- Quel contrôleur est appelé ?
- Quel template est rendu ?
- Quelles données sont chargées (Repository/Service) ?
- Y a-t-il une règle de sécurité qui bloque ?

---

Si tu veux, prochaine étape : je peux te faire une **visite guidée d’une fonctionnalité réelle** (ex: Contact, Profil, ou Don), fichier par fichier, pour te montrer comment lire le code sans te noyer.
