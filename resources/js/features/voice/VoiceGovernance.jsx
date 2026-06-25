import React, { useEffect, useState } from 'react';
import { listConsents, grantConsent, revokeConsent, createVoiceModel } from '../../lib/api';

/**
 * Gouvernance du clonage vocal : consentement écrit (finalité + durée),
 * modèle vocal, révocation. Conformité ARTCI / Loi 2013-450.
 */
export default function VoiceGovernance({ tenantId, onClose, onActiveModel }) {
    const [consents, setConsents] = useState([]);
    const [form, setForm] = useState({ person_name: '', purpose: '', retention_until: '' });
    const [error, setError] = useState(null);
    const [busy, setBusy] = useState(false);

    const refresh = async () => {
        try {
            const data = await listConsents(tenantId);
            setConsents(data);
            const active = data.flatMap((c) => c.voice_models ?? []).find((m) => m.active);
            if (active) onActiveModel?.(active.id);
        } catch (e) {
            setError(e.message);
        }
    };

    useEffect(() => {
        refresh();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [tenantId]);

    const grant = async (event) => {
        event.preventDefault();
        if (!form.person_name || !form.purpose) return;
        setBusy(true);
        setError(null);
        try {
            await grantConsent(tenantId, form);
            setForm({ person_name: '', purpose: '', retention_until: '' });
            refresh();
        } catch (e) {
            setError(e.message);
        } finally {
            setBusy(false);
        }
    };

    const revoke = async (consentId) => {
        await revokeConsent(consentId);
        refresh();
    };

    const addModel = async (consentId, files) => {
        if (!files || files.length === 0) return;
        setBusy(true);
        setError(null);
        try {
            await createVoiceModel(consentId, Array.from(files));
            refresh();
        } catch (e) {
            setError(e.message || 'Clonage impossible (consentement requis, clé ElevenLabs).');
        } finally {
            setBusy(false);
        }
    };

    const field = 'rounded-xl border border-line bg-surface px-3 py-2 text-sm text-ink placeholder:text-ink-muted focus:border-brand-500';

    return (
        <div className="scene-veil fixed inset-0 z-50 flex flex-col p-4 motion-safe:animate-fade-in sm:p-6">
            <div className="mx-auto flex w-full max-w-2xl items-center justify-between pb-4 text-white">
                <div>
                    <p className="text-xs uppercase tracking-[0.18em] text-white/55">Gouvernance</p>
                    <p className="font-display text-lg">Voix clonée</p>
                </div>
                <button onClick={onClose} className="rounded-full px-3 py-1.5 text-sm text-white/80 hover:bg-white/10" type="button">
                    Fermer
                </button>
            </div>

            <div className="scroll-fine mx-auto w-full max-w-2xl flex-1 space-y-4 overflow-y-auto">
                <p className="rounded-xl border border-warn-100 bg-warn-50 px-3.5 py-2.5 text-xs leading-relaxed text-warn-700">
                    Données biométriques : consentement écrit, limité et révocable (Loi 2013-450 / ARTCI).
                    La révocation supprime le modèle vocal.
                </p>

                <form onSubmit={grant} className="grid gap-2 rounded-2xl bg-surface p-4 shadow-lift sm:grid-cols-3">
                    <input className={field} placeholder="Dirigeant" value={form.person_name} onChange={(e) => setForm({ ...form, person_name: e.target.value })} />
                    <input className={field} placeholder="Finalité" value={form.purpose} onChange={(e) => setForm({ ...form, purpose: e.target.value })} />
                    <input type="date" className={field} value={form.retention_until} onChange={(e) => setForm({ ...form, retention_until: e.target.value })} />
                    <button type="submit" disabled={busy} className="btn-brand sm:col-span-3">
                        Enregistrer le consentement
                    </button>
                </form>

                {error && <p className="text-sm text-[#F2C7BF]">{error}</p>}

                {consents.map((consent) => (
                    <div key={consent.id} className="rounded-2xl bg-surface p-4 shadow-lift">
                        <div className="flex items-start justify-between gap-3">
                            <div className="min-w-0">
                                <p className="text-sm font-medium text-ink">{consent.person_name}</p>
                                <p className="text-xs text-ink-muted">
                                    {consent.purpose}
                                    {consent.retention_until ? ` · jusqu’au ${consent.retention_until}` : ''}
                                </p>
                            </div>
                            <span className={`shrink-0 rounded-full px-2 py-0.5 text-xs ${consent.active ? 'bg-brand-50 text-brand-700' : 'bg-ink/5 text-ink-muted'}`}>
                                {consent.active ? 'actif' : 'révoqué'}
                            </span>
                        </div>

                        {(consent.voice_models ?? []).map((model) => (
                            <p key={model.id} className="mt-1.5 text-xs text-ink-soft">
                                Modèle #{model.id} · {model.provider} · {model.active ? '✓ actif' : 'révoqué'}
                            </p>
                        ))}

                        {consent.active && (
                            <div className="mt-3 flex flex-wrap items-center gap-3">
                                <label className="cursor-pointer text-xs font-medium text-brand-600 hover:underline">
                                    + Modèle vocal (échantillons)
                                    <input type="file" accept="audio/*" multiple className="sr-only" onChange={(e) => addModel(consent.id, e.target.files)} />
                                </label>
                                <button onClick={() => revoke(consent.id)} className="text-xs text-flag hover:underline" type="button">
                                    Révoquer
                                </button>
                            </div>
                        )}
                    </div>
                ))}
            </div>
        </div>
    );
}
