<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreModeracaoRequest extends FormRequest
{
    /**
     * Qualquer participante autenticado pode denunciar (a rota já exige auth).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * TICKET #015: regras de validação do motivo da denúncia.
     */
    public function rules(): array
    {
        return [
            'motivo' => ['required', 'string', 'min:10', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'motivo.required' => 'Informe o motivo da denúncia.',
            'motivo.min' => 'O motivo precisa ter pelo menos :min caracteres.',
            'motivo.max' => 'O motivo pode ter no máximo :max caracteres.',
        ];
    }
}
