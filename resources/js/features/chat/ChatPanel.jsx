import React, { useState } from 'react';
import { useChat } from './useChat';
import { useAudioRecorder } from './useAudioRecorder';
import SourceList from './SourceList';

/** Chat vocal RAG : question écrite ou orale, réponse sourcée. */
export default function ChatPanel({ session }) {
    const { messages, pending, error, send, transcribe } = useChat(session.uuid);
    const recorder = useAudioRecorder();
    const [input, setInput] = useState('');
    const [transcribing, setTranscribing] = useState(false);

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

            {(error || recorder.error) && (
                <p className="px-1 pb-2 text-sm text-red-600">{error || recorder.error}</p>
            )}

            <form onSubmit={submit} className="flex items-center gap-2 border-t border-slate-200 pt-3">
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
