<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Lancement d'un débat du board sur une question.
 */
class StartDebateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'question' => ['required', 'string', 'min:3', 'max:2000'],
            'max_rounds' => ['nullable', 'integer', 'min:1', 'max:4'],
        ];
    }
}
