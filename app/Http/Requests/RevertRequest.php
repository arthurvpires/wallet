<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RevertRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'transaction_id' => ['required', 'integer', 'exists:transactions,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'transaction_id.required' => 'O ID da transação é obrigatório.',
            'transaction_id.integer' => 'O ID da transação deve ser um número inteiro.',
            'transaction_id.exists' => 'A transação não existe.',
        ];
    }

    public function transactionId(): int
    {
        return (int) $this->validated('transaction_id');
    }
}
