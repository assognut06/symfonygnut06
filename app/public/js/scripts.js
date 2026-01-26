// javascript
// Fichier : `app/public/js/scripts.js`

document.addEventListener('DOMContentLoaded', () => {
    const btnPhysique = document.getElementById('btn-physique');
    const btnSociete = document.getElementById('btn-societe');
    const formPhysique = document.getElementById('form-physique');
    const formSociete = document.getElementById('form-societe');

    if (!btnPhysique || !btnSociete || !formPhysique || !formSociete) {
        // éléments manquants : ne rien faire (évite les erreurs JS)
        return;
    }

    // Initialisation des classes visuelles
    btnPhysique.classList.add('btn-neon');
    btnPhysique.classList.remove('btn-outline-gradient');
    btnSociete.classList.remove('btn-neon');
    btnSociete.classList.add('btn-outline-gradient');

    btnPhysique.addEventListener('click', () => {
        formPhysique.style.display = 'block';
        formSociete.style.display = 'none';
        btnPhysique.classList.add('btn-neon');
        btnPhysique.classList.remove('btn-outline-gradient');
        btnSociete.classList.remove('btn-neon');
        btnSociete.classList.add('btn-outline-gradient');
    });

    btnSociete.addEventListener('click', () => {
        formSociete.style.display = 'block';
        formPhysique.style.display = 'none';
        btnSociete.classList.add('btn-neon');
        btnSociete.classList.remove('btn-outline-gradient');
        btnPhysique.classList.remove('btn-neon');
        btnPhysique.classList.add('btn-outline-gradient');
    });

    // --- Ajout : sablier de chargement sur soumission des formulaires donateur ---
    function attachLoadingOnSubmit(formContainer, formType) {
        if (!formContainer) return;
        const formElem = formContainer.querySelector('form');
        if (!formElem) return;

        formElem.addEventListener('submit', function (e) {
            // Appel à validateRecaptcha si disponible
            if (typeof validateRecaptcha === 'function') {
                const ok = validateRecaptcha(formType);
                if (!ok) {
                    e.preventDefault();
                    return;
                }
            }
            // Empêcher envoi multiple et afficher sablier
            e.preventDefault();
            const submitBtn = this.querySelector('.submit-btn');
            if (submitBtn && !submitBtn.disabled) {
                submitBtn.dataset.originalHtml = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-2" aria-hidden="true"></i>Enregistrement...';
                submitBtn.disabled = true;
            }
            // soumettre le formulaire après mise à jour du UI
            // petit délai pour laisser le changement visuel s'afficher
            setTimeout(() => this.submit(), 500);
        });
    }

    attachLoadingOnSubmit(formPhysique, 'physique');
    attachLoadingOnSubmit(formSociete, 'societe');
});
