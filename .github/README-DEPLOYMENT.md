# GitHub Actions - Configuration du Deploiement Production

## Secrets a configurer dans GitHub

Pour que le deploiement automatique en production fonctionne, configurez les secrets suivants dans le repository GitHub.

### Comment ajouter les secrets
1. Allez dans **Settings** -> **Secrets and variables** -> **Actions**
2. Cliquez sur **New repository secret**
3. Ajoutez chaque secret avec son nom et sa valeur

## Secrets utilises par le workflow

### `DEV_HOST`
- **Description** : Adresse IP ou nom de domaine du serveur de production
- **Exemple** : `prod.gnut06.com`

### `DEV_USERNAME`
- **Description** : Utilisateur SSH du serveur de production
- **Exemple** : `deploy` ou `ubuntu`

### `DEV_SSH_KEY`
- **Description** : Cle privee SSH (format PEM) pour la connexion depuis GitHub Actions
- **Important** : Ajouter la cle publique correspondante dans `~/.ssh/authorized_keys` sur le serveur

### `DEV_PORT` (optionnel)
- **Description** : Port SSH du serveur
- **Defaut** : `22`

### `GITHUB_TOKEN`
- **Description** : Token GitHub utilise pour authentifier `git fetch` sur le serveur
- **Important** : Le token doit avoir les permissions necessaires sur le repository

### `TEAMS_WEBHOOK_URL` (optionnel mais recommande)
- **Description** : URL de webhook Teams pour les notifications de debut, creation de tag et fin de deploiement

## Declenchement du deploiement production

Le workflow se declenche automatiquement :
- Quand une pull request vers `main` est fermee **et mergee**

Condition appliquee dans le job :
- `github.event.pull_request.merged == true`

## Etapes executees

1. Notification Teams de debut (si webhook configure)
2. Checkout du repository
3. Creation d'un tag de release incrementale au format `0.YYMM.N`
4. Push du tag vers `origin`
5. Notification Teams de creation du tag
6. Connexion SSH au serveur de production
7. Positionnement dans `/var/www/gnut06_prod`
8. Configuration `safe.directory` pour Git
9. `git fetch origin --tags` (avec auth via token si disponible)
10. `git checkout $RELEASE_TAG`
11. Positionnement dans `/var/www/gnut06_prod/app`
12. `composer install --no-dev --optimize-autoloader`
13. `composer dump-autoload --optimize --classmap-authoritative`
14. `php bin/console doctrine:migrations:migrate --no-interaction --env=prod`
15. `npm install`
16. `npm run build`
17. `php bin/console cache:clear --env=prod --no-debug`
18. `php bin/console cache:warmup --env=prod --no-debug`
19. Notification Teams de fin (toujours executee) avec statut du job

## Repertoire cible sur le serveur

- Projet : `/var/www/gnut06_prod`
- Application Symfony : `/var/www/gnut06_prod/app`

## Prerequis serveur production

- Git installe
- PHP 8.2+ et Composer
- Node.js et npm
- Acces SSH fonctionnel
- Permissions suffisantes pour l'utilisateur de deploiement
- Projet deja clone dans `/var/www/gnut06_prod`

## Notes importantes sur l'authentification GitHub

Le workflow n'ecrase pas l'URL du remote. Il utilise une configuration Git temporaire pendant le `fetch` :

```bash
git \
  -c "url.https://${GITHUB_TOKEN}@github.com/.insteadOf=git@github.com:" \
  -c "url.https://${GITHUB_TOKEN}@github.com/.insteadOf=https://github.com/" \
  fetch origin --tags
```

Cela evite de persister un token dans la configuration du depot sur le serveur.

## Securite

- Ne jamais committer de cles privees
- Utiliser uniquement les secrets GitHub Actions
- Limiter les permissions du compte SSH de deploiement
- Faire une rotation reguliere des cles et tokens
