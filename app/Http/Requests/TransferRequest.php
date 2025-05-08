<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransferRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'amount' => ['required', 'integer', 'min:1'],
            'recipient' => ['required', 'email', 'exists:users,email'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'O campo valor é obrigatório.',
            'amount.integer' => 'O valor deve ser um número inteiro.',
            'amount.min' => 'O valor deve ser maior que zero.',
            'recipient.required' => 'O campo destinatário é obrigatório.',
            'recipient.email' => 'O destinatário deve ser um email válido.',
            'recipient.exists' => 'O email informado não está registrado.',
        ];
    }

    public function amount(): int
    {
        return (int) $this->validated('amount');
    }

    public function recipient(): string
    {
        return (string) $this->validated('recipient');
    }
}
