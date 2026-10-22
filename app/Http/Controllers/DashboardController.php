<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Http\Resources\ServiceRequestResource;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $requests = $request->user()->serviceRequests();

        return Inertia::render('Dashboard', [
            'stats' => [
                'total' => (clone $requests)->count(),
                'awaiting_payment' => (clone $requests)->where('status', RequestStatus::PendingPayment)->count(),
                'action_required' => (clone $requests)->where('status', RequestStatus::InfoRequested)->count(),
                'in_progress' => (clone $requests)->whereIn('status', [RequestStatus::Submitted, RequestStatus::UnderReview])->count(),
                'completed' => (clone $requests)->whereIn('status', [RequestStatus::Approved, RequestStatus::Completed])->count(),
            ],
            'recent' => ServiceRequestResource::collection(
                (clone $requests)->with('service')->latest()->limit(5)->get()
            )->resolve(),
            'services' => ServiceResource::collection(Service::active()->get())->resolve(),
        ]);
    }
}
