import React, { useState } from 'react';
import { useChat } from './useChat';
import { useAudioRecorder } from './useAudioRecorder';
import SourceList from './SourceList';
import PresentationPlayer from '../presentation/PresentationPlayer';
import DebateView from '../debate/DebateView';
import { createPresentation, startDebate } from '../../lib/api';

/** Chat vocal RAG : question écrite ou orale, réponse sourcée. */
export default function ChatPanel({ session }) {
    const { messages, pending, error, send, transcribe } = useChat(session.uuid);
    const recorder = useAudioRecorder();
    const [input, setInput] = useState('');
    const [transcribing, setTranscribing] = useState(false);
    const [presentation, setPresentation] = useState(null);
    const [presoLoading, setPresoLoading] = useState(false);
    const [presoError, setPresoError] = useState(null);

    const [debate, setDebate] = useState(null);
    const [debateLoading, setDebateLoading] = useState(false);

    const launchPresentation = async () => {
        const question = input.trim();
        if (!question || presoLoading) return;
        setPresoError(null);
        setPresoLoading(true);
        try {
            const result = await createPresentation(session.uuid, question);
            setPresentation(result);
        } catch (e) {
            setPresoError(e.message || "La présentation n'a pas pu être générée.");
        } finally {
            setPresoLoading(false);
        }
    };

    const launchDebate = async () => {
        const question = input.trim();
        if (!question || debateLoading) return;
        setPresoError(null);
        setDebateLoading(true);
        try {
            const result = await startDebate(session.uuid, question, 2);
            setDebate(result);
        } catch (e) {
            setPresoError(e.message || "Le débat n'a pas pu être lancé.");
        } finally {
            setDebateLoading(false);
        }
    };

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
                // l'erreur micro/STT est affichée via recorder.error
            } finally {
                setTranscribing(false);
            }
        } else {
            await recorder.start().catch(() => {});
        }
    };

    return (
        <div className="flex h-full flex-col">
            {presentation && (
                <PresentationPlayer presentation={presentation} onClose={() => setPresentation(null)} />
            )}
            {debate && <DebateView debate={debate} onClose={() => setDebate(null)} />}

            <div className="flex-1 space-y-4 overflow-y-auto px-1 py-4">
                {messages.length === 0 && (
                    <p className="text-center text-sm text-slate-400">
                        Posez une question sur le business plan (à l'écrit ou à l'oral).
                    </p>
                )}

                {messages.map((message) => (
                    <div
                        key={message.id}
                        className={message.role === 'user' ? 'flex justify-end' : 'flex justify-start'}
                    >
                        <div
                            className={
                                message.role === 'user'
                                    ? 'max-w-[80%] rounded-2xl bg-indigo-600 px-4 py-2 text-sm text-white'
                                    : 'max-w-[80%] rounded-2xl bg-white px-4 py-2 text-sm text-slate-800 shadow-sm ring-1 ring-slate-200'
                            }
                        >
                            <p className="whitespace-pre-wrap">{message.text}</p>
                            {message.role === 'assistant' && <SourceList sources={message.sources} />}
                        </div>
                    </div>
                ))}

                {pending && <p className="text-sm text-slate-400">L'assistant réfléchit…</p>}
            </div>

            {(error || recorder.error || presoError) && (
                <p className="px-1 pb-2 text-sm text-red-600">{error || recorder.error || presoError}</p>
            )}

            <div className="flex items-center justify-between border-t border-slate-200 pt-2">
                <span className="text-xs text-slate-400">
                    Tapez une question, puis générez une présentation express ou envoyez-la au chat.
                </span>
                <div className="flex gap-2">
                    <button
                        type="button"
                        onClick={launchDebate}
                        disabled={debateLoading || !input.trim()}
                        className="rounded-full bg-slate-700 px-3 py-1.5 text-xs font-medium text-white disabled:opacity-40"
                    >
                        {debateLoading ? 'Lancement…' : '⚖ Débat du board'}
                    </button>
                    <button
                        type="button"
                        onClick={launchPresentation}
                        disabled={presoLoading || !input.trim()}
                        className="rounded-full bg-amber-500 px-3 py-1.5 text-xs font-medium text-white disabled:opacity-40"
                    >
                        {presoLoading ? 'Génération…' : '▶ Présentation express'}
                    </button>
                </div>
            </div>

            <form onSubmit={submit} className="mt-2 flex items-center gap-2">
                <button
                    type="button"
                    onClick={toggleMic}
                    disabled={transcribing}
                    className={
                        'flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-lg ' +
                        (recorder.recording
                            ? 'bg-red-100 text-red-600 ring-2 ring-red-400'
                            : 'bg-slate-100 text-slate-600 hover:bg-slate-200')
                    }
                    title={recorder.recording ? "Arrêter l'enregistrement" : 'Question vocale'}
                >
                    {recorder.recording ? '■' : '🎤'}
                </button>

                <input
                    type="text"
                    value={input}
                    onChange={(event) => setInput(event.target.value)}
                    placeholder={transcribing ? 'Transcription en cours…' : 'Votre question…'}
                    className="flex-1 rounded-full border border-slate-300 px-4 py-2 text-sm focus:border-indigo-500 focus:outline-none"
                />

                <button
                    type="submit"
                    disabled={pending || !input.trim()}
                    className="rounded-full bg-indigo-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-40"
                >
                    Envoyer
                </button>
            </form>
        </div>
    );
}
