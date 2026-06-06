<?php

namespace App\Http\Requests\Master\User;

use App\Domain\UserAccess\Models\Role;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
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
        if ($this->has('role_id')) {
            try {
                $this->merge([
                    'role_id' => (int) Crypt::decryptString($this->role_id),
                ]);
            } catch (DecryptException $e) {
                // Ignore, let the exists validation fail
            }
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
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->withoutTrashed()],
            'name' => ['required', 'string', 'max:255'],
            'role_id' => [
                'required',
                'exists:roles,id',
                function ($attribute, $value, $fail) {
                    $role = Role::find($value);
                    $currentUser = auth()->user();

                    if ($role && $role->name === 'super_admin') {
                        if (! $currentUser || ! $currentUser->role || $currentUser->role->name !== 'super_admin') {
                            $fail('You do not have permission to assign the super admin role.');
                        }
                    }
                },
            ],
        ];
    }
}
