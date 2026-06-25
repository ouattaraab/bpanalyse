import React, { useEffect, useState } from 'react';
import { getDocument, startSession } from '../lib/api';
import UploadPanel from '../features/upload/UploadPanel';
import ChatPanel from '../features/chat/ChatPanel';

const STATUS = {
    uploaded: { label: 'Téléversé', tone: 'pending' },
    parsing: { label: 'Analyse…', tone: 'pending' },
    parsed: { label: 'Analysé', tone: 'pending' },
    indexed: { label: 'Prêt', tone: 'ready' },
    failed: { label: 'Échec', tone: 'failed' },
};

function StatusPill({ status }) {
    const meta = STATUS[status] ?? { label: status, tone: 'pending' };
    const tone = {
        ready: 'bg-brand-50 text-brand-700 ring-brand-100',
        failed: 'bg-[#F6E3DF] text-flag ring-[#EAC9C3]',
        pending: 'bg-warn-50 text-warn-700 ring-warn-100',
    }[meta.tone];

    return (
        <span className={`inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium ring-1 ${tone}`}>
            <span
                className={`h-1.5 w-1.5 rounded-full ${meta.tone === 'ready' ? 'bg-brand-500' : meta.tone === 'failed' ? 'bg-flag' : 'bg-warn-600 motion-safe:animate-pulse'}`}
            />
            {meta.label}
        </span>
    );
}

const CAPABILITIES = [
    ['Présentation express', 'Les bonnes slides, narrées en 2 minutes.'],
    ['Débat du board', 'Quatre voix challengent le plan — et traquent les chiffres faux.'],
    ['Chat sourcé', 'Chaque réponse renvoie à sa slide. Aucun chiffre inventé.'],
    ['Compte rendu', 'Épinglez, exportez, repartez avec l’essentiel.'],
];

export default function App() {
    const [document, setDocument] = useState(null);
    const [session, setSession] = useState(null);
    const [status, setStatus] = useState(null);
    const [error, setError] = useState(null);

    const onUploaded = async (doc) => {
        setDocument(doc);
        setStatus(doc.status);
        try {
            setSession(await startSession(doc.id));
        } catch (e) {
            setError(e.message || 'Impossible de démarrer la session.');
        }
    };

    useEffect(() => {
        if (!document || status === 'indexed' || status === 'failed') return undefined;
        const timer = setInterval(async () => {
            try {
                setStatus((await getDocument(document.id)).status);
            } catch {
                /* retry au prochain tick */
            }
        }, 3000);
        return () => clearInterval(timer);
    }, [document, status]);

    return (
        <div className="flex min-h-screen flex-col">
            <header className="border-b border-line/80 bg-paper/80 backdrop-blur">
                <div className="mx-auto flex w-full max-w-4xl items-center justify-between gap-4 px-6 py-4">
                    <div className="flex items-baseline gap-3">
                        <span className="font-display text-xl font-semibold tracking-tight text-ink">
                            BP&thinsp;Explorer
                        </span>
                        <span className="hidden text-xs text-ink-muted sm:inline">
                            le business plan, rendu intelligible
                        </span>
                    </div>
                    <div className="flex items-center gap-3">
                        {document && <span className="hidden max-w-[12rem] truncate text-xs text-ink-soft md:inline">{document.title}</span>}
                        {status && <StatusPill status={status} />}
                    </div>
                </div>
            </header>

            <main className="mx-auto flex w-full max-w-4xl flex-1 flex-col px-6">
                {error && (
                    <p className="mt-4 rounded-xl bg-[#F6E3DF] px-4 py-2 text-sm text-flag" role="alert">
                        {error}
                    </p>
                )}

                {!session ? (
                    <section className="flex flex-1 flex-col justify-center py-12 motion-safe:animate-rise-in">
                        <p className="text-sm font-medium uppercase tracking-[0.18em] text-brand-600">
                            Séminaire de direction
                        </p>
                        <h1 className="mt-3 max-w-2xl text-balance font-display text-4xl font-semibold leading-[1.1] text-ink sm:text-5xl">
                            Cent cinquante pages.{' '}
                            <span className="text-brand-600">Deux minutes</span> pour les comprendre.
                        </h1>
                        <p className="mt-4 max-w-xl text-pretty text-base leading-relaxed text-ink-soft">
                            Téléversez le business plan : BP Explorer le présente, le met en débat et
                            répond à vos questions — chiffres vérifiés, sources à l’appui.
                        </p>

                        <div className="mt-9">
                            <UploadPanel onUploaded={onUploaded} />
                        </div>

                        <ul className="mt-12 grid gap-x-10 gap-y-6 border-t border-line pt-8 sm:grid-cols-2">
                            {CAPABILITIES.map(([title, desc], i) => (
                                <li key={title} className="motion-safe:animate-rise-in" style={{ animationDelay: `${120 + i * 70}ms` }}>
                                    <h2 className="font-display text-base font-medium text-ink">{title}</h2>
                                    <p className="mt-1 text-sm leading-relaxed text-ink-soft">{desc}</p>
                                </li>
                            ))}
                        </ul>
                    </section>
                ) : (
                    <section className="flex flex-1 flex-col py-5">
                        {status && status !== 'indexed' && status !== 'failed' && (
                            <p className="mb-3 flex items-center gap-2 rounded-xl bg-warn-50 px-3.5 py-2 text-xs text-warn-700">
                                <span className="h-1.5 w-1.5 rounded-full bg-warn-600 motion-safe:animate-pulse" />
                                L’ingestion se poursuit. Les réponses gagneront en précision une fois le document « Prêt ».
                            </p>
                        )}
                        <div className="flex-1">
                            <ChatPanel session={session} tenantId={document?.tenant_id} documentTitle={document?.title} />
                        </div>
                    </section>
                )}
            </main>
        </div>
    );
}
