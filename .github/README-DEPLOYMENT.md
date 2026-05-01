# GitHub Actions - Configuration du Déploiement

## Secrets à configurer dans GitHub

Pour que le déploiement automatique fonctionne, vous devez configurer les secrets suivants dans votre repository GitHub :

### Comment ajouter les secrets :
1. Allez dans **Settings** → **Secrets and variables** → **Actions**
2. Cliquez sur **New repository secret**
3. Ajoutez chaque secret avec son nom et sa valeur

### Secrets requis :

#### `DEV_HOST`
- **Description** : Adresse IP ou nom de domaine de votre serveur de développement
- **Exemple** : `192.168.1.100` ou `dev.gnut06.com`

#### `DEV_USERNAME`
- **Description** : Nom d'utilisateur pour se connecter au serveur via SSH
- **Exemple** : `deploy` ou `ubuntu` ou `root`

#### `DEV_SSH_KEY`
- **Description** : Clé privée SSH pour se connecter au serveur (format PEM)
- **Comment générer** :
  ```bash
  # Sur votre machine locale
  ssh-keygen -t rsa -b 4096 -C "deploy-gnut06"
  # Copier le contenu de la clé privée (~/.ssh/id_rsa)
  cat ~/.ssh/id_rsa
  ```
- **Important** : Ajoutez la clé publique (`~/.ssh/id_rsa.pub`) dans `~/.ssh/authorized_keys` sur votre serveur

#### `DEV_PORT` (optionnel)
- **Description** : Port SSH du serveur (défaut : 22)
- **Exemple** : `22` ou `2222`

## Workflow de déploiement

Le déploiement se déclenche automatiquement :
- ✅ À chaque merge de pull request vers `develop`

### Étapes du déploiement :
1. 🔐 Connexion SSH au serveur de développement
2. 📁 Navigation vers `/var/www/html/gnut06_dev`
3. 📥 `git pull` des dernières modifications
4. 📁 Navigation vers le sous-dossier `app`
5. 📦 `composer install` (dépendances PHP)
6. 🗄️ Migrations de base de données
7. 📦 `npm install` (dépendances Node.js)
8. 🎨 `npm run build` (compilation des assets)
9. 🧹 Vidage du cache Symfony
10. 🔥 Réchauffement du cache

## Prérequis sur le serveur

Assurez-vous que votre serveur de développement dispose de :
- ✅ Git installé et configuré
- ✅ PHP 8.2+ avec Composer
- ✅ Node.js avec npm
- ✅ Accès SSH configuré
- ✅ Permissions appropriées pour l'utilisateur de déploiement
- ✅ Le projet déjà cloné dans `/var/www/html/gnut06_dev`

## Sécurité

- 🔒 Ne jamais committer de clés privées dans le code
- 🔒 Utiliser exclusivement les secrets GitHub
- 🔒 Configurer des permissions minimales pour l'utilisateur de déploiement
- 🔒 Considérer l'utilisation d'un agent SSH pour plus de sécurité