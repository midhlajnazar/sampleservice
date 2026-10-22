<?php

namespace App\Http\Controllers;

use App\Http\Requests\PayServiceRequestRequest;
use App\Http\Resources\ServiceRequestResource;
use App\Models\ServiceRequest;
use App\Services\Payments\DummyPaymentGateway;
use App\Services\ServiceRequestManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The hosted "SamplePay" checkout page. In a real integration this would be a
 * redirect to the gateway and a signed callback; here the page lives in-app.
 */
class PaymentController extends Controller
{
    public function __construct(private readonly ServiceRequestManager $manager) {}

    public function checkout(ServiceRequest $serviceRequest): Response|RedirectResponse
    {
        Gate::authorize('update', $serviceRequest);

        if (! $serviceRequest->awaitingPayment()) {
            return to_route('requests.show', $serviceRequest);
        }

        $serviceRequest->load(['service', 'payments']);

        return Inertia::render('gateway/Checkout', [
            'serviceRequest' => ServiceRequestResource::make($serviceRequest)->resolve(),
            'testCards' => DummyPaymentGateway::testCards(),
        ]);
    }

    public function process(PayServiceRequestRequest $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        $payment = $this->manager->pay(
            $serviceRequest,
            $request->user(),
            $request->validated('card_number'),
            $request->validated('card_holder'),
        );

        if ($payment->failure_reason) {
            return back()->withErrors(['card_number' => "Payment declined: {$payment->failure_reason}"]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => "Payment of AED {$payment->amount} received. Your application is submitted."]);

        return to_route('requests.show', $serviceRequest);
    }
}
