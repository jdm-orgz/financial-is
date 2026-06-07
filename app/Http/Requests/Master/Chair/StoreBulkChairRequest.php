<?php

namespace App\Http\Requests\Master\Chair;

use Illuminate\Foundation\Http\FormRequest;

class StoreBulkChairRequest extends FormRequest
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
            'chairs_count' => [
                'required',
                'integer',
                'min:1',
                'max:1000', // reasonable limit to prevent abuse
            ],
        ];
    }
}
