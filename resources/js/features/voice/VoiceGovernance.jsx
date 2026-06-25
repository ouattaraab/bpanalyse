import React, { useEffect, useState } from 'react';
import { listConsents, grantConsent, revokeConsent, createVoiceModel } from '../../lib/api';

/**
 * Gouvernance du clonage vocal : consentement écrit (finalité + durée),
 * création d'un modèle vocal, révocation. Conformité ARTCI / Loi 2013-450.
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
            const active = data
                .flatMap((c) => c.voice_models ?? [])
                .find((m) => m.active);
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

    return (
        <div className="fixed inset-0 z-50 flex flex-col bg-slate-900/95 p-4">
            <div className="mb-3 flex items-center justify-between text-white">
                <span className="font-medium">Gouvernance de la voix clonée</span>
                <button onClick={onClose} className="rounded px-3 py-1 text-sm hover:bg-white/10" type="button">
                    Fermer ✕
                </button>
            </div>

            <div className="flex-1 space-y-4 overflow-y-auto rounded-lg bg-white p-4">
                <p className="rounded bg-amber-50 px-3 py-2 text-xs text-amber-700">
                    Données biométriques : consentement écrit, limité et révocable (Loi 2013-450 / ARTCI).
                    La révocation supprime le modèle vocal.
                </p>

                <form onSubmit={grant} className="grid gap-2 sm:grid-cols-3">
                    <input
                        className="rounded border border-slate-300 px-2 py-1.5 text-sm"
                        placeholder="Personne (dirigeant)"
                        value={form.person_name}
                        onChange={(e) => setForm({ ...form, person_name: e.target.value })}
                    />
                    <input
                        className="rounded border border-slate-300 px-2 py-1.5 text-sm"
                        placeholder="Finalité"
                        value={form.purpose}
                        onChange={(e) => setForm({ ...form, purpose: e.target.value })}
                    />
                    <input
                        type="date"
                        className="rounded border border-slate-300 px-2 py-1.5 text-sm"
                        value={form.retention_until}
                        onChange={(e) => setForm({ ...form, retention_until: e.target.value })}
                    />
                    <button
                        type="submit"
                        disabled={busy}
                        className="rounded bg-indigo-600 px-3 py-1.5 text-sm text-white disabled:opacity-40 sm:col-span-3"
                    >
                        Enregistrer le consentement
                    </button>
                </form>

                {error && <p className="text-sm text-red-600">{error}</p>}

                {consents.map((consent) => (
                    <div key={consent.id} className="rounded-xl border border-slate-200 p-3">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium">{consent.person_name}</p>
                                <p className="text-xs text-slate-500">
                                    {consent.purpose} · {consent.active ? 'actif' : 'révoqué'}
                                    {consent.retention_until ? ` · jusqu'au ${consent.retention_until}` : ''}
                                </p>
                            </div>
                            {consent.active && (
                                <button onClick={() => revoke(consent.id)} className="text-xs text-rose-600 hover:underline" type="button">
                                    Révoquer
                                </button>
                            )}
                        </div>

                        <div className="mt-2 space-y-1">
                            {(consent.voice_models ?? []).map((model) => (
                                <p key={model.id} className="text-xs text-slate-600">
                                    Modèle vocal #{model.id} · {model.provider} · {model.active ? '✓ actif' : 'révoqué'}
                                </p>
                            ))}
                        </div>

                        {consent.active && (
                            <label className="mt-2 inline-block cursor-pointer text-xs text-indigo-600 hover:underline">
                                + Créer un modèle vocal (échantillons audio)
                                <input
                                    type="file"
                                    accept="audio/*"
                                    multiple
                                    className="hidden"
                                    onChange={(e) => addModel(consent.id, e.target.files)}
                                />
                            </label>
                        )}
                    </div>
                ))}
            </div>
        </div>
    );
}
