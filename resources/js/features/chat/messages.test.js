import { describe, expect, it } from 'vitest';
import { assistantMessage, userMessage } from './messages';

describe('messages', () => {
    it('crée un message utilisateur', () => {
        expect(userMessage('Quel est le CA ?')).toEqual({ role: 'user', text: 'Quel est le CA ?' });
    });

    it('mappe une réponse assistant avec ses sources', () => {
        const message = assistantMessage({ answer: 'Le CA 2026 est 150.', sources: [{ slide_index: 4 }] });

        expect(message.role).toBe('assistant');
        expect(message.text).toBe('Le CA 2026 est 150.');
        expect(message.sources).toHaveLength(1);
    });

    it('applique des valeurs par défaut sûres', () => {
        expect(assistantMessage(undefined)).toEqual({ role: 'assistant', text: '', sources: [] });
    });
});
