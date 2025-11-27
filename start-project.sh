#!/bin/bash

echo "🚀 Démarrage du projet Symfony..."

# Se placer dans le bon répertoire
cd /home/gnut-06/Documents/symfonygnut06

echo "📦 Démarrage des conteneurs Docker..."
sudo docker compose up -d

echo "⏳ Attente que les conteneurs soient prêts..."
sleep 10

echo "🎵 Installation des dépendances Composer..."
sudo docker exec -it symfony_asso composer install --no-interaction

echo "📦 Installation des dépendances npm..."
sudo docker exec -it symfony_asso npm install

echo "🔨 Compilation des assets..."
sudo docker exec -it symfony_asso npm run build

echo "✅ Projet prêt !"
echo "🌐 Application : http://localhost:8000"
echo "🛢️  phpMyAdmin : http://localhost:8080"
echo "📧 Maildev : http://localhost:1080"