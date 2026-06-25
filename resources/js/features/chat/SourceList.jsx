import React from 'react';

/** Sources citées d'une réponse (slide / section). */
export default function SourceList({ sources }) {
    if (!sources || sources.length === 0) return null;

    return (
        <div className="mt-2.5 flex flex-wrap gap-1.5">
            {sources.map((source, index) => (
                <span
                    key={source.chunk_id ?? index}
                    className="inline-flex items-center rounded-full bg-brand-50 px-2 py-0.5 text-xs text-brand-700"
                    title={source.caption ?? ''}
                >
                    {source.slide_index != null ? `slide ${source.slide_index}` : 'source'}
                    {source.section ? ` · ${source.section}` : ''}
                </span>
            ))}
        </div>
    );
}
