import React, { useEffect, useRef, useState } from 'react';
import { getDebate } from '../../lib/api';

const PERSONA_TONE = {
    dg: 'bg-brand-50 text-brand-700',
    investor: 'bg-[#F6E3DF] text-flag',
    cfo: 'bg-warn-50 text-warn-700',
    sales: 'bg-[#EAE6F2] text-[#5b4a86]',
};

function FiguresBadges({ figures }) {
    if (!figures || figures.length === 0) return null;
    return (
        <div className="mt-2.5 flex flex-wrap gap-1.5">
            {figures.map((figure, index) => {
                const ok = figure.status === 'verifie';
                return (
                    <span
                        key={index}
                        className={`inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs ${ok ? 'bg-brand-50 text-brand-700' : 'bg-warn-50 text-warn-700'}`}
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
                setDebate(await getDebate(initial.id));
            } catch {
                /* retry */
            }
        }, 2500);
        return () => clearInterval(timerRef.current);
    }, [debate.status, initial.id]);

    const turns = debate.turns ?? [];
    const running = debate.status === 'pending' || debate.status === 'running';

    return (
        <div className="scene-veil fixed inset-0 z-50 flex flex-col p-4 motion-safe:animate-fade-in sm:p-6">
            <div className="mx-auto flex w-full max-w-3xl items-center justify-between pb-4 text-white">
                <div className="min-w-0">
                    <p className="text-xs uppercase tracking-[0.18em] text-white/55">Débat du board</p>
                    <p className="truncate font-display text-lg">{debate.question}</p>
                </div>
                <button onClick={onClose} className="rounded-full px-3 py-1.5 text-sm text-white/80 hover:bg-white/10" type="button">
                    Fermer
                </button>
            </div>

            <div className="scroll-fine mx-auto w-full max-w-3xl flex-1 space-y-3 overflow-y-auto">
                {turns.length === 0 && (
                    <p className="rounded-2xl bg-white/5 px-4 py-6 text-center text-sm text-white/60">
                        Le débat se prépare… (en local, lancez <code className="text-white/80">php artisan queue:work</code>)
                    </p>
                )}

                {turns.map((turn) => (
                    <div key={turn.turn_index} className="rounded-2xl bg-surface p-4 shadow-lift motion-safe:animate-rise-in">
                        <span className={`inline-block rounded-full px-2.5 py-0.5 text-xs font-medium ${PERSONA_TONE[turn.persona] ?? 'bg-ink/5 text-ink-soft'}`}>
                            {turn.persona_name}
                        </span>
                        <p className="mt-2 whitespace-pre-wrap text-sm leading-relaxed text-ink">{turn.content}</p>
                        <FiguresBadges figures={turn.verified_figures} />
                    </div>
                ))}

                {running && turns.length > 0 && (
                    <p className="py-2 text-center text-xs text-white/45">Le débat se poursuit…</p>
                )}
            </div>
        </div>
    );
}
