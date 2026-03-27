#!/bin/bash

echo "🔐 Génération d'un certificat SSL personnalisé pour GNUT06..."

# Créer le répertoire SSL s'il n'existe pas
mkdir -p ./app/ssl

# Générer une clé privée RSA 4096 bits
openssl genrsa -out ./app/ssl/gnut06.key 4096

# Créer un fichier de configuration pour le certificat
cat > ./app/ssl/gnut06.conf << EOF
[req]
default_bits = 4096
prompt = no
default_md = sha256
distinguished_name = dn
req_extensions = v3_req

[dn]
C=FR
ST=Provence-Alpes-Côte d'Azur
L=Nice
O=GNUT06 Association
OU=Développement Web
CN=gnut06.local

[v3_req]
basicConstraints = CA:FALSE
keyUsage = nonRepudiation, digitalSignature, keyEncipherment
subjectAltName = @alt_names

[alt_names]
DNS.1 = localhost
DNS.2 = gnut06.local
DNS.3 = *.gnut06.local
IP.1 = 127.0.0.1
IP.2 = ::1
EOF

# Générer le certificat auto-signé
openssl req -new -x509 -key ./app/ssl/gnut06.key -out ./app/ssl/gnut06.crt -days 365 -config ./app/ssl/gnut06.conf -extensions v3_req

echo "✅ Certificat SSL généré !"
echo "📁 Fichiers créés :"
echo "   - ./app/ssl/gnut06.key (clé privée)"
echo "   - ./app/ssl/gnut06.crt (certificat)"
echo "   - ./app/ssl/gnut06.conf (configuration)"
echo ""
echo "🌐 Domaines supportés :"
echo "   - https://localhost"
echo "   - https://gnut06.local" 
echo "   - https://127.0.0.1"
echo ""
echo "⚠️  Pour utiliser gnut06.local, ajoutez cette ligne à /etc/hosts :"
echo "   127.0.0.1    gnut06.local"