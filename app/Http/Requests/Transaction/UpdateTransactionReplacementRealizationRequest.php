<?php

namespace App\Http\Requests\Transaction;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateTransactionReplacementRealizationRequest extends FormRequest
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
            'problem_chair_id' => ['sometimes', 'string'],
            'replacement_chair_id' => ['sometimes', 'string'],
            'payment_method' => ['sometimes', new Enum(PaymentMethod::class)],
            'amount' => ['sometimes', 'numeric', 'min:1'],
            'proof_image' => ['nullable', 'image', 'max:5120'],
            'proof_video' => ['nullable', 'file', 'mimetypes:video/mp4,video/quicktime,video/x-msvideo', 'max:51200'],
        ];
    }
}
