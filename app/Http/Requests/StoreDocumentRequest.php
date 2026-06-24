<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation de l'upload d'un BP : formats autorisés (PDF/PPTX), taille bornée,
 * tenant existant. Les données restent dans le corps de la requête (multipart),
 * jamais en query string (règle : pas de données sensibles dans l'URL).
 */
class StoreDocumentRequest extends FormRequest
{
    /** Taille maximale du fichier (Ko). 50 Mo. */
    private const MAX_KILOBYTES = 51200;

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            // Optionnel : à défaut, le document est rattaché au tenant « default ».
            'tenant_id' => ['nullable', 'integer', 'exists:tenants,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'file' => ['required', 'file', 'mimes:pdf,pptx', 'max:'.self::MAX_KILOBYTES],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'file.mimes' => 'Le document doit être au format PDF ou PPTX.',
            'file.max' => 'Le document dépasse la taille maximale autorisée (50 Mo).',
            'tenant_id.exists' => 'Tenant inconnu.',
        ];
    }
}
