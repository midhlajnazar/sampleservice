<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use App\Services\FeeCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ServiceController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return ServiceResource::collection(Service::active()->get());
    }

    public function show(Service $service): ServiceResource
    {
        abort_unless($service->is_active, 404);

        return ServiceResource::make($service)->withSchema();
    }

    /** Quote the fee for a set of answers without creating anything. */
    public function quote(Request $request, Service $service, FeeCalculator $fees): JsonResponse
    {
        abort_unless($service->is_active, 404);

        return response()->json(['data' => [...$fees->calculate($service, (array) $request->input('fields', [])), 'currency' => 'AED']]);
    }
}
