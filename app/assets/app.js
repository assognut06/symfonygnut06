// import './bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import * as bootstrap from 'bootstrap';
import './styles/app.scss';
import $ from 'jquery'; // Utilisation de l'import au lieu de require
import 'bootstrap';
import 'bootstrap-icons/font/bootstrap-icons.css';

$(function() {
    console.log('ready');
});

// import '@symfony/autoimport';

// Registers Stimulus controllers from controllers.json and in the controllers/ directory
// export const app = startStimulusApp(require.context('./controllers', true, /\.(j|t)sx?$/));

console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');

document.addEventListener('DOMContentLoaded', function () {
  const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
  });
});