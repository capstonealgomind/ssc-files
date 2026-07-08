import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;
window.axios.defaults.withXSRFToken = true;

export function syncCsrfToken(token) {
    if (!token) {
        return;
    }

    const meta = document.head.querySelector('meta[name="csrf-token"]');
    if (meta) {
        meta.setAttribute('content', token);
    }

    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
}

const csrfToken = document.head.querySelector('meta[name="csrf-token"]');
if (csrfToken?.content) {
    syncCsrfToken(csrfToken.content);
}
