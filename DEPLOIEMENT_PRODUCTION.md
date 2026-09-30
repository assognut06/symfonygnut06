# Guide de déploiement - Gnut06 Symfony

## 🎯 Déploiement de la branche `main` vers gnut06.org

Ce guide détaille le processus complet de déploiement de l'application Symfony depuis GitHub vers le serveur de production.

## 📋 Prérequis

### Sur le serveur de production (gnut06.org)
- PHP 8.2 ou supérieur
- Composer installé
- MySQL/MariaDB
- Serveur web (Apache/Nginx)
- Git installé
- Accès SSH au serveur

### Variables d'environnement de production
```bash
APP_ENV=prod
APP_DEBUG=false
DATABASE_URL="mysql://username:password@127.0.0.1:3306/gnut06_prod"
MAILER_DSN="smtp://user:pass@smtp.example.com:587"
AZURE_CLIENT_ID="votre-azure-client-id"
AZURE_CLIENT_SECRET="votre-azure-client-secret"
GOOGLE_CLIENT_ID="votre-google-client-id.apps.googleusercontent.com"
GOOGLE_CLIENT_SECRET="votre-google-client-secret"
```

## 🚀 Processus de déploiement

### 1. **Connexion au serveur**
```bash
# Connexion SSH au serveur
ssh user@gnut06.org

# Aller dans le répertoire de l'application
cd /var/www/gnut06.org
```

### 2. **Sauvegarde avant déploiement**
```bash
# Créer une sauvegarde de l'application actuelle
sudo cp -r /var/www/gnut06.org /var/backups/gnut06-$(date +%Y%m%d-%H%M%S)

# Sauvegarde de la base de données
mysqldump -u username -p gnut06_prod > /var/backups/gnut06-db-$(date +%Y%m%d-%H%M%S).sql
```

### 3. **Récupération du code depuis GitHub**
```bash
# Si c'est le premier déploiement
git clone https://github.com/assognut06/symfonygnut06.git /var/www/gnut06.org

# Si l'application existe déjà
cd /var/www/gnut06.org
git fetch origin
git checkout main
git pull origin main
```

### 4. **Configuration de l'environnement**
```bash
# Copier le fichier d'environnement de production
cp .env .env.local

# Éditer les variables d'environnement pour la production
nano .env.local
```

**Contenu du fichier `.env.local` :**
```bash
APP_ENV=prod
APP_DEBUG=false
APP_SECRET=VotreSecretDeProd32Caracteres

# Base de données de production
DATABASE_URL="mysql://gnut06_user:motdepasse@127.0.0.1:3306/gnut06_prod"

# Configuration email (Mailjet production)
MAILER_DSN="mailjet+api://VOTRE_API_KEY:VOTRE_SECRET_KEY@default"

# OAuth Production (remplacer par vos vraies valeurs)
AZURE_CLIENT_ID="votre-azure-client-id"
AZURE_CLIENT_SECRET="votre-azure-client-secret"
GOOGLE_CLIENT_ID="votre-google-client-id.apps.googleusercontent.com"
GOOGLE_CLIENT_SECRET="votre-google-client-secret"

# URLs de redirection OAuth (IMPORTANT)
# Configurer dans Azure Portal et Google Console :
# - https://gnut06.org/connect/outlook/check
# - https://gnut06.org/connect/google/check
```

### 5. **Installation des dépendances**
```bash
# Installation des dépendances Composer (production uniquement)
composer install --no-dev --optimize-autoloader --no-interaction

# Vérifier que les dépendances sont installées
composer check-platform-reqs
```

### 6. **Configuration de la base de données**

#### Création de la base de données (si première fois)
```bash
# Connexion MySQL
mysql -u root -p

# Créer la base de données et l'utilisateur
CREATE DATABASE gnut06_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'gnut06_user'@'localhost' IDENTIFIED BY 'motdepasse_securise';
GRANT ALL PRIVILEGES ON gnut06_prod.* TO 'gnut06_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

#### Exécution des migrations
```bash
# Vérifier le statut des migrations
php bin/console doctrine:migrations:status --env=prod

# Exécuter les migrations
php bin/console doctrine:migrations:migrate --env=prod --no-interaction

# Vérifier que la base de données est à jour
php bin/console doctrine:schema:validate --env=prod
```

### 7. **Optimisation pour la production**
```bash
# Vider et réchauffer le cache de production
php bin/console cache:clear --env=prod --no-debug
php bin/console cache:warmup --env=prod --no-debug

# Installer les assets
php bin/console assets:install public --env=prod --no-debug

