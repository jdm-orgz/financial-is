<?php

namespace App\Http\Requests\Master\User;

use App\Domain\UserAccess\Models\Role;
use App\Domain\UserAccess\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
        try {
            $userId = Crypt::decryptString($this->route('user'));
        } catch (DecryptException $e) {
            $userId = null;
        }

        return [
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($userId)->withoutTrashed()],
            'name' => ['required', 'string', 'max:255'],
            'role_id' => [
                'required',
                'exists:roles,id',
                function ($attribute, $value, $fail) use ($userId) {
                    $role = Role::find($value);
                    $currentUser = auth()->user();

                    if ($role && $role->name === 'super_admin') {
                        if (! $currentUser || ! $currentUser->role || $currentUser->role->name !== 'super_admin') {
                            $fail('You do not have permission to assign the super admin role.');
                        }
                    }

                    if ($userId && $currentUser && $userId === (string) $currentUser->id && $currentUser->role?->name !== 'super_admin') {
                        $targetUser = User::find($userId);
                        if ($targetUser && $targetUser->role_id !== (int) $value) {
                            $fail('You cannot update your own role.');
                        }
                    }
                },
            ],
        ];
    }
}
