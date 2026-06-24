import React, { useEffect, useState } from 'react';
import { getDocument, startSession } from '../lib/api';
import UploadPanel from '../features/upload/UploadPanel';
import ChatPanel from '../features/chat/ChatPanel';

const STATUS_LABELS = {
    uploaded: 'Téléversé',
    parsing: 'Analyse en cours…',
    parsed: 'Analysé',
    indexed: 'Prêt',
    failed: 'Échec',
};

function StatusBadge({ status }) {
    const ready = status === 'indexed';
    const failed = status === 'failed';
    const tone = ready
        ? 'bg-emerald-50 text-emerald-700'
        : failed
        ? 'bg-red-50 text-red-700'
        : 'bg-amber-50 text-amber-700';

    return (
        <span className={`rounded-full px-2.5 py-0.5 text-xs font-medium ${tone}`}>
            {STATUS_LABELS[status] ?? status}
        </span>
    );
}

export default function App() {
    const [document, setDocument] = useState(null);
    const [session, setSession] = useState(null);
    const [status, setStatus] = useState(null);
    const [error, setError] = useState(null);

    const onUploaded = async (doc) => {
        setDocument(doc);
        setStatus(doc.status);
        try {
            const newSession = await startSession(doc.id);
            setSession(newSession);
        } catch (e) {
            setError(e.message || 'Impossible de démarrer la session.');
        }
    };

    // Suivi de l'ingestion jusqu'à "indexed" (ou échec).
    useEffect(() => {
        if (!document || status === 'indexed' || status === 'failed') return undefined;

        const timer = setInterval(async () => {
            try {
                const fresh = await getDocument(document.id);
                setStatus(fresh.status);
            } catch {
                // on réessaie au prochain tick
            }
        }, 3000);

        return () => clearInterval(timer);
    }, [document, status]);

    return (
        <div className="flex min-h-screen flex-col bg-slate-50 text-slate-900">
            <header className="border-b border-slate-200 bg-white">
                <div className="mx-auto flex max-w-3xl items-center justify-between px-6 py-4">
                    <div>
                        <h1 className="text-lg font-semibold">BP Explorer</h1>
                        {document && <p className="text-xs text-slate-500">{document.title}</p>}
                    </div>
                    {status && <StatusBadge status={status} />}
                </div>
            </header>

            <main className="mx-auto flex w-full max-w-3xl flex-1 flex-col px-6 py-6">
                {error && <p className="mb-4 text-sm text-red-600">{error}</p>}

                {!session ? (
                    <div className="mt-10">
                        <p className="mb-6 text-center text-sm text-slate-500">
                            Téléversez un business plan pour l'explorer par le chat (oral ou écrit), sourcé.
                        </p>
                        <UploadPanel onUploaded={onUploaded} />
                    </div>
                ) : (
                    <>
                        {status && status !== 'indexed' && status !== 'failed' && (
                            <p className="mb-3 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-700">
                                L'ingestion se poursuit en arrière-plan. Les réponses gagneront en
                                précision une fois le document « Prêt ».
                            </p>
                        )}
                        <div className="flex-1">
                            <ChatPanel session={session} />
                        </div>
                    </>
                )}
            </main>
        </div>
    );
}
