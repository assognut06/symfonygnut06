function initAdresseAutocomplete(options) {
    const adresseInput = document.querySelector(options.inputSelector);
    if (!adresseInput) return;

    const suggestionsContainer = document.createElement('div');
    suggestionsContainer.className = 'autocomplete-suggestions';
    suggestionsContainer.style.display = 'none';
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
                suggestionsContainer.style.display = 'block';
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

                    if (options.fields.adresse_1)
                        document.querySelector(options.fields.adresse_1).value = props.name || props.label;
                    if (options.fields.code_postal)
                        document.querySelector(options.fields.code_postal).value = props.postcode || '';
                    if (options.fields.ville)
                        document.querySelector(options.fields.ville).value = props.city || '';
                    if (options.fields.pays)
                        document.querySelector(options.fields.pays).value = props.country || 'France';

                    suggestionsContainer.innerHTML = '';
                    suggestionsContainer.style.display = 'none';
                });

                suggestionsContainer.appendChild(div);
            });

            suggestionsContainer.style.display = 'block';

        } catch (error) {
            console.error("Erreur lors de la recherche d'adresse :", error);
            suggestionsContainer.innerHTML = '<div class="text-danger">Erreur de connexion.</div>';
            suggestionsContainer.style.display = 'block';
        }
    });

    // Fermeture au clic extérieur
    document.addEventListener('click', function (e) {
        if (!adresseInput.contains(e.target) && !suggestionsContainer.contains(e.target)) {
            suggestionsContainer.innerHTML = '';
            suggestionsContainer.style.display = 'none';
        }
    });
}

// Initialisation pour chaque formulaire
document.addEventListener('DOMContentLoaded', function () {
    initAdresseAutocomplete({
        inputSelector: '#benevole_adresse_1',
        fields: {
            adresse_1: '#benevole_adresse_1',
            code_postal: '#benevole_code_postal',
            ville: '#benevole_ville',
            pays: '#benevole_pays'
        }
    });

    initAdresseAutocomplete({
        inputSelector: '#personne_physique_adresse_1',
        fields: {
            adresse_1: '#personne_physique_adresse_1',
            code_postal: '#personne_physique_code_postal',
            ville: '#personne_physique_ville',
            pays: '#personne_physique_pays'
        }
    });

    initAdresseAutocomplete({
        inputSelector: '#societe_adresse_1',
        fields: {
            adresse_1: '#societe_adresse_1',
            code_postal: '#societe_code_postal',
            ville: '#societe_ville',
            pays: '#societe_pays'
        }
    });
});