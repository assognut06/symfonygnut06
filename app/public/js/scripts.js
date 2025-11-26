// Script pour gérer l'affichage des formulaires 

document.addEventListener('DOMContentLoaded', function() {
    const btnPhysique = document.getElementById('btn-physique');
    const btnSociete = document.getElementById('btn-societe');
    const formPhysique = document.getElementById('form-physique');
    const formSociete = document.getElementById('form-societe');

    // Vérifier que tous les éléments existent avant de les utiliser
    if (!btnPhysique || !btnSociete || !formPhysique || !formSociete) {
        console.log('Éléments de formulaire non trouvés sur cette page');
        return;
    }

    // Initialisation des boutons et des formulaires
    btnPhysique.classList.add('btn-primary');
    btnPhysique.classList.remove('btn-outline-primary');
    btnSociete.classList.remove('btn-primary');
    btnSociete.classList.add('btn-outline-secondary');

    btnPhysique.addEventListener('click', () => {
        formPhysique.style.display = 'block';
        formSociete.style.display = 'none';
        btnPhysique.classList.add('btn-primary');
        btnPhysique.classList.remove('btn-outline-primary');
        btnSociete.classList.remove('btn-primary');
        btnSociete.classList.add('btn-outline-secondary');
    });

    btnSociete.addEventListener('click', () => {
        formSociete.style.display = 'block';
        formPhysique.style.display = 'none';
        btnSociete.classList.add('btn-primary');
        btnSociete.classList.remove('btn-outline-secondary');
        btnPhysique.classList.remove('btn-primary');
        btnPhysique.classList.add('btn-outline-primary');
    });
}); // Fin du DOMContentLoaded