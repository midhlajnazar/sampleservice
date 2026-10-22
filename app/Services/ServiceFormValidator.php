<?php

namespace App\Services;

use App\Models\Service;
use Illuminate\Validation\Rule;

/**
 * Turns a service's form/document schemas into Laravel validation rules, so
 * the web wizard and the API validate an application identically.
 */
class ServiceFormValidator
{
    /**
     * Rules for the `fields.*` answers.
     *
     * @param  array<string, mixed>  $input  the submitted answers, used to evaluate show_if
     * @return array<string, mixed>
     */
    public function fieldRules(Service $service, array $input): array
    {
        $rules = ['fields' => ['required', 'array']];

        foreach ($service->fields() as $name => $field) {
            $key = "fields.{$name}";

            if (! $this->isVisible($field, $input)) {
                $rules[$key] = ['exclude'];

                continue;
            }

            $required = ($field['required'] ?? false) ? 'required' : 'nullable';
            $options = array_column($field['options'] ?? [], 'value');

            $rules[$key] = match ($field['type']) {
                'email' => [$required, 'email', 'max:255'],
                'number' => array_filter([$required, 'numeric', isset($field['min']) ? "min:{$field['min']}" : null, isset($field['max']) ? "max:{$field['max']}" : null]),
                'date' => array_filter([$required, 'date', $field['date_rule'] ?? null]),
                'textarea' => [$required, 'string', 'max:2000'],
                'select', 'radio' => [$required, 'string', Rule::in($options)],
                'checkbox' => $options
                    ? [$required, 'array', ...(($field['required'] ?? false) ? ['min:1'] : [])]
                    : [($field['required'] ?? false) ? 'accepted' : 'nullable', 'boolean'],
                default => array_filter([$required, 'string', 'max:255', isset($field['pattern']) ? "regex:/{$field['pattern']}/" : null]),
            };

            if ($field['type'] === 'checkbox' && $options) {
                $rules["{$key}.*"] = ['string', Rule::in($options)];
            }
        }

        return $rules;
    }

    /**
     * Rules for the `documents.*` uploads.
     *
     * @param  array<string, mixed>  $input
     * @param  bool  $allRequiredOptional  true when re-uploading after an info request
     * @return array<string, mixed>
     */
    public function documentRules(Service $service, array $input, bool $allRequiredOptional = false): array
    {
        $rules = ['documents' => [$allRequiredOptional ? 'nullable' : 'required', 'array']];

        foreach ($service->document_schema as $doc) {
            $key = "documents.{$doc['key']}";

            if (! $this->isVisible($doc, $input)) {
                $rules[$key] = ['exclude'];

                continue;
            }

            $required = ($doc['required'] ?? false) && ! $allRequiredOptional ? 'required' : 'nullable';
            $rules[$key] = [$required, 'file', 'mimes:'.implode(',', $doc['mimes']), 'max:'.$doc['max_kb']];
        }

        return $rules;
    }

    /**
     * Human-friendly attribute names for error messages.
     *
     * @return array<string, string>
     */
    public function attributes(Service $service): array
    {
        $attributes = [];

        foreach ($service->fields() as $name => $field) {
            $attributes["fields.{$name}"] = $field['label'];
        }

        foreach ($service->document_schema as $doc) {
            $attributes["documents.{$doc['key']}"] = $doc['label'];
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $definition
     * @param  array<string, mixed>  $input
     */
    public function isVisible(array $definition, array $input): bool
    {
        if (! isset($definition['show_if'])) {
            return true;
        }

        $expected = (array) $definition['show_if']['value'];
        $actual = $input[$definition['show_if']['field']] ?? null;

        return array_intersect((array) $actual, $expected) !== [];
    }
}
