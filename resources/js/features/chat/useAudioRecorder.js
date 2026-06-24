import { useCallback, useRef, useState } from 'react';

/**
 * Enregistrement audio via MediaRecorder. start() démarre la capture micro,
 * stop() renvoie le Blob audio (webm). À utiliser pour la question orale (STT).
 */
export function useAudioRecorder() {
    const [recording, setRecording] = useState(false);
    const [error, setError] = useState(null);
    const mediaRecorderRef = useRef(null);
    const chunksRef = useRef([]);

    const start = useCallback(async () => {
        setError(null);
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            const recorder = new MediaRecorder(stream);
            chunksRef.current = [];

            recorder.ondataavailable = (event) => {
                if (event.data.size > 0) chunksRef.current.push(event.data);
            };

            recorder.start();
            mediaRecorderRef.current = recorder;
            setRecording(true);
        } catch (e) {
            setError("Micro inaccessible. Vérifiez les autorisations du navigateur.");
            throw e;
        }
    }, []);

    const stop = useCallback(() => {
        return new Promise((resolve) => {
            const recorder = mediaRecorderRef.current;
            if (!recorder) {
                resolve(null);
                return;
            }

            recorder.onstop = () => {
                const blob = new Blob(chunksRef.current, { type: 'audio/webm' });
                recorder.stream.getTracks().forEach((track) => track.stop());
                setRecording(false);
                resolve(blob);
            };

            recorder.stop();
        });
    }, []);

    return { recording, error, start, stop };
}