# Compiler les assets (si Webpack Encore)
npm install --production
npm run build
```

### 8. **Configuration des permissions**
```bash
# Définir les bonnes permissions
sudo chown -R www-data:www-data /var/www/gnut06.org
sudo chmod -R 755 /var/www/gnut06.org
sudo chmod -R 775 /var/www/gnut06.org/var
sudo chmod -R 775 /var/www/gnut06.org/public/uploads
```

## 🔧 Commandes de maintenance

### Mise à jour de l'application
```bash
# Script de mise à jour rapide
cd /var/www/gnut06.org
git pull origin main
composer install --no-dev --optimize-autoloader --no-interaction
php bin/console doctrine:migrations:migrate --env=prod --no-interaction
php bin/console cache:clear --env=prod --no-debug
sudo systemctl reload apache2
```

### Vérifications post-déploiement

Apache doit activer `mod_rewrite` et autoriser les règles du fichier
`public/.htaccess` (`AllowOverride All`). Ce fichier impose HTTPS pour
`gnut06.org`, ses sous-domaines et le serveur local `127.0.0.1`, y compris les fichiers statiques. La
redirection permanente 308 conserve la méthode HTTP, le chemin et les paramètres
et utilise le port HTTPS standard (443). L'accès HTTP local reste disponible.

Cette configuration suppose que TLS est terminé par Apache. Si un reverse proxy
termine TLS, appliquer la redirection sur ce proxy et adapter la règle Apache
avant le déploiement pour éviter une boucle ; ne pas faire confiance à un en-tête
`X-Forwarded-Proto` envoyé directement par le client.

```bash
# Attendu : 308 et Location: https://gnut06.org/login?next=%2Fprofil
curl -I 'http://gnut06.org/login?next=%2Fprofil'
# Attendu : réponse HTTPS sans nouvelle redirection vers la même URL
curl -I 'https://gnut06.org/login?next=%2Fprofil'
```

Le test PHPUnit dédié interroge Apache sur `http://127.0.0.1` :

```bash
docker compose exec -w /var/www/app symfony php vendor/bin/phpunit --no-configuration --bootstrap vendor/autoload.php tests/Infrastructure/HttpsRedirectTest.php
```

Le bootstrap applicatif est volontairement évité : ce test ne nécessite pas de
base de données. Il vérifie réellement le statut 308 pour une page, une URL avec
paramètres, un fichier statique et une requête POST.

```bash
# Vérifier la configuration Symfony
php bin/console about --env=prod

# Vérifier les routes
php bin/console debug:router --env=prod

# Vérifier la base de données
php bin/console doctrine:schema:validate --env=prod

# Tester les services
php bin/console debug:container --env=prod | grep -i oauth
```

## 🧪 Tests de validation

### 1. **Test de l'application**
```bash
# Test des pages principales
curl -I https://gnut06.org/
curl -I https://gnut06.org/login
curl -I https://gnut06.org/profil
```

### 2. **Test OAuth**
- Tester la connexion Google : `https://gnut06.org/connect/google`
- Tester la connexion Microsoft : `https://gnut06.org/connect/outlook`

### 3. **Test des fonctionnalités**
- Inscription d'un nouvel utilisateur
- Connexion classique
- Connexion OAuth (Google et Microsoft)
- Accès au profil utilisateur
- Fonctionnalités métier (dons, casques, etc.)

## 🚨 Configuration OAuth en production

### Azure Portal (Microsoft)
```
Application ID: [Votre Azure Client ID]
Redirect URIs:
  - https://gnut06.org/connect/outlook/check
  - https://www.gnut06.org/connect/outlook/check
```

### Google Cloud Console
```
Client ID: [Votre Google Client ID]
Redirect URIs:
  - https://gnut06.org/connect/google/check
  - https://www.gnut06.org/connect/google/check
```

## 📊 Monitoring et logs

### Logs à surveiller
```bash
# Logs Symfony
tail -f /var/www/gnut06.org/var/log/prod.log

# Logs Apache
tail -f /var/log/apache2/gnut06_error.log
tail -f /var/log/apache2/gnut06_access.log

# Logs système
tail -f /var/log/syslog
```

## ✅ Checklist de déploiement

- [ ] Code récupéré depuis GitHub (branche main)
- [ ] Variables d'environnement configurées (.env.local)
- [ ] Dépendances Composer installées (--no-dev)
- [ ] Base de données créée et configurée
- [ ] Migrations exécutées
- [ ] Cache vidé et réchauffé
- [ ] Permissions configurées
- [ ] Virtual Host Apache configuré
- [ ] SSL configuré (HTTPS)
- [ ] OAuth configuré (Azure + Google)
- [ ] Tests de validation effectués
- [ ] Monitoring en place

**Votre application Gnut06 est maintenant déployée en production !** 🎉
