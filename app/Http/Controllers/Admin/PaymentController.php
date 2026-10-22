<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function index(Request $request): Response
    {
        $payments = Payment::query()
            ->with(['serviceRequest.service', 'serviceRequest.user'])
            ->when($request->string('status')->value(), fn ($q, $status) => $q->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/payments/Index', [
            'payments' => PaymentResource::collection($payments),
            'statuses' => array_map(fn (PaymentStatus $s) => ['value' => $s->value, 'label' => $s->label()], PaymentStatus::cases()),
            'filters' => ['status' => $request->query('status')],
        ]);
    }
}
