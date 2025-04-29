document.addEventListener('DOMContentLoaded', function () {
    const adresseInput = document.querySelector('#benevole_adresse_1');
    const suggestionsContainer = document.createElement('div');
    suggestionsContainer.className = 'autocomplete-suggestions';
    suggestionsContainer.style.display = 'none'; // Masqué par défaut
    adresseInput.parentNode.appendChild(suggestionsContainer);

    let selectedAddress = null;

    adresseInput.addEventListener('input', async function () {
        const query = adresseInput.value.trim();

        if (query.length < 3 || selectedAddress === query) {
            suggestionsContainer.innerHTML = '';
            suggestionsContainer.style.display = 'none';
            return;
        }

        try {
            const url = `https://api-adresse.data.gouv.fr/search/?q=${encodeURIComponent(query)}&limit=5`;
            const response = await fetch(url);
            const data = await response.json();

            suggestionsContainer.innerHTML = '';

            if (!data.features || data.features.length === 0) {
                const noResult = document.createElement('div');
                noResult.textContent = "Aucune adresse trouvée.";
                noResult.className = 'suggestion-item text-muted';
                suggestionsContainer.appendChild(noResult);
                suggestionsContainer.style.display = 'block'; // Affiche quand même les suggestions vides
                return;
            }

            data.features.forEach(feature => {
                const props = feature.properties;

                const div = document.createElement('div');
                div.textContent = props.label;
                div.className = 'suggestion-item';
                div.style.padding = '8px';
                div.style.borderBottom = '1px solid #eee';
                div.style.cursor = 'pointer';

                div.addEventListener('click', function () {
                    selectedAddress = props.label;
                    adresseInput.value = props.label;

                    document.querySelector('#benevole_adresse_1').value = props.name || props.label;
                    document.querySelector('#benevole_code_postal').value = props.postcode || '';
                    document.querySelector('#benevole_ville').value = props.city || '';
                    document.querySelector('#benevole_pays').value = props.country || 'France';

                    suggestionsContainer.innerHTML = '';
                    suggestionsContainer.style.display = 'none'; // Disparaît après sélection
                });

                suggestionsContainer.appendChild(div);
            });

            suggestionsContainer.style.display = 'block'; // Affichage conditionnel

        } catch (error) {
            console.error("Erreur lors de la recherche d'adresse :", error);
            suggestionsContainer.innerHTML = '<div class="text-danger">Erreur de connexion.</div>';
            suggestionsContainer.style.display = 'block';
        }
    });

    // Fermer les suggestions si on clique ailleurs
    document.addEventListener('click', function (e) {
        if (!adresseInput.contains(e.target) && !suggestionsContainer.contains(e.target)) {
            suggestionsContainer.innerHTML = '';
            suggestionsContainer.style.display = 'none';
        }
    });
});