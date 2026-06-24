// Client API de BP Explorer. Toutes les requêtes IA passent par le backend
// (aucune clé exposée côté front). Les routes /api sont stateless (pas de CSRF).

const BASE = '/api';

async function request(path, { method = 'GET', body, isForm = false } = {}) {
    const headers = { Accept: 'application/json' };
    if (!isForm) headers['Content-Type'] = 'application/json';

    const response = await fetch(`${BASE}${path}`, {
        method,
        headers,
        body: isForm ? body : body ? JSON.stringify(body) : undefined,
    });

    const payload = await response.json().catch(() => ({}));

    if (!response.ok) {
        const message = payload?.message || 'Une erreur est survenue.';
        throw Object.assign(new Error(message), { status: response.status, payload });
    }

    return payload.data ?? payload;
}

export function uploadDocument(file, title) {
    const form = new FormData();
    form.append('file', file);
    if (title) form.append('title', title);

    return request('/documents', { method: 'POST', body: form, isForm: true });
}

export function getDocument(documentId) {
    return request(`/documents/${documentId}`);
}

export function startSession(documentId) {
    return request(`/documents/${documentId}/sessions`, { method: 'POST' });
}

export function askQuestion(sessionUuid, question) {
    return request(`/sessions/${sessionUuid}/chat`, {
        method: 'POST',
        body: { question },
    });
}

export function createPresentation(sessionUuid, question) {
    return request(`/sessions/${sessionUuid}/presentations`, {
        method: 'POST',
        body: { question },
    });
}

export function transcribeAudio(sessionUuid, blob) {
    const form = new FormData();
    form.append('audio', blob, 'question.webm');

    return request(`/sessions/${sessionUuid}/transcribe`, {
        method: 'POST',
        body: form,
        isForm: true,
    });
}
