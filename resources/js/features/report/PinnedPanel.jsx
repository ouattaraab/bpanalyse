import React, { useEffect, useState } from 'react';
import { listPins, unpin, exportUrl } from '../../lib/api';

/** Compte rendu : réponses épinglées + export DOCX/PDF. */
export default function PinnedPanel({ sessionUuid, onClose }) {
    const [items, setItems] = useState([]);
    const [loading, setLoading] = useState(true);

    const refresh = async () => {
        setLoading(true);
        try {
            setItems(await listPins(sessionUuid));
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        refresh();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [sessionUuid]);

    const remove = async (pinId) => {
        await unpin(pinId);
        refresh();
    };

    return (
        <div className="scene-veil fixed inset-0 z-50 flex flex-col p-4 motion-safe:animate-fade-in sm:p-6">
            <div className="mx-auto flex w-full max-w-2xl items-center justify-between pb-4 text-white">
                <div>
                    <p className="text-xs uppercase tracking-[0.18em] text-white/55">Compte rendu</p>
                    <p className="font-display text-lg">Réponses épinglées</p>
                </div>
                <button onClick={onClose} className="rounded-full px-3 py-1.5 text-sm text-white/80 hover:bg-white/10" type="button">
                    Fermer
                </button>
            </div>

            <div className="scroll-fine mx-auto w-full max-w-2xl flex-1 space-y-3 overflow-y-auto">
                {loading && <p className="py-6 text-center text-sm text-white/50">Chargement…</p>}
                {!loading && items.length === 0 && (
                    <p className="rounded-2xl bg-white/5 px-4 py-8 text-center text-sm text-white/60">
                        Rien d’épinglé pour l’instant. Depuis le chat, épinglez les réponses à retenir.
                    </p>
                )}
                {items.map((item) => (
                    <div key={item.id} className="rounded-2xl bg-surface p-4 shadow-lift motion-safe:animate-rise-in">
                        <p className="text-sm font-medium text-ink">{item.question}</p>
                        <p className="mt-1.5 whitespace-pre-wrap text-sm leading-relaxed text-ink-soft">{item.answer}</p>
                        {item.note && <p className="mt-1.5 text-xs italic text-ink-muted">Note : {item.note}</p>}
                        <button onClick={() => remove(item.id)} className="mt-2.5 text-xs text-flag hover:underline" type="button">
                            Retirer
                        </button>
                    </div>
                ))}
            </div>

            <div className="mx-auto mt-4 flex w-full max-w-2xl justify-center gap-2">
                <a href={exportUrl(sessionUuid, 'docx')} className="btn-brand">Exporter en DOCX</a>
                <a href={exportUrl(sessionUuid, 'pdf')} className="btn bg-white text-ink hover:bg-white/90">Exporter en PDF</a>
            </div>
        </div>
    );
}
