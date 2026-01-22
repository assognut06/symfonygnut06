// Script pour gérer l'affichage des formulaires 

    const btnPhysique = document.getElementById('btn-physique');
    const btnSociete = document.getElementById('btn-societe');
    const formPhysique = document.getElementById('form-physique');
    const formSociete = document.getElementById('form-societe');

    // Initialisation des boutons et des formulaires
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




    //Ajouter btn-neon et btn-outline-gradient aux boutons de navigation