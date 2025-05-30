window._ = require('lodash');

/**
 * Importiamo axios HTTP library
 */
window.axios = require('axios');
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Token CSRF - configurazione migliorata
 */
function setupCSRFToken() {
    let token = document.head.querySelector('meta[name="csrf-token"]');

    if (token) {
        window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
        console.log('CSRF token configurato:', token.content.substring(0, 10) + '...');
    } else {
        console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token');

        // Prova a ottenere il token da altre fonti
        const bodyToken = document.querySelector('input[name="_token"]');
        if (bodyToken) {
            window.axios.defaults.headers.common['X-CSRF-TOKEN'] = bodyToken.value;
            console.log('CSRF token ottenuto da input hidden');
        }
    }
}

// Configura il token all'avvio
setupCSRFToken();

// Riconfigura il token se la pagina viene aggiornata
document.addEventListener('DOMContentLoaded', setupCSRFToken);
