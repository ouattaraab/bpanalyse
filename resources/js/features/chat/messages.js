// Fabriques pures de messages de chat (faciles à tester).

export function userMessage(text) {
    return { role: 'user', text };
}

export function assistantMessage(answer) {
    return {
        role: 'assistant',
        text: answer?.answer ?? '',
        sources: answer?.sources ?? [],
        interactionId: answer?.interaction_id ?? null,
    };
}
