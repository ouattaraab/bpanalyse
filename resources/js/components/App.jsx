import React from 'react';

/**
 * Coquille de l'application BP Explorer (squelette d'Étape 2).
 * Les 4 capacités seront implémentées dans resources/js/features/* :
 *   chat (Epic 2), présentation express (Epic 3), débat (Epic 4), compte rendu (Epic 5).
 */
const CAPACITES = [
    {
        titre: 'Chat vocal RAG',
        desc: "Poser une question (oral ou écrit), réponse sourcée en voix clonée du dirigeant.",
        phase: 'Phase 1',
    },
    {
        titre: 'Présentation express',
        desc: 'Sélection automatique des bonnes slides et narration synchronisée de 1 à 2 min.',
        phase: 'Phase 2 · différenciateur',
    },
    {
        titre: 'Débat du board',
        desc: '4 personas débattent tour par tour, répliques sourcées et chiffres vérifiés.',
        phase: 'Phase 3',
    },
    {
        titre: 'Compte rendu one-shot',
        desc: 'Épinglage des réponses pertinentes, export DOCX/PDF, puis purge de la session.',
        phase: 'Phase 4',
    },
];

export default function App() {
    return (
        <main className="min-h-screen bg-slate-50 text-slate-900">
            <header className="border-b border-slate-200 bg-white">
                <div className="mx-auto max-w-5xl px-6 py-6">
                    <h1 className="text-2xl font-semibold">BP Explorer</h1>
                    <p className="mt-1 text-sm text-slate-500">
                        Rendre un business plan complexe intelligible et interrogeable en séminaire de direction.
                    </p>
                </div>
            </header>

            <section className="mx-auto max-w-5xl px-6 py-10">
                <div className="grid gap-4 sm:grid-cols-2">
                    {CAPACITES.map((c) => (
                        <article
                            key={c.titre}
                            className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
                        >
                            <span className="text-xs font-medium uppercase tracking-wide text-indigo-600">
                                {c.phase}
                            </span>
                            <h2 className="mt-2 text-lg font-semibold">{c.titre}</h2>
                            <p className="mt-1 text-sm text-slate-600">{c.desc}</p>
                        </article>
                    ))}
                </div>

                <p className="mt-8 text-center text-xs text-slate-400">
                    Squelette initialisé (Étape 2). Implémentation phase par phase via{' '}
                    <code className="rounded bg-slate-100 px-1 py-0.5">/implement-story</code>.
                </p>
            </section>
        </main>
    );
}
