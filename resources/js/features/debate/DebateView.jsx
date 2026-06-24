import React, { useEffect, useRef, useState } from 'react';
import { getDebate } from '../../lib/api';

const PERSONA_TONE = {
    dg: 'bg-blue-50 text-blue-700',
    investor: 'bg-rose-50 text-rose-700',
    cfo: 'bg-emerald-50 text-emerald-700',
    sales: 'bg-violet-50 text-violet-700',
};

function FiguresBadges({ figures }) {
    if (!figures || figures.length === 0) return null;

    return (
        <div className="mt-2 flex flex-wrap gap-1.5">
            {figures.map((figure, index) => {
                const ok = figure.status === 'verifie';
                return (
                    <span
                        key={index}
                        className={
                            'inline-flex items-center rounded-full px-2 py-0.5 text-xs ' +
                            (ok ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-100 text-amber-800')
                        }
                        title={figure.matched_label ?? 'Chiffre non adossé aux données'}
                    >
                        {ok ? '✓' : '⚠'} {figure.value}
                        {ok && figure.matched_label ? ` · ${figure.matched_label}` : ' · à vérifier'}
                    </span>
                );
            })}
        </div>
    );
}

export default function DebateView({ debate: initial, onClose }) {
    const [debate, setDebate] = useState(initial);
    const timerRef = useRef(null);

    useEffect(() => {
        const running = debate.status === 'pending' || debate.status === 'running';
        if (!running) return undefined;

        timerRef.current = setInterval(async () => {
            try {
                const fresh = await getDebate(initial.id);
                setDebate(fresh);
            } catch {
                // on réessaie au prochain tick
            }
        }, 2500);

        return () => clearInterval(timerRef.current);
    }, [debate.status, initial.id]);

    const turns = debate.turns ?? [];
    const running = debate.status === 'pending' || debate.status === 'running';

    return (
        <div className="fixed inset-0 z-50 flex flex-col bg-slate-900/95 p-4">
            <div className="mb-3 flex items-center justify-between text-white">
                <div className="text-sm">
                    <span className="font-medium">Débat du board</span>
                    <span className="ml-2 text-slate-300">{debate.question}</span>
                </div>
                <button onClick={onClose} className="rounded px-3 py-1 text-sm hover:bg-white/10" type="button">
                    Fermer ✕
                </button>
            </div>

            <div className="flex-1 space-y-3 overflow-y-auto rounded-lg bg-white p-4">
                {turns.length === 0 && (
                    <p className="text-center text-sm text-slate-400">
                        Le débat se prépare… (en local, lancez <code>php artisan queue:work</code>)
                    </p>
                )}

                {turns.map((turn) => (
                    <div key={turn.turn_index} className="rounded-xl border border-slate-200 p-3">
                        <span className={`inline-block rounded-full px-2 py-0.5 text-xs font-medium ${PERSONA_TONE[turn.persona] ?? 'bg-slate-100 text-slate-700'}`}>
                            {turn.persona_name}
                        </span>
                        <p className="mt-2 whitespace-pre-wrap text-sm text-slate-800">{turn.content}</p>
                        <FiguresBadges figures={turn.verified_figures} />
                    </div>
                ))}

                {running && turns.length > 0 && (
                    <p className="text-center text-xs text-slate-400">Le débat se poursuit…</p>
                )}
            </div>
        </div>
    );
}
