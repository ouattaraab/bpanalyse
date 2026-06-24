import React, { useEffect, useRef, useState } from 'react';
import Reveal from 'reveal.js';
import 'reveal.js/reveal.css';
import 'reveal.js/theme/white.css';
import { useNarration } from './useNarration';

/**
 * Lecteur de présentation express : slides Reveal.js + narration synchronisée
 * (la slide avance à la fin de la narration). Story 3.3 + 3.4 (texte affiché).
 */
export default function PresentationPlayer({ presentation, onClose }) {
    const slides = presentation?.slides ?? [];
    const containerRef = useRef(null);
    const deckRef = useRef(null);
    const stopRef = useRef(false);
    const indexRef = useRef(0);

    const [ready, setReady] = useState(false);
    const [currentIndex, setCurrentIndex] = useState(0);
    const [playing, setPlaying] = useState(false);

    const { speak, cancel } = useNarration();

    // Initialise Reveal une fois.
    useEffect(() => {
        if (!containerRef.current) return undefined;
        const deck = new Reveal(containerRef.current, {
            embedded: true,
            controls: true,
            progress: true,
            slideNumber: 'c/t',
            transition: 'slide',
            keyboard: true,
        });
        deck.initialize().then(() => setReady(true));
        deckRef.current = deck;

        return () => {
            try {
                deck.destroy();
            } catch {
                // ignore
            }
        };
    }, []);

    // Garde l'index courant synchronisé avec Reveal.
    useEffect(() => {
        indexRef.current = currentIndex;
        if (ready) deckRef.current?.slide(currentIndex);
    }, [currentIndex, ready]);

    const stop = () => {
        stopRef.current = true;
        cancel();
        setPlaying(false);
    };

    const play = async () => {
        if (playing) return;
        setPlaying(true);
        stopRef.current = false;

        for (let i = indexRef.current; i < slides.length; i++) {
            if (stopRef.current) break;
            setCurrentIndex(i);
            // eslint-disable-next-line no-await-in-loop
            await speak(slides[i]?.narration);
            if (stopRef.current) break;
        }

        setPlaying(false);
    };

    const goTo = (index) => {
        stop();
        setCurrentIndex(Math.max(0, Math.min(index, slides.length - 1)));
    };

    const close = () => {
        stop();
        onClose?.();
    };

    const current = slides[currentIndex] ?? {};

    return (
        <div className="fixed inset-0 z-50 flex flex-col bg-slate-900/95 p-4">
            <div className="mb-3 flex items-center justify-between text-white">
                <div className="text-sm">
                    <span className="font-medium">Présentation express</span>
                    <span className="ml-2 text-slate-300">
                        {currentIndex + 1} / {slides.length}
                    </span>
                </div>
                <button onClick={close} className="rounded px-3 py-1 text-sm hover:bg-white/10" type="button">
                    Fermer ✕
                </button>
            </div>

            <div className="flex-1 overflow-hidden rounded-lg bg-white">
                <div className="reveal h-full" ref={containerRef}>
                    <div className="slides">
                        {slides.map((slide) => (
                            <section key={slide.slide_id} data-slide-index={slide.slide_index}>
                                <h3 className="text-xl font-semibold">
                                    {slide.title || `Slide ${slide.slide_index ?? ''}`}
                                </h3>
                                <pre className="mx-auto mt-4 max-w-3xl whitespace-pre-wrap text-left text-sm text-slate-700">
                                    {slide.markdown}
                                </pre>
                            </section>
                        ))}
                    </div>
                </div>
            </div>

            {/* Narration affichée à l'écrit (story 3.4). */}
            <div className="mt-3 rounded-lg bg-white/10 px-4 py-3 text-sm text-white">
                <p className="text-slate-200">{current.narration}</p>
            </div>

            <div className="mt-3 flex items-center justify-center gap-2">
                <button
                    onClick={() => goTo(currentIndex - 1)}
                    disabled={currentIndex === 0}
                    className="rounded-full bg-white/10 px-4 py-2 text-sm text-white disabled:opacity-30"
                    type="button"
                >
                    ◀ Précédente
                </button>
                {playing ? (
                    <button onClick={stop} className="rounded-full bg-white px-5 py-2 text-sm font-medium text-slate-900" type="button">
                        ⏸ Pause
                    </button>
                ) : (
                    <button onClick={play} className="rounded-full bg-indigo-500 px-5 py-2 text-sm font-medium text-white" type="button">
                        ▶ Lire la narration
                    </button>
                )}
                <button
                    onClick={() => goTo(currentIndex + 1)}
                    disabled={currentIndex >= slides.length - 1}
                    className="rounded-full bg-white/10 px-4 py-2 text-sm text-white disabled:opacity-30"
                    type="button"
                >
                    Suivante ▶
                </button>
            </div>
        </div>
    );
}
