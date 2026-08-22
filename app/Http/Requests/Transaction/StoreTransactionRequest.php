<?php

namespace App\Http\Requests\Transaction;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Crypt;

class StoreTransactionRequest extends FormRequest
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
            'outlet_id' => ['required', 'string'],
            'date' => ['required', 'date'],
        ];
    }

    /**
     * Decrypt the outlet_id after validation.
     */
    public function decryptedOutletId(): ?string
    {
        try {
            return (string) Crypt::decryptString($this->validated('outlet_id'));
        } catch (DecryptException $e) {
            return null;
        }
    }
}
