<?php

namespace App\Http\Requests\Master\LinkedOutletUser;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\Rule;

class StoreLinkedOutletUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $mergeData = [];

        if ($this->has('user_id')) {
            try {
                $mergeData['user_id'] = (string) Crypt::decryptString($this->user_id);
            } catch (DecryptException $e) {
                // Ignore
            }
        }

        if ($this->has('outlet_ids') && is_array($this->outlet_ids)) {
            $decryptedOutletIds = [];
            foreach ($this->outlet_ids as $outletId) {
                try {
                    $decryptedOutletIds[] = (string) Crypt::decryptString($outletId);
                } catch (DecryptException $e) {
                    // Ignore
                }
            }
            $mergeData['outlet_ids'] = $decryptedOutletIds;
        }

        if (! empty($mergeData)) {
            $this->merge($mergeData);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'string', 'exists:users,id'],
            'outlet_ids' => ['required', 'array', 'min:1'],
            'outlet_ids.*' => [
                'required',
                'string',
                'exists:outlets,id',
                'distinct',
                Rule::unique('linked_outlets_users', 'outlet_id')
                    ->where('user_id', $this->user_id)
                    ->whereNull('deleted_at'),
            ],
            'is_active' => ['nullable', 'in:0,1'],
        ];
    }
}
