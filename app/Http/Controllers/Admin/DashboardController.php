<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Enums\RequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceRequestResource;
use App\Models\Payment;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $byStatus = ServiceRequest::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return Inertia::render('admin/Dashboard', [
            'stats' => [
                'queue' => (int) ($byStatus[RequestStatus::Submitted->value] ?? 0),
                'under_review' => (int) ($byStatus[RequestStatus::UnderReview->value] ?? 0),
                'info_requested' => (int) ($byStatus[RequestStatus::InfoRequested->value] ?? 0),
                'approved' => (int) (($byStatus[RequestStatus::Approved->value] ?? 0) + ($byStatus[RequestStatus::Completed->value] ?? 0)),
                'revenue' => (float) Payment::where('status', PaymentStatus::Paid)->sum('amount'),
                'refunded' => (float) Payment::where('status', PaymentStatus::Refunded)->sum('amount'),
                'applicants' => User::where('role', 'applicant')->count(),
            ],
            'byStatus' => collect(RequestStatus::cases())->map(fn (RequestStatus $status) => [
                ...$status->toArray(),
                'total' => (int) ($byStatus[$status->value] ?? 0),
            ]),
            'byService' => Service::withCount('requests')->orderByDesc('requests_count')->get(['id', 'name'])
                ->map(fn (Service $s) => ['name' => $s->name, 'total' => $s->requests_count]),
            'needsAttention' => ServiceRequestResource::collection(
                ServiceRequest::with(['service', 'user'])
                    ->whereIn('status', [RequestStatus::Submitted, RequestStatus::UnderReview])
                    ->oldest('submitted_at')
                    ->limit(8)
                    ->get()
            )->resolve(),
        ]);
    }
}
