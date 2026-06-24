import { useCallback } from 'react';

/**
 * Narration vocale via la synthèse du navigateur (SpeechSynthesis, fr-FR).
 * speak() résout sa promesse à la fin de la lecture (ou en cas d'erreur/absence
 * d'API), ce qui permet d'enchaîner les slides.
 *
 * Voix clonée du dirigeant (ElevenLabs) : prévue en Phase 4, hors de ce lecteur.
 */
export function useNarration() {
    const speak = useCallback((text) => {
        return new Promise((resolve) => {
            const synth = typeof window !== 'undefined' ? window.speechSynthesis : null;
            if (!synth || !text) {
                resolve();
                return;
            }

            const utterance = new SpeechSynthesisUtterance(text);
            utterance.lang = 'fr-FR';

            const frenchVoice = synth.getVoices().find((voice) => voice.lang?.toLowerCase().startsWith('fr'));
            if (frenchVoice) utterance.voice = frenchVoice;

            utterance.onend = () => resolve();
            utterance.onerror = () => resolve();

            synth.cancel();
            synth.speak(utterance);
        });
    }, []);

    const cancel = useCallback(() => {
        if (typeof window !== 'undefined') window.speechSynthesis?.cancel();
    }, []);

    return { speak, cancel };
}
