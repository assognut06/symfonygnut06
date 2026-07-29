#!/bin/bash
echo "Arrêt des conteneurs"
docker compose down
if [[ "$1" == "optimize" ]]; then
    echo "Purge images"
    docker system prune -af
    docker volume prune -f
fi
if [[ "$1" == "optimize" ]] || [[ "$1" == "db" ]]; then
    sudo rm -rf mysql 
fi


sudo rm -Rf app/node_modules
sudo rm -Rf app/public/build/
sudo rm -Rf app/node_modules
sudo rm -Rf app/vendor
sudo rm -Rf app/var

ls app
echo "🚀 Démarrage du projet Symfony..."
echo "📦 Construction des conteneurs Docker..."
docker compose up --build -d

echo "⏳ Attente que les conteneurs soient prêts..."
sleep 10

echo "🎵 Installation des dépendances Composer..."
docker exec -it symfony_asso composer install --no-interaction

echo "📦 Installation des dépendances npm..."
docker exec -it symfony_asso npm install

echo "🔨 Compilation des assets..."
docker exec -it symfony_asso npm run build
docker exec -it symfony_asso php bin/console doctrine:migration:migrate --no-interaction
echo "✅ Projet prêt !"
echo "🌐 Application : https://127.0.0.1"
echo "🛢️  phpMyAdmin : http://localhost:8080"
echo "📧 Maildev : http://localhost:1080"
