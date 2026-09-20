<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WebhookPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'order_id' => ['required', 'string'],
            'transaction_status' => ['required', 'string', 'in:settlement,capture,expire,cancel,deny,pending'],
            'payment_type' => ['nullable', 'string'],
            'transaction_id' => ['nullable', 'string'],
        ];
    }
}
