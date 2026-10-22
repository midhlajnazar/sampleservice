<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

class PayServiceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('serviceRequest')) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['card_number' => preg_replace('/\D/', '', (string) $this->input('card_number'))]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'card_holder' => ['required', 'string', 'max:100'],
            'card_number' => ['required', 'digits_between:13,19'],
            'expiry' => ['required', 'regex:/^(0[1-9]|1[0-2])\/\d{2}$/'],
            'cvv' => ['required', 'digits_between:3,4'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->has('expiry')) {
                return;
            }

            [$month, $year] = explode('/', (string) $this->input('expiry'));

            if (Carbon::createFromDate(2000 + (int) $year, (int) $month, 1)->endOfMonth()->isPast()) {
                $validator->errors()->add('expiry', 'The card has expired.');
            }
        }];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['expiry.regex' => 'Use the format MM/YY.'];
    }
}
