#!/bin/sh

set -e

npm install

npm run cache:clear --force

symfony console cache:clear

symfony server:start --no-tls --port=8000 --dir=public & echo "Symfony server started"

npm run watch