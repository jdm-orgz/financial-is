<?php

namespace App\Http\Requests\Transaction;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionSystemIncomeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'system_incomes' => ['required', 'array', 'min:1'],
            'system_incomes.*.chair_id' => ['required', 'string'],
            'system_incomes.*.amount' => ['required', 'numeric', 'min:1'],
        ];
    }
}
