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
        <div className="scene-veil fixed inset-0 z-50 flex flex-col p-4 motion-safe:animate-fade-in sm:p-6">
            <div className="mx-auto flex w-full max-w-4xl items-center justify-between pb-3 text-white">
                <div>
                    <p className="text-xs uppercase tracking-[0.18em] text-white/55">Présentation express</p>
                    <p className="font-display text-lg">
                        {currentIndex + 1}<span className="text-white/45"> / {slides.length}</span>
                    </p>
                </div>
                <button onClick={close} className="rounded-full px-3 py-1.5 text-sm text-white/80 hover:bg-white/10" type="button">
                    Fermer
                </button>
            </div>

            <div className="mx-auto w-full max-w-4xl flex-1 overflow-hidden rounded-2xl bg-surface shadow-lift">
                <div className="reveal h-full" ref={containerRef}>
                    <div className="slides">
                        {slides.map((slide) => (
                            <section key={slide.slide_id} data-slide-index={slide.slide_index}>
                                <h3 className="font-display text-2xl text-ink">
                                    {slide.title || `Slide ${slide.slide_index ?? ''}`}
                                </h3>
                                <pre className="mx-auto mt-4 max-w-3xl whitespace-pre-wrap text-left font-sans text-sm leading-relaxed text-ink-soft">
                                    {slide.markdown}
                                </pre>
                            </section>
                        ))}
                    </div>
                </div>
            </div>

            {/* Narration affichée à l'écrit (story 3.4). */}
            <div className="mx-auto mt-3 w-full max-w-4xl rounded-xl bg-white/10 px-4 py-3">
                <p className="text-sm leading-relaxed text-white/85">{current.narration}</p>
            </div>

            <div className="mx-auto mt-3 flex w-full max-w-4xl items-center justify-center gap-2">
                <button onClick={() => goTo(currentIndex - 1)} disabled={currentIndex === 0} className="rounded-full bg-white/10 px-4 py-2 text-sm text-white hover:bg-white/15 disabled:opacity-30" type="button">
                    Précédente
                </button>
                {playing ? (
                    <button onClick={stop} className="rounded-full bg-white px-6 py-2 text-sm font-medium text-ink" type="button">
                        Pause
                    </button>
                ) : (
                    <button onClick={play} className="rounded-full bg-brand-500 px-6 py-2 text-sm font-medium text-white hover:bg-brand-600" type="button">
                        Lire la narration
                    </button>
                )}
                <button onClick={() => goTo(currentIndex + 1)} disabled={currentIndex >= slides.length - 1} className="rounded-full bg-white/10 px-4 py-2 text-sm text-white hover:bg-white/15 disabled:opacity-30" type="button">
                    Suivante
                </button>
            </div>
        </div>
    );
}
