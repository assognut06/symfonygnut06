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

// ✅ Filtre email
document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('search-email');
    const rows = document.querySelectorAll('tbody tr');

    if (input) {
        input.addEventListener('input', function () {
            const query = input.value.toLowerCase();

            rows.forEach(row => {
                const emailCell = row.querySelector('td:nth-child(2)');
                if (emailCell) {
                    const email = emailCell.textContent.toLowerCase();
                    row.style.display = email.includes(query) ? '' : 'none';
                }
            });
        });
    }
});

// ✅ Initialisation Select2
$(document).ready(function() {
    const select = $('#tih_competences');
    console.log("tih_competences exists ?", select.length);

    if (select.length) {
        select.select2({
            placeholder: "Sélectionnez vos compétences",
            width: '100%',
            allowClear: true
        });
    }
});
