<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\PayServiceRequestRequest;
use App\Http\Requests\RespondServiceRequestRequest;
use App\Http\Requests\StoreServiceRequestRequest;
use App\Http\Resources\PaymentResource;
use App\Http\Resources\ServiceRequestResource;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestDocument;
use App\Services\ServiceRequestManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ServiceRequestController extends Controller
{
    public function __construct(private readonly ServiceRequestManager $manager) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return ServiceRequestResource::collection(
            $request->user()->serviceRequests()
                ->with('service')
                ->when($request->string('status')->value(), fn ($q, $status) => $q->where('status', $status))
                ->latest()
                ->paginate($request->integer('per_page', 15))
        );
    }

    public function store(StoreServiceRequestRequest $request): JsonResponse
    {
        $serviceRequest = $this->manager->create(
            $request->user(), $request->service(), $request->answers(), $request->documents(), 'api'
        );

        return $this->detail($serviceRequest)
            ->additional(['next_step' => $serviceRequest->awaitingPayment()
                ? ['action' => 'pay', 'method' => 'POST', 'url' => route('api.v1.requests.pay', $serviceRequest)]
                : null])
            ->response()
            ->setStatusCode(201);
    }

    public function show(ServiceRequest $serviceRequest): ServiceRequestResource
    {
        Gate::authorize('view', $serviceRequest);

        return $this->detail($serviceRequest);
    }

    public function pay(PayServiceRequestRequest $request, ServiceRequest $serviceRequest): JsonResponse
    {
        $payment = $this->manager->pay(
            $serviceRequest, $request->user(), $request->validated('card_number'), $request->validated('card_holder')
        );

        return PaymentResource::make($payment)
            ->additional(['request_status' => $serviceRequest->fresh()?->status->toArray()])
            ->response()
            ->setStatusCode($payment->failure_reason ? 402 : 201);
    }

    public function respond(RespondServiceRequestRequest $request, ServiceRequest $serviceRequest): ServiceRequestResource
    {
        $this->manager->respond($serviceRequest, $request->user(), $request->validated('comment'), $request->documents());

        return $this->detail($serviceRequest);
    }

    public function cancel(Request $request, ServiceRequest $serviceRequest): ServiceRequestResource
    {
        Gate::authorize('update', $serviceRequest);

        $this->manager->cancel($serviceRequest, $request->user());

        return $this->detail($serviceRequest);
    }

    public function document(ServiceRequestDocument $document): StreamedResponse
    {
        Gate::authorize('view', $document->serviceRequest);

        return Storage::disk('local')->download($document->path, $document->original_name);
    }

    private function detail(ServiceRequest $serviceRequest): ServiceRequestResource
    {
        return ServiceRequestResource::make($serviceRequest->load([
            'service',
            'documents' => fn ($q) => $q->latest('id'),
            'payments',
            'activities' => fn ($q) => $q->where('is_internal', false)->with('user'),
        ]));
    }
}
