<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\TransitionServiceRequestRequest;
use App\Http\Resources\ServiceRequestResource;
use App\Models\ServiceRequest;
use App\Services\ServiceRequestManager;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ServiceRequestController extends Controller
{
    public function __construct(private readonly ServiceRequestManager $manager) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return ServiceRequestResource::collection(
            ServiceRequest::with(['service', 'user'])
                ->when($request->string('status')->value(), fn ($q, $status) => $q->where('status', $status))
                ->when($request->string('service')->value(), fn ($q, $slug) => $q->whereRelation('service', 'slug', $slug))
                ->latest()
                ->paginate($request->integer('per_page', 15))
        );
    }

    public function show(ServiceRequest $serviceRequest): ServiceRequestResource
    {
        return ServiceRequestResource::make($serviceRequest->load(['service', 'user', 'assignee', 'documents', 'payments', 'activities.user']))
            ->additional(['allowed_transitions' => array_map(fn ($s) => $s->toArray(), $serviceRequest->status->adminTransitions())]);
    }

    public function transition(TransitionServiceRequestRequest $request, ServiceRequest $serviceRequest): ServiceRequestResource
    {
        $this->manager->transition($serviceRequest, $request->status(), $request->user(), $request->validated('comment'));

        return $this->show($serviceRequest);
    }
}
