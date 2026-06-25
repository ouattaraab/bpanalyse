<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Enregistrement d'un consentement au clonage vocal : explicite, limité
 * (finalité + durée), avec preuve écrite optionnelle.
 */
class StoreConsentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'person_name' => ['required', 'string', 'max:255'],
            'purpose' => ['required', 'string', 'max:500'],
            'legal_basis' => ['nullable', 'string', 'max:255'],
            'retention_until' => ['nullable', 'date', 'after:today'],
            'signed_document' => ['nullable', 'file', 'mimes:pdf,png,jpg,jpeg', 'max:10240'],
        ];
    }
}
