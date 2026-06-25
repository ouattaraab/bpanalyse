import React, { useEffect, useState } from 'react';
import { listPins, unpin, exportUrl } from '../../lib/api';

/** Compte rendu : liste des réponses épinglées + export DOCX/PDF. */
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
        <div className="fixed inset-0 z-50 flex flex-col bg-slate-900/95 p-4">
            <div className="mb-3 flex items-center justify-between text-white">
                <span className="font-medium">Compte rendu — réponses épinglées</span>
                <button onClick={onClose} className="rounded px-3 py-1 text-sm hover:bg-white/10" type="button">
                    Fermer ✕
                </button>
            </div>

            <div className="flex-1 space-y-3 overflow-y-auto rounded-lg bg-white p-4">
                {loading && <p className="text-sm text-slate-400">Chargement…</p>}
                {!loading && items.length === 0 && (
                    <p className="text-center text-sm text-slate-400">
                        Aucune réponse épinglée. Épinglez des réponses (📌) depuis le chat.
                    </p>
                )}
                {items.map((item) => (
                    <div key={item.id} className="rounded-xl border border-slate-200 p-3">
                        <p className="text-sm font-medium text-slate-800">{item.question}</p>
                        <p className="mt-1 whitespace-pre-wrap text-sm text-slate-600">{item.answer}</p>
                        {item.note && <p className="mt-1 text-xs italic text-slate-400">Note : {item.note}</p>}
                        <button
                            onClick={() => remove(item.id)}
                            className="mt-2 text-xs text-rose-600 hover:underline"
                            type="button"
                        >
                            Retirer
                        </button>
                    </div>
                ))}
            </div>

            <div className="mt-3 flex justify-center gap-2">
                <a
                    href={exportUrl(sessionUuid, 'docx')}
                    className="rounded-full bg-indigo-500 px-4 py-2 text-sm font-medium text-white"
                >
                    Exporter en DOCX
                </a>
                <a
                    href={exportUrl(sessionUuid, 'pdf')}
                    className="rounded-full bg-white px-4 py-2 text-sm font-medium text-slate-900"
                >
                    Exporter en PDF
                </a>
            </div>
        </div>
    );
}
