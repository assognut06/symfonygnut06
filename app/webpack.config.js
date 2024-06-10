const Encore = require('@symfony/webpack-encore');

Encore
  .setOutputPath('public/build/') // Répertoire de sortie pour les assets compilés
  .setPublicPath('/build') // Chemin public des assets générés
  .addEntry('app', './assets/app.js') // Entrée principale de votre JS
  .enableSingleRuntimeChunk() // Autoriser un seul "chunk" d'exécution
  .cleanupOutputBeforeBuild() // Nettoyer le répertoire de sortie avant chaque build
  .enableSourceMaps(!Encore.isProduction()) // Activer les source maps en développement
  .enableVersioning(Encore.isProduction()); // Activer le versionnage des assets en production

// webpack .config.js
constnodeExternals = require('webpack-node-externals');

module.exports= {
 // ...autreconfiguration webpack
 externals : [
 nodeExternals(),
 { 'fs/promises': 'node-fs-extra' }, // Ajoutez cette ligne
 ],
};