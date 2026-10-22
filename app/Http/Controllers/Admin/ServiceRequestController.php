<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DocumentStatus;
use App\Enums\RequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\TransitionServiceRequestRequest;
use App\Http\Resources\ServiceRequestResource;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestDocument;
use App\Services\ServiceRequestManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ServiceRequestController extends Controller
{
    public function __construct(private readonly ServiceRequestManager $manager) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'status', 'service']);

        $requests = ServiceRequest::query()
            ->with(['service', 'user', 'assignee'])
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['service'] ?? null, fn ($q, $slug) => $q->whereRelation('service', 'slug', $slug))
            ->when($filters['search'] ?? null, fn ($q, $search) => $q->where(fn ($q) => $q
                ->where('reference', 'like', "%{$search}%")
                ->orWhereRelation('user', 'name', 'like', "%{$search}%")
                ->orWhereRelation('user', 'email', 'like', "%{$search}%")))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/requests/Index', [
            'requests' => ServiceRequestResource::collection($requests),
            'statuses' => RequestStatus::options(),
            'services' => Service::orderBy('name')->get(['name', 'slug']),
            'filters' => $filters,
        ]);
    }

    public function show(ServiceRequest $serviceRequest): Response
    {
        $serviceRequest->load([
            'service', 'user', 'assignee',
            'documents' => fn ($q) => $q->latest('id'),
            'payments',
            'activities.user',
        ]);

        return Inertia::render('admin/requests/Show', [
            'serviceRequest' => ServiceRequestResource::make($serviceRequest)->resolve(),
            'transitions' => array_map(fn (RequestStatus $s) => $s->toArray(), $serviceRequest->status->adminTransitions()),
        ]);
    }

    public function transition(TransitionServiceRequestRequest $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        $this->manager->transition($serviceRequest, $request->status(), $request->user(), $request->validated('comment'));

        Inertia::flash('toast', ['type' => 'success', 'message' => "Request moved to {$request->status()->label()}."]);

        return back();
    }

    public function note(Request $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        $validated = $request->validate(['note' => ['required', 'string', 'max:2000']]);

        $this->manager->addNote($serviceRequest, $request->user(), $validated['note']);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Internal note added.']);

        return back();
    }

    public function reviewDocument(Request $request, ServiceRequestDocument $document): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in([DocumentStatus::Accepted->value, DocumentStatus::Rejected->value])],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $this->manager->reviewDocument($document, DocumentStatus::from($validated['status']), $request->user(), $validated['note'] ?? null);

        return back();
    }
}
