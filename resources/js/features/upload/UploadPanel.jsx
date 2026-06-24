import React, { useState } from 'react';
import { uploadDocument } from '../../lib/api';

/** Téléversement d'un BP (PDF/PPTX). Déclenche l'ingestion côté backend. */
export default function UploadPanel({ onUploaded }) {
    const [file, setFile] = useState(null);
    const [title, setTitle] = useState('');
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState(null);

    const submit = async (event) => {
        event.preventDefault();
        if (!file) return;

        setBusy(true);
        setError(null);
        try {
            const document = await uploadDocument(file, title);
            onUploaded(document);
        } catch (e) {
            setError(e.message || "Le téléversement a échoué.");
        } finally {
            setBusy(false);
        }
    };

    return (
        <form onSubmit={submit} className="mx-auto max-w-md space-y-4">
            <div>
                <label className="mb-1 block text-sm font-medium text-slate-700">
                    Business plan (PDF ou PPTX)
                </label>
                <input
                    type="file"
                    accept=".pdf,.pptx"
                    onChange={(event) => setFile(event.target.files?.[0] ?? null)}
                    className="block w-full text-sm text-slate-600 file:mr-3 file:rounded-full file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:text-indigo-700 hover:file:bg-indigo-100"
                />
            </div>

            <div>
                <label className="mb-1 block text-sm font-medium text-slate-700">Titre (optionnel)</label>
                <input
                    type="text"
                    value={title}
                    onChange={(event) => setTitle(event.target.value)}
                    placeholder="BP Groupe 2026"
                    className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none"
                />
            </div>

            {error && <p className="text-sm text-red-600">{error}</p>}

            <button
                type="submit"
                disabled={!file || busy}
                className="w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white disabled:opacity-40"
            >
                {busy ? 'Téléversement…' : 'Téléverser et lancer l\'ingestion'}
            </button>
        </form>
    );
}
