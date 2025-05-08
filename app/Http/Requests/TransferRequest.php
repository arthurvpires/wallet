<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransferRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01','regex:/^\d*\.?\d+$/'],
            'recipient' => ['required', 'email', 'exists:users,email'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'O campo valor é obrigatório.',
            'amount.numeric' => 'O valor deve ser um número.',
            'amount.min' => 'O valor deve ser maior que zero.',
            'amount.regex' => 'O valor deve ser um número positivo.',
            'recipient.required' => 'O campo destinatário é obrigatório.',
            'recipient.email' => 'O destinatário deve ser um email válido.',
            'recipient.exists' => 'O email informado não está registrado.',
        ];
    }

    public function amount(): float
    {
        return (float) $this->validated('amount');
    }


    public function recipient(): string
    {
        return (string) $this->validated('recipient');
    }
}
