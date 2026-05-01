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
- **Exemple** : `22`

#### `GITHUB_TOKEN`
- **Description** : Token GitHub pour accéder au repository lors du git pull
- **Comment générer** : 
  1. GitHub → Settings → Developer settings → Personal access tokens → Tokens (classic)
  2. Générer un token avec la permission `repo`
  3. Copier le token généré
- **Important** : Ce token permet l'authentification pour le `git pull` sur le serveur

## Workflow de déploiement

Le déploiement se déclenche automatiquement :
- ✅ À chaque merge de pull request vers `develop`

### Étapes du déploiement :
1. 🔐 Connexion SSH au serveur de développement
2. 📁 Navigation vers `/var/www/html/gnut06_dev`
3. 📥 `git pull` des dernières modifications
4. 📁 Navigation vers le sous-dossier `app`
5. 📦 `composer install --no-dev --no-scripts` (dépendances PHP production)
6. 🔄 Optimisation de l'autoloader pour la production
7. 🗄️ Migrations de base de données (env: prod)
8. 📦 `npm install --production` (dépendances Node.js)
9. 🎨 `npm run build` (compilation des assets)
10. 🧹 Vidage du cache Symfony (env: prod)
11. 🔥 Réchauffement du cache (env: prod)

## Prérequis sur le serveur

Assurez-vous que votre serveur de développement dispose de :
- ✅ Git installé et configuré
- ✅ PHP 8.2+ avec Composer
- ✅ Node.js avec npm
- ✅ Accès SSH configuré
- ✅ Permissions appropriées pour l'utilisateur de déploiement
- ✅ Le projet déjà cloné dans `/var/www/html/gnut06_dev`
- ✅ **Accès GitHub configuré** (voir section Configuration GitHub)

## Configuration GitHub pour le déploiement

### Reconfiguration automatique (Utilisée dans notre workflow)
Le workflow reconfigure automatiquement l'URL du repository pour utiliser HTTPS avec le token :
```bash
git remote set-url origin "https://${GITHUB_TOKEN}@github.com/OWNER/REPO.git"
```

### Option 1 : Token GitHub (Recommandé)
```bash
# Sur le serveur, configurer Git avec un token
git config --global url."https://${GITHUB_TOKEN}@github.com/".insteadOf "https://github.com/"

# Ou directement dans le projet
cd /var/www/html/gnut06_dev
git remote set-url origin https://${GITHUB_TOKEN}@github.com/votre-username/votre-repo.git
```

**Secret GitHub à ajouter :**
- `GITHUB_TOKEN` : Token avec permissions `repo` (Settings → Developer settings → Personal access tokens)

### Option 2 : Clé SSH de déploiement
```bash
# Générer une clé SSH spécifique pour le déploiement
ssh-keygen -t rsa -b 4096 -C "deploy-gnut06-server" -f ~/.ssh/github_deploy

# Ajouter la clé publique comme "Deploy Key" dans GitHub
# (Settings → Deploy keys → Add deploy key)
cat ~/.ssh/github_deploy.pub

# Configurer SSH pour utiliser cette clé
echo "Host github.com
  HostName github.com
  User git
  IdentityFile ~/.ssh/github_deploy
  IdentitiesOnly yes" >> ~/.ssh/config
```

## Sécurité

- 🔒 Ne jamais committer de clés privées dans le code
- 🔒 Utiliser exclusivement les secrets GitHub
- 🔒 Configurer des permissions minimales pour l'utilisateur de déploiement
- 🔒 Considérer l'utilisation d'un agent SSH pour plus de sécurité