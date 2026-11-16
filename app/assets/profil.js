import './styles/profil.css';

document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.account-page .needs-validation');
    if (!form) {
        return;
    }

    const fields = Array.from(form.querySelectorAll('.account-field input, .account-field select, .account-field textarea'));
    const summary = form.querySelector('#profile-errors');
    const errorList = summary.querySelector('[data-profile-errors]');
    const status = form.querySelector('[data-profile-status]');
    const pageTitle = document.title.replace(/^Erreur : /, '');
    const submittedWithErrors = form.dataset.profileUnsaved === 'true';
    const snapshot = () => JSON.stringify(fields.map(field => [field.name, field.value]));
    const originalValues = snapshot();

    const warnBeforeLeaving = event => {
        event.preventDefault();
        event.returnValue = true;
    };

    const updateDirtyState = () => {
        const dirty = submittedWithErrors || snapshot() !== originalValues;
        status.hidden = !dirty;
        window.removeEventListener('beforeunload', warnBeforeLeaving);
        if (dirty) {
            window.addEventListener('beforeunload', warnBeforeLeaving);
        }
    };

    const clientErrorMessage = field => {
        const label = field.labels[0].childNodes[0].textContent.trim();
        if (field.validity.valueMissing) {
            return `${label} : ce champ est obligatoire.`;
        }
        if (field.validity.patternMismatch) {
            return `${label} : veuillez respecter le format indiqué.`;
        }
        return `${label} : veuillez saisir une valeur valide.`;
    };

    const updateErrors = () => {
        errorList.querySelectorAll('li:not([data-form-error])').forEach(item => item.remove());
        fields.forEach(field => {
            const container = field.closest('.account-field');
            const serverErrors = container.querySelector('[data-profile-server-errors]');
            const clientError = container.querySelector('[data-profile-client-error]');
            const clientInvalid = form.classList.contains('was-validated') && !field.validity.valid;
            clientError.textContent = clientInvalid && !serverErrors.textContent.trim() ? clientErrorMessage(field) : '';
            const messages = Array.from(serverErrors.querySelectorAll('li'), item => item.textContent.trim());
            if (clientError.textContent) {
                messages.push(clientError.textContent);
            }
            const invalid = messages.length > 0;
            field.classList.toggle('is-invalid', invalid);
            field.setAttribute('aria-invalid', invalid ? 'true' : 'false');
            messages.forEach(message => {
                const item = document.createElement('li');
                const link = document.createElement('a');
                link.href = `#${field.id}`;
                link.textContent = message;
                item.appendChild(link);
                errorList.appendChild(item);
            });
        });
        summary.hidden = errorList.children.length === 0;
        document.title = summary.hidden ? pageTitle : `Erreur : ${pageTitle}`;
    };

    summary.addEventListener('click', event => {
        const link = event.target.closest('a');
        if (link) {
            const field = document.getElementById(link.hash.slice(1));
            if (field) {
                event.preventDefault();
                field.focus();
            }
        }
    });

    fields.forEach(field => {
        const onEdit = () => {
            field.closest('.account-field').querySelector('[data-profile-server-errors]').replaceChildren();
            updateErrors();
            updateDirtyState();
        };
        field.addEventListener('input', onEdit);
        field.addEventListener('change', onEdit);
    });

    form.addEventListener('submit', event => {
        form.classList.add('was-validated');
        updateErrors();
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
            summary.focus();
            updateDirtyState();
            return;
        }
        if (!event.defaultPrevented) {
            window.removeEventListener('beforeunload', warnBeforeLeaving);
        }
    });

    // Re-arm protection when returning with the browser's back/forward cache.
    window.addEventListener('pageshow', updateDirtyState);
    updateDirtyState();
    if (!summary.hidden) {
        summary.focus();
    }
});
