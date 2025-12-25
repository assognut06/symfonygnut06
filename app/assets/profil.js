// Import CSS
import './styles/profil.css';

// Profile Page JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Affichage de la date du jour
    const today = new Date();
    const options = {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        weekday: 'long'
    };
    const formattedDate = today.toLocaleDateString('fr-FR', options);
    const dateElement = document.getElementById('current-date');

    if (dateElement) {
        dateElement.textContent = formattedDate;
        dateElement.setAttribute('aria-label', `Date du jour : ${formattedDate}`);
    }

    // Validation de formulaire accessible
    const form = document.querySelector('.needs-validation');
    if (form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();

                // Focus sur le premier champ invalide
                const firstInvalid = form.querySelector(':invalid');
                if (firstInvalid) {
                    firstInvalid.focus();
                }

                // Annonce pour lecteurs d'écran
                const announcement = document.createElement('div');
                announcement.className = 'visually-hidden';
                announcement.setAttribute('aria-live', 'assertive');
                announcement.textContent = 'Veuillez corriger les erreurs dans le formulaire';
                document.body.appendChild(announcement);

                setTimeout(() => {
                    document.body.removeChild(announcement);
                }, 3000);
            }

            form.classList.add('was-validated');
        }, false);
    }

    console.log('✅ Page profil accessible initialisée');
});
