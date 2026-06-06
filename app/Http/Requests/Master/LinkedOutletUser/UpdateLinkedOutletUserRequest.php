<?php

namespace App\Http\Requests\Master\LinkedOutletUser;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Crypt;

class UpdateLinkedOutletUserRequest extends FormRequest
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

        // user_id is not allowed to be updated

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
            // user_id is not allowed to be updated
            'outlet_ids' => ['required', 'array', 'min:0'],
            'outlet_ids.*' => ['required', 'string', 'exists:outlets,id'],
            'is_active' => ['nullable', 'in:0,1'],
        ];
    }
}
