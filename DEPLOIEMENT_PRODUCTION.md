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
