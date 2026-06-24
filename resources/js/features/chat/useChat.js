import { useCallback, useState } from 'react';
import { askQuestion, transcribeAudio } from '../../lib/api';
import { assistantMessage, userMessage } from './messages';

let seq = 0;
const nextId = () => {
    seq += 1;
    return `msg-${seq}`;
};

/**
 * État du chat d'une session : liste des messages, envoi d'une question,
 * transcription d'un audio. Toute la logique IA reste côté backend.
 */
export function useChat(sessionUuid) {
    const [messages, setMessages] = useState([]);
    const [pending, setPending] = useState(false);
    const [error, setError] = useState(null);

    const send = useCallback(
        async (question) => {
            const text = question?.trim();
            if (!text || pending) return;

            setError(null);
            setMessages((prev) => [...prev, { id: nextId(), ...userMessage(text) }]);
            setPending(true);

            try {
                const answer = await askQuestion(sessionUuid, text);
                setMessages((prev) => [...prev, { id: nextId(), ...assistantMessage(answer) }]);
            } catch (e) {
                setError(e.message || "La réponse n'a pas pu être obtenue.");
            } finally {
                setPending(false);
            }
        },
        [sessionUuid, pending]
    );

    const transcribe = useCallback(
        (blob) => transcribeAudio(sessionUuid, blob),
        [sessionUuid]
    );

    return { messages, pending, error, send, transcribe };
}
