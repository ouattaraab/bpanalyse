<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Upload d'un audio à transcrire (question orale).
 */
class TranscribeAudioRequest extends FormRequest
{
    /** Taille max de l'audio (Ko). 25 Mo. */
    private const MAX_KILOBYTES = 25600;

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'audio' => [
                'required',
                'file',
                'max:'.self::MAX_KILOBYTES,
                'mimetypes:audio/wav,audio/x-wav,audio/mpeg,audio/mp4,audio/m4a,audio/webm,audio/ogg,audio/flac',
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'audio.mimetypes' => 'Le fichier doit être un audio (wav, mp3, m4a, webm, ogg, flac).',
        ];
    }
}
