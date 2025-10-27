<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChargeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ride_id' => ['required', 'string'],
            'price' => ['required', 'numeric', 'gt:0'],
            'payment_method_type' => ['required', Rule::in(['amex', 'diners', 'mastercard'])],
            'currency' => ['required', 'string'],
            'card_token' => ['required', 'string'],
        ];
    }
}
