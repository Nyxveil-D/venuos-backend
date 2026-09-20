<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\CourtFloorType;
use App\Enums\CourtType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCourtRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:100'],
            'court_type' => ['sometimes', 'string', Rule::enum(CourtType::class)],
            'floor_type' => ['nullable', 'string', Rule::enum(CourtFloorType::class)],
            'hourly_rate' => ['sometimes', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
