#!/bin/sh

set -e

npm install

symfony server:start --no-tls --port=8000 --dir=public & echo "Symfony server started"
npm run watch 