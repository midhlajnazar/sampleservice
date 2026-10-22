<?php

namespace App\Http\Requests;

use App\Models\Service;
use App\Services\ServiceFormValidator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

/**
 * Validates a new application against the chosen service's schemas. The
 * service comes from the route ({service}) on the web, or a `service` slug in
 * the body for the API.
 */
class StoreServiceRequestRequest extends FormRequest
{
    private ?Service $resolvedService = null;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $service = $this->service();

        if (! $service) {
            return ['service' => ['required', 'exists:services,slug']];
        }

        $validator = app(ServiceFormValidator::class);
        $fields = (array) $this->input('fields', []);

        return $validator->fieldRules($service, $fields) + $validator->documentRules($service, $fields);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->service() ? app(ServiceFormValidator::class)->attributes($this->service()) : [];
    }

    public function service(): ?Service
    {
        if ($this->resolvedService) {
            return $this->resolvedService;
        }

        $routeService = $this->route('service');

        return $this->resolvedService = $routeService instanceof Service
            ? $routeService
            : Service::active()->where('slug', $this->input('service'))->first();
    }

    /**
     * @return array<string, mixed>
     */
    public function answers(): array
    {
        return (array) $this->validated('fields', []);
    }

    /**
     * @return array<string, UploadedFile>
     */
    public function documents(): array
    {
        return array_filter((array) $this->file('documents', []), fn ($file) => $file instanceof UploadedFile);
    }
}
