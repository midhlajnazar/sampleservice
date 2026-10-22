<?php

namespace App\Http\Requests;

use App\Enums\RequestStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransitionServiceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('process', $this->route('serviceRequest')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(RequestStatus::class)],
            'comment' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function status(): RequestStatus
    {
        return RequestStatus::from($this->validated('status'));
    }
}
