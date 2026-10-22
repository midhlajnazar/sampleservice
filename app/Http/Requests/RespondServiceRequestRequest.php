<?php

namespace App\Http\Requests;

use App\Models\ServiceRequest;
use App\Services\ServiceFormValidator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

/** Applicant reply to an "information requested" status: a comment and/or replacement documents. */
class RespondServiceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->serviceRequest()) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $request = $this->serviceRequest();

        return ['comment' => ['nullable', 'string', 'max:2000']]
            + app(ServiceFormValidator::class)->documentRules($request->service, $request->form_data, allRequiredOptional: true);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return app(ServiceFormValidator::class)->attributes($this->serviceRequest()->service);
    }

    public function serviceRequest(): ServiceRequest
    {
        /** @var ServiceRequest */
        return $this->route('serviceRequest');
    }

    /**
     * @return array<string, UploadedFile>
     */
    public function documents(): array
    {
        return array_filter((array) $this->file('documents', []), fn ($file) => $file instanceof UploadedFile);
    }
}
