<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Http\Requests\RespondServiceRequestRequest;
use App\Http\Requests\StoreServiceRequestRequest;
use App\Http\Resources\ServiceRequestResource;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Services\ServiceRequestManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ServiceRequestController extends Controller
{
    public function __construct(private readonly ServiceRequestManager $manager) {}

    public function index(Request $request): Response
    {
        $requests = $request->user()->serviceRequests()
            ->with('service')
            ->when($request->string('status')->value(), fn ($q, $status) => $q->where('status', $status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('requests/Index', [
            'requests' => ServiceRequestResource::collection($requests),
            'statuses' => RequestStatus::options(),
            'filters' => ['status' => $request->query('status')],
        ]);
    }

    public function create(Request $request, Service $service): Response
    {
        abort_unless($service->is_active, 404);

        $user = $request->user();

        return Inertia::render('requests/Create', [
            'service' => ServiceResource::make($service)->withSchema()->resolve(),
            // Pre-fill common fields from the applicant's profile.
            'defaults' => array_filter([
                'contact_email' => $user->email,
                'contact_phone' => $user->phone,
                'owner_name' => $user->name,
                'owner_nationality' => $user->nationality,
                'nationality' => $user->nationality,
            ]),
        ]);
    }

    public function store(StoreServiceRequestRequest $request, Service $service): RedirectResponse
    {
        $serviceRequest = $this->manager->create($request->user(), $service, $request->answers(), $request->documents());

        if ($serviceRequest->awaitingPayment()) {
            Inertia::flash('toast', ['type' => 'success', 'message' => "Application {$serviceRequest->reference} saved. Complete payment to submit it."]);

            return to_route('payments.checkout', $serviceRequest);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => "Application {$serviceRequest->reference} submitted."]);

        return to_route('requests.show', $serviceRequest);
    }

    public function show(ServiceRequest $serviceRequest): Response
    {
        Gate::authorize('view', $serviceRequest);

        $serviceRequest->load([
            'service',
            'documents' => fn ($q) => $q->latest('id'),
            'payments',
            'activities' => fn ($q) => $q->where('is_internal', false)->with('user'),
        ]);

        return Inertia::render('requests/Show', [
            'serviceRequest' => ServiceRequestResource::make($serviceRequest)->resolve(),
            'documentSchema' => $serviceRequest->service->document_schema,
        ]);
    }

    public function respond(RespondServiceRequestRequest $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        $this->manager->respond($serviceRequest, $request->user(), $request->validated('comment'), $request->documents());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Thanks, your response was sent to the reviewer.']);

        return to_route('requests.show', $serviceRequest);
    }

    public function cancel(Request $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        Gate::authorize('update', $serviceRequest);

        $this->manager->cancel($serviceRequest, $request->user());

        Inertia::flash('toast', ['type' => 'info', 'message' => 'Application cancelled.']);

        return to_route('requests.show', $serviceRequest);
    }

    /** Printable receipt / certificate page. */
    public function print(ServiceRequest $serviceRequest, string $document): Response
    {
        Gate::authorize('view', $serviceRequest);

        abort_unless(in_array($document, ['receipt', 'certificate'], true), 404);
        abort_if($document === 'certificate' && ! $serviceRequest->certificate_number, 404);
        abort_if($document === 'receipt' && ! $serviceRequest->isPaid() && $serviceRequest->payments()->doesntExist(), 404);

        $serviceRequest->load(['service', 'user', 'payments']);

        return Inertia::render('print/'.ucfirst($document), [
            'serviceRequest' => ServiceRequestResource::make($serviceRequest)->resolve(),
        ]);
    }
}
