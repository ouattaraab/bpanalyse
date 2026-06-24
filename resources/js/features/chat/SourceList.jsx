import React from 'react';

/** Affiche les sources citées d'une réponse (slide / section / type). */
export default function SourceList({ sources }) {
    if (!sources || sources.length === 0) return null;

    return (
        <div className="mt-2 flex flex-wrap gap-1.5">
            {sources.map((source, index) => (
                <span
                    key={`${source.chunk_id ?? index}`}
                    className="inline-flex items-center rounded-full bg-indigo-50 px-2 py-0.5 text-xs text-indigo-700"
                    title={source.caption ?? ''}
                >
                    {source.slide_index != null ? `slide ${source.slide_index}` : 'source'}
                    {source.section ? ` · ${source.section}` : ''}
                </span>
            ))}
        </div>
    );
}
