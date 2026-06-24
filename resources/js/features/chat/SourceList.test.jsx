import React from 'react';
import { describe, expect, it } from 'vitest';
import { render, screen } from '@testing-library/react';
import SourceList from './SourceList';

describe('SourceList', () => {
    it('affiche les sources citées (slide et section)', () => {
        render(<SourceList sources={[{ chunk_id: 1, slide_index: 4, section: 'Projections' }]} />);

        expect(screen.getByText(/slide 4/)).toBeInTheDocument();
        expect(screen.getByText(/Projections/)).toBeInTheDocument();
    });

    it('ne rend rien en l\'absence de sources', () => {
        const { container } = render(<SourceList sources={[]} />);

        expect(container).toBeEmptyDOMElement();
    });
});
