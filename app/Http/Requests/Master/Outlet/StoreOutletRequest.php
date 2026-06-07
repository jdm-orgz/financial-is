<?php

namespace App\Http\Requests\Master\Outlet;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOutletRequest extends FormRequest
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
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('outlets', 'name')->withoutTrashed()],
            'address' => ['nullable', 'string', 'max:255'],
            'chairs_count' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'prefix' => ['required', 'string', 'max:255', Rule::unique('chair_prefixes', 'prefix')],
        ];
    }
}
