<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\CourtFloorType;
use App\Enums\CourtType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateCourtRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'court_type' => ['required', 'string', Rule::enum(CourtType::class)],
            'floor_type' => ['nullable', 'string', Rule::enum(CourtFloorType::class)],
            'hourly_rate' => ['required', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
