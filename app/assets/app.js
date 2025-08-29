// import './bootstrap.js';

/*
 * Welcome to your app's main JavaScript file!
 */

import * as bootstrap from 'bootstrap';
import './styles/app.scss';

import $ from 'jquery';
import 'bootstrap';
import 'select2';
import 'select2/dist/css/select2.min.css'; // ✅ CSS Select2
import 'bootstrap-icons/font/bootstrap-icons.css';

console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');

// ✅ Initialisation générale
$(function() {
    console.log('ready');
});

// ✅ Tooltips Bootstrap
document.addEventListener('DOMContentLoaded', function () {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// // ✅ Filtre email
// document.addEventListener('DOMContentLoaded', function () {
//     const input = document.getElementById('search-email');
//     const rows = document.querySelectorAll('tbody tr');

//     if (input) {
//         input.addEventListener('input', function () {
//             const query = input.value.toLowerCase();

//             rows.forEach(row => {
//                 const emailCell = row.querySelector('td:nth-child(2)');
//                 if (emailCell) {
//                     const email = emailCell.textContent.toLowerCase();
//                     row.style.display = email.includes(query) ? '' : 'none';
//                 }
//             });
//         });
//     }
// });

// document.addEventListener('DOMContentLoaded', () => {
//   const norm = s => (s || '')
//     .toString()
//     .toLowerCase()
//     .normalize('NFD')
//     .replace(/[\u0300-\u036f]/g, '');

//   // Tous les inputs marqués .table-live-filter
//   document.querySelectorAll('.table-live-filter').forEach(input => {
//     const tableSel = input.dataset.target;          // ex: "#tih-table"
//     const cols     = (input.dataset.cols || '').split(',')  // ex: "2,3,4"
//                         .map(s => parseInt(s.trim(), 10))
//                         .filter(n => !isNaN(n));

//     const table = tableSel ? document.querySelector(tableSel) : null;
//     if (!table) return;

//     const rows = Array.from(table.querySelectorAll('tbody tr'));

//     input.addEventListener('input', () => {
//       const q = norm(input.value);
//       rows.forEach(tr => {
//         const tds = tr.querySelectorAll('td');
//         if (!tds.length) return; // Ignore lignes sans cellules (récap, etc.)

//         // Si aucune colonne spécifiée, on cherche dans TOUTES les colonnes
//         const indices = cols.length ? cols : Array.from({length: tds.length}, (_, i) => i + 1);

//         const haystack = indices.map(i => tds[i - 1]?.textContent || '')
//                                 .map(norm)
//                                 .join(' ');
//         tr.style.display = haystack.includes(q) ? '' : 'none';
//       });

//       // Optionnel: masquer la pagination quand on tape
//       const pag = table.closest('.container, .mx-4, body')?.querySelector('.pagination-container');
//       if (pag) pag.classList.toggle('d-none', input.value.trim().length > 0);
//     });
//   });
// });


document.addEventListener('DOMContentLoaded', () => {
  const norm = s => (s || '').toString().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');

  // Filtre pour tableaux (colonnes configurables)
  document.querySelectorAll('.table-live-filter').forEach(input => {
    const tableSel = input.dataset.target;
    const cols = (input.dataset.cols || '').split(',').map(s => parseInt(s.trim(),10)).filter(n => !isNaN(n));
    const table = tableSel ? document.querySelector(tableSel) : null;
    if (!table) return;

    const rows = Array.from(table.querySelectorAll('tbody tr'));
    input.addEventListener('input', () => {
      const q = norm(input.value);
      rows.forEach(tr => {
        const tds = tr.querySelectorAll('td');
        if (!tds.length) return;
        const idxs = cols.length ? cols : Array.from({length: tds.length}, (_,i)=>i+1);
        const hay = idxs.map(i => tds[i-1]?.textContent || '').map(norm).join(' ');
        tr.style.display = hay.includes(q) ? '' : 'none';
      });
      table.closest('.container, .mx-4, body')?.querySelector('.pagination-container')?.classList.toggle('d-none', q.length>0);
    });
  });

  // Filtre pour listes/cartes (éléments avec [data-search])
  document.querySelectorAll('.list-live-filter').forEach(input => {
    const container = document.querySelector(input.dataset.target || '');
    if (!container) return;
    const items = Array.from(container.querySelectorAll('[data-search]'));
    input.addEventListener('input', () => {
      const q = norm(input.value);
      items.forEach(el => {
        el.style.display = norm(el.dataset.search).includes(q) ? '' : 'none';
      });
    });
  });
});

// ✅ Initialisation Select2
$(document).ready(function() {
    const select = $('#tih_competences');
    console.log("tih_competences exists ?", select.length);

    if (select.length) {
        select.select2({
            placeholder: "Sélectionnez vos compétences",
            width: '100%',
            height: '100%',
            allowClear: true
        });
    }
});
