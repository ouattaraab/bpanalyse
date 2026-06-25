import React, { useRef, useState } from 'react';
import { uploadDocument } from '../../lib/api';

/** Téléversement d'un BP (PDF/PPTX) — glisser-déposer, déclenche l'ingestion. */
export default function UploadPanel({ onUploaded }) {
    const [file, setFile] = useState(null);
    const [title, setTitle] = useState('');
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState(null);
    const [dragging, setDragging] = useState(false);
    const inputRef = useRef(null);

    const choose = (f) => {
        if (!f) return;
        setError(null);
        setFile(f);
        if (!title) setTitle(f.name.replace(/\.[^.]+$/, ''));
    };

    const submit = async (event) => {
        event.preventDefault();
        if (!file) return;
        setBusy(true);
        setError(null);
        try {
            onUploaded(await uploadDocument(file, title));
        } catch (e) {
            setError(e.message || 'Le téléversement a échoué.');
            setBusy(false);
        }
    };

    const human = file ? `${(file.size / 1024 / 1024).toFixed(1)} Mo` : null;

    return (
        <form onSubmit={submit} className="max-w-xl">
            <button
                type="button"
                onClick={() => inputRef.current?.click()}
                onDragOver={(e) => {
                    e.preventDefault();
                    setDragging(true);
                }}
                onDragLeave={() => setDragging(false)}
                onDrop={(e) => {
                    e.preventDefault();
                    setDragging(false);
                    choose(e.dataTransfer.files?.[0]);
                }}
                className={`group flex w-full items-center gap-4 rounded-2xl border border-dashed px-5 py-5 text-left transition-colors duration-200 ${
                    dragging ? 'border-brand-500 bg-brand-50' : 'border-line bg-surface hover:border-brand-500/60 hover:bg-brand-50/40'
                }`}
            >
                <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand-600">
                    {/* document mark */}
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" aria-hidden="true">
                        <path d="M14 3v4a1 1 0 0 0 1 1h4" />
                        <path d="M5 3h9l5 5v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z" />
                    </svg>
                </span>
                <span className="min-w-0">
                    {file ? (
                        <>
                            <span className="block truncate text-sm font-medium text-ink">{file.name}</span>
                            <span className="text-xs text-ink-muted">{human} · prêt à téléverser</span>
                        </>
                    ) : (
                        <>
                            <span className="block text-sm font-medium text-ink">Déposez le business plan, ou parcourez</span>
                            <span className="text-xs text-ink-muted">PDF ou PPTX · jusqu’à 50 Mo</span>
                        </>
                    )}
                </span>
            </button>
            <input
                ref={inputRef}
                type="file"
                accept=".pdf,.pptx"
                className="sr-only"
                onChange={(e) => choose(e.target.files?.[0] ?? null)}
            />

            {file && (
                <input
                    type="text"
                    value={title}
                    onChange={(e) => setTitle(e.target.value)}
                    placeholder="Titre du document"
                    className="mt-3 w-full rounded-xl border border-line bg-surface px-3.5 py-2.5 text-sm text-ink placeholder:text-ink-muted focus:border-brand-500"
                />
            )}

            {error && <p className="mt-3 text-sm text-flag" role="alert">{error}</p>}

            <button type="submit" disabled={!file || busy} className="btn-brand mt-4">
                {busy ? 'Téléversement…' : 'Téléverser et lancer l’analyse'}
            </button>
        </form>
    );
}
