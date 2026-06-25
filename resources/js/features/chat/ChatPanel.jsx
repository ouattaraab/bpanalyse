import React, { useEffect, useRef, useState } from 'react';
import { useChat } from './useChat';
import { useAudioRecorder } from './useAudioRecorder';
import SourceList from './SourceList';
import PresentationPlayer from '../presentation/PresentationPlayer';
import DebateView from '../debate/DebateView';
import PinnedPanel from '../report/PinnedPanel';
import VoiceGovernance from '../voice/VoiceGovernance';
import { createPresentation, startDebate, pinInteraction, synthesizeAnswer } from '../../lib/api';

const SUGGESTIONS = [
    'Quelle est la trajectoire de chiffre d’affaires ?',
    'Quels sont les risques du plan ?',
    'Résume la stratégie commerciale.',
];

export default function ChatPanel({ session, tenantId, documentTitle }) {
    const { messages, pending, error, send, transcribe } = useChat(session.uuid);
    const recorder = useAudioRecorder();
    const [input, setInput] = useState('');
    const [transcribing, setTranscribing] = useState(false);
    const [presentation, setPresentation] = useState(null);
    const [presoLoading, setPresoLoading] = useState(false);
    const [actionError, setActionError] = useState(null);
    const [debate, setDebate] = useState(null);
    const [debateLoading, setDebateLoading] = useState(false);
    const [showPins, setShowPins] = useState(false);
    const [showVoice, setShowVoice] = useState(false);
    const [activeVoiceModelId, setActiveVoiceModelId] = useState(null);
    const [pinned, setPinned] = useState({});
    const scrollRef = useRef(null);

    useEffect(() => {
        scrollRef.current?.scrollTo({ top: scrollRef.current.scrollHeight, behavior: 'smooth' });
    }, [messages, pending]);

    const submit = async (event) => {
        event.preventDefault();
        const question = input;
        setInput('');
        await send(question);
    };

    const toggleMic = async () => {
        if (recorder.recording) {
            const blob = await recorder.stop();
            if (!blob) return;
            setTranscribing(true);
            try {
                const { text } = await transcribe(blob);
                setInput((prev) => (prev ? `${prev} ${text}` : text));
            } catch {
                /* erreur affichée via recorder.error */
            } finally {
                setTranscribing(false);
            }
        } else {
            await recorder.start().catch(() => {});
        }
    };

    const runScene = async (kind) => {
        const question = input.trim();
        if (!question) {
            setActionError('Saisissez d’abord une question.');
            return;
        }
        setActionError(null);
        if (kind === 'presentation') {
            setPresoLoading(true);
            try {
                setPresentation(await createPresentation(session.uuid, question));
            } catch (e) {
                setActionError(e.message || 'Présentation indisponible.');
            } finally {
                setPresoLoading(false);
            }
        } else {
            setDebateLoading(true);
            try {
                setDebate(await startDebate(session.uuid, question, 2));
            } catch (e) {
                setActionError(e.message || 'Débat indisponible.');
            } finally {
                setDebateLoading(false);
            }
        }
    };

    const pinAnswer = async (interactionId) => {
        if (!interactionId) return;
        try {
            await pinInteraction(session.uuid, interactionId);
            setPinned((prev) => ({ ...prev, [interactionId]: true }));
        } catch (e) {
            setActionError(e.message);
        }
    };

    const listenAnswer = async (interactionId) => {
        if (!interactionId) return;
        if (!activeVoiceModelId) {
            setActionError('Aucune voix active. Configurez-la dans « Voix ».');
            return;
        }
        try {
            new Audio(await synthesizeAnswer(interactionId, activeVoiceModelId)).play();
        } catch (e) {
            setActionError(e.message);
        }
    };

    return (
        <div className="surface-card flex h-full flex-col overflow-hidden">
            {presentation && <PresentationPlayer presentation={presentation} onClose={() => setPresentation(null)} />}
            {debate && <DebateView debate={debate} onClose={() => setDebate(null)} />}
            {showPins && <PinnedPanel sessionUuid={session.uuid} onClose={() => setShowPins(false)} />}
            {showVoice && (
                <VoiceGovernance tenantId={tenantId} onClose={() => setShowVoice(false)} onActiveModel={setActiveVoiceModelId} />
            )}

            <div className="flex items-center justify-between border-b border-line px-5 py-3">
                <p className="truncate text-sm font-medium text-ink">{documentTitle || 'Conversation'}</p>
                <div className="flex items-center gap-1">
                    <button type="button" onClick={() => setShowPins(true)} className="btn-ghost px-3 py-1.5 text-xs">
                        Compte rendu
                    </button>
                    <button type="button" onClick={() => setShowVoice(true)} className="btn-ghost px-3 py-1.5 text-xs">
                        Voix
                    </button>
                </div>
            </div>

            <div ref={scrollRef} className="scroll-fine flex-1 space-y-5 overflow-y-auto px-5 py-6">
                {messages.length === 0 && (
                    <div className="mx-auto max-w-md py-8 text-center motion-safe:animate-rise-in">
                        <h3 className="font-display text-lg text-ink">Posez votre première question</h3>
                        <p className="mt-1.5 text-sm text-ink-soft">À l’écrit ou à l’oral. Essayez par exemple&nbsp;:</p>
                        <div className="mt-4 flex flex-col items-center gap-2">
                            {SUGGESTIONS.map((q) => (
                                <button
                                    key={q}
                                    type="button"
                                    onClick={() => setInput(q)}
                                    className="rounded-full border border-line bg-surface px-3.5 py-1.5 text-sm text-ink-soft transition-colors hover:border-brand-500/50 hover:text-ink"
                                >
                                    {q}
                                </button>
                            ))}
                        </div>
                    </div>
                )}

                {messages.map((message) => (
                    <div
                        key={message.id}
                        className={`flex motion-safe:animate-rise-in ${message.role === 'user' ? 'justify-end' : 'justify-start'}`}
                    >
                        <div
                            className={
                                message.role === 'user'
                                    ? 'max-w-[82%] rounded-2xl rounded-br-md bg-ink px-4 py-2.5 text-sm leading-relaxed text-paper'
                                    : 'max-w-[82%] rounded-2xl rounded-bl-md border border-line bg-surface px-4 py-3 text-sm leading-relaxed text-ink shadow-card'
                            }
                        >
                            <p className="whitespace-pre-wrap">{message.text}</p>
                            {message.role === 'assistant' && (
                                <>
                                    <SourceList sources={message.sources} />
                                    {message.interactionId && (
                                        <div className="mt-2.5 flex gap-4 border-t border-line/70 pt-2 text-xs text-ink-muted">
                                            <button type="button" onClick={() => pinAnswer(message.interactionId)} className="transition-colors hover:text-brand-600">
                                                {pinned[message.interactionId] ? '✓ Épinglée' : 'Épingler'}
                                            </button>
                                            <button type="button" onClick={() => listenAnswer(message.interactionId)} className="transition-colors hover:text-brand-600">
                                                Écouter
                                            </button>
                                        </div>
                                    )}
                                </>
                            )}
                        </div>
                    </div>
                ))}

                {pending && (
                    <div className="flex items-center gap-1.5 px-1 text-ink-muted" aria-live="polite">
                        <span className="h-1.5 w-1.5 rounded-full bg-ink-muted motion-safe:animate-bounce [animation-delay:-0.2s]" />
                        <span className="h-1.5 w-1.5 rounded-full bg-ink-muted motion-safe:animate-bounce [animation-delay:-0.1s]" />
                        <span className="h-1.5 w-1.5 rounded-full bg-ink-muted motion-safe:animate-bounce" />
                    </div>
                )}
            </div>

            <div className="border-t border-line px-5 pb-4 pt-3">
                {(error || recorder.error || actionError) && (
                    <p className="mb-2 text-sm text-flag" role="alert">{error || recorder.error || actionError}</p>
                )}

                <div className="mb-3 flex items-center gap-2">
                    <button type="button" onClick={() => runScene('presentation')} disabled={presoLoading} className="btn-ghost border border-line px-3 py-1.5 text-xs disabled:opacity-40">
                        {presoLoading ? 'Génération…' : 'Présentation express'}
                    </button>
                    <button type="button" onClick={() => runScene('debate')} disabled={debateLoading} className="btn-ghost border border-line px-3 py-1.5 text-xs disabled:opacity-40">
                        {debateLoading ? 'Lancement…' : 'Débat du board'}
                    </button>
                    <span className="ml-auto hidden text-xs text-ink-muted sm:inline">à partir de votre question</span>
                </div>

                <form onSubmit={submit} className="flex items-center gap-2">
                    <button
                        type="button"
                        onClick={toggleMic}
                        disabled={transcribing}
                        aria-label={recorder.recording ? 'Arrêter l’enregistrement' : 'Question vocale'}
                        className={`flex h-11 w-11 shrink-0 items-center justify-center rounded-full transition-colors ${
                            recorder.recording ? 'bg-[#F6E3DF] text-flag ring-2 ring-flag/40' : 'bg-ink/5 text-ink-soft hover:bg-ink/10'
                        }`}
                    >
                        {recorder.recording ? (
                            <span className="h-3 w-3 rounded-[3px] bg-current" />
                        ) : (
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7" aria-hidden="true">
                                <rect x="9" y="3" width="6" height="11" rx="3" />
                                <path d="M5 11a7 7 0 0 0 14 0M12 18v3" />
                            </svg>
                        )}
                    </button>
                    <input
                        type="text"
                        value={input}
                        onChange={(e) => setInput(e.target.value)}
                        placeholder={transcribing ? 'Transcription en cours…' : 'Votre question…'}
                        className="h-11 flex-1 rounded-full border border-line bg-surface px-4 text-sm text-ink placeholder:text-ink-muted focus:border-brand-500"
                    />
                    <button type="submit" disabled={pending || !input.trim()} className="btn-brand h-11">
                        Envoyer
                    </button>
                </form>
            </div>
        </div>
    );
}
