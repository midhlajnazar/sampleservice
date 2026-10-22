<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ServiceController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/services/Index', [
            'services' => ServiceResource::collection(Service::withCount('requests')->orderBy('sort_order')->get())->resolve(),
        ]);
    }

    public function edit(Service $service): Response
    {
        return Inertia::render('admin/services/Edit', [
            'service' => ServiceResource::make($service)->withSchema()->resolve(),
        ]);
    }

    public function update(Request $request, Service $service): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'summary' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'base_fee' => ['required', 'numeric', 'min:0'],
            'vat_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'processing_days' => ['required', 'integer', 'min:1', 'max:365'],
            'is_active' => ['boolean'],
            'form_schema' => ['required', 'json'],
            'document_schema' => ['required', 'json'],
        ]);

        $service->update([
            ...$validated,
            'form_schema' => json_decode($validated['form_schema'], true),
            'document_schema' => json_decode($validated['document_schema'], true),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$service->name} updated."]);

        return to_route('admin.services.index');
    }

    public function toggle(Service $service): RedirectResponse
    {
        $service->update(['is_active' => ! $service->is_active]);

        Inertia::flash('toast', ['type' => 'success', 'message' => $service->name.($service->is_active ? ' enabled.' : ' disabled.')]);

        return back();
    }
}
