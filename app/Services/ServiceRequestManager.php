<?php

namespace App\Services;

use App\Enums\DocumentStatus;
use App\Enums\PaymentStatus;
use App\Enums\RequestStatus;
use App\Models\Payment;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestDocument;
use App\Models\User;
use App\Notifications\ServiceRequestUpdated;
use App\Services\Payments\DummyPaymentGateway;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * The single place where a service request changes state. Web controllers and
 * API controllers both call into this, so the workflow rules live here once.
 */
class ServiceRequestManager
{
    public function __construct(
        private readonly FeeCalculator $fees,
        private readonly ServiceFormValidator $validator,
        private readonly DummyPaymentGateway $gateway,
    ) {}

    /**
     * Create an application in "pending payment" (or "submitted" if free).
     *
     * @param  array<string, mixed>  $answers
     * @param  array<string, UploadedFile>  $documents
     */
    public function create(User $user, Service $service, array $answers, array $documents, string $channel = 'web'): ServiceRequest
    {
        $answers = $this->normalizeAnswers($service, $answers);
        $fee = $this->fees->calculate($service, $answers);

        $request = DB::transaction(function () use ($user, $service, $answers, $documents, $channel, $fee) {
            $request = ServiceRequest::create([
                'reference' => $this->newReference(),
                'user_id' => $user->id,
                'service_id' => $service->id,
                'status' => RequestStatus::PendingPayment,
                'form_data' => $answers,
                'fee_breakdown' => $fee['breakdown'],
                'subtotal' => $fee['subtotal'],
                'vat_amount' => $fee['vat'],
                'total_amount' => $fee['total'],
                'channel' => $channel,
            ]);

            $this->storeDocuments($request, $documents);
            $this->log($request, $user, 'created', null, RequestStatus::PendingPayment, "Application created via {$channel}.");

            return $request;
        });

        if ($fee['total'] <= 0) {
            $this->markSubmitted($request, $user, 'No fee due; application submitted.');
        }

        return $request->fresh() ?? $request;
    }

    /**
     * Charge the applicant's card through the dummy gateway.
     */
    public function pay(ServiceRequest $request, User $payer, string $cardNumber, string $cardHolder): Payment
    {
        if (! $request->awaitingPayment()) {
            throw ValidationException::withMessages(['card_number' => 'This request is not awaiting payment.']);
        }

        $payment = Payment::create([
            'transaction_reference' => 'TXN-'.now()->format('ymd').'-'.strtoupper(Str::random(8)),
            'service_request_id' => $request->id,
            'user_id' => $payer->id,
            'amount' => $request->total_amount,
            'currency' => 'AED',
            'status' => PaymentStatus::Pending,
            'method' => 'card',
        ]);

        $result = $this->gateway->charge($cardNumber, (float) $request->total_amount, 'AED', $payment->transaction_reference);

        $payment->update([
            'status' => $result['approved'] ? PaymentStatus::Paid : PaymentStatus::Failed,
            'card_brand' => $result['brand'],
            'card_last4' => $result['last4'],
            'failure_reason' => $result['approved'] ? null : $result['message'],
            'gateway_response' => $result['raw'] + ['card_holder' => $cardHolder],
            'paid_at' => $result['approved'] ? now() : null,
        ]);

        if ($result['approved']) {
            $this->markSubmitted($request, $payer, "Payment {$payment->transaction_reference} of AED {$payment->amount} received.");
        } else {
            $this->log($request, $payer, 'payment_failed', null, null, "Payment failed: {$result['message']}");
        }

        return $payment;
    }

    /**
     * Admin moves a request along the workflow.
     */
    public function transition(ServiceRequest $request, RequestStatus $to, User $admin, ?string $comment = null): ServiceRequest
    {
        $from = $request->status;

        if (! $from->canTransitionTo($to)) {
            throw ValidationException::withMessages(['status' => "Cannot move a request from {$from->label()} to {$to->label()}."]);
        }

        if (in_array($to, [RequestStatus::InfoRequested, RequestStatus::Rejected], true) && blank($comment)) {
            throw ValidationException::withMessages(['comment' => 'Please tell the applicant why.']);
        }

        DB::transaction(function () use ($request, $to, $admin, $comment, $from) {
            $changes = ['status' => $to, 'assigned_to' => $request->assigned_to ?? $admin->id];

            if ($to === RequestStatus::Approved) {
                $changes += [
                    'certificate_number' => $this->newCertificateNumber($request),
                    'valid_until' => now()->addYears((int) ($request->form_data['renewal_years'] ?? 1))->toDateString(),
                    'decided_at' => now(),
                    'decision_note' => $comment,
                ];
            }

            if ($to === RequestStatus::Rejected) {
                $changes += ['decided_at' => now(), 'decision_note' => $comment];
                $this->refund($request);
            }

            $request->update($changes);
            $this->log($request, $admin, 'status_changed', $from, $to, $comment);
        });

        $request->user->notify(new ServiceRequestUpdated($request, $comment));

        return $request;
    }

    /**
     * Applicant answers an "information requested" by commenting and/or re-uploading documents.
     *
     * @param  array<string, UploadedFile>  $documents
     */
    public function respond(ServiceRequest $request, User $applicant, ?string $comment, array $documents): ServiceRequest
    {
        if ($request->status !== RequestStatus::InfoRequested) {
            throw ValidationException::withMessages(['comment' => 'This request is not waiting for more information.']);
        }

        if (blank($comment) && $documents === []) {
            throw ValidationException::withMessages(['comment' => 'Add a comment or upload at least one document.']);
        }

        DB::transaction(function () use ($request, $applicant, $comment, $documents) {
            $this->storeDocuments($request, $documents);
            $request->update(['status' => RequestStatus::UnderReview]);

            $uploaded = $documents ? ' Uploaded: '.implode(', ', array_keys($documents)).'.' : '';
            $this->log($request, $applicant, 'applicant_responded', RequestStatus::InfoRequested, RequestStatus::UnderReview, trim(($comment ?? '').$uploaded));
        });

        return $request;
    }

    public function cancel(ServiceRequest $request, User $user): ServiceRequest
    {
        if (! $request->awaitingPayment()) {
            throw ValidationException::withMessages(['status' => 'Only unpaid requests can be cancelled.']);
        }

        $request->update(['status' => RequestStatus::Cancelled]);
        $this->log($request, $user, 'cancelled', RequestStatus::PendingPayment, RequestStatus::Cancelled, 'Cancelled by applicant.');

        return $request;
    }

    public function addNote(ServiceRequest $request, User $admin, string $note): void
    {
        $this->log($request, $admin, 'internal_note', null, null, $note, internal: true);
    }

    public function reviewDocument(ServiceRequestDocument $document, DocumentStatus $status, User $admin, ?string $note = null): void
    {
        $document->update(['status' => $status, 'review_note' => $note]);

        $this->log($document->serviceRequest, $admin, 'document_reviewed', null, null,
            "{$document->label} marked {$status->value}".($note ? ": {$note}" : '.'), internal: true);
    }

    /**
     * Keep only answers for fields that exist and are visible, with booleans cast.
     *
     * @param  array<string, mixed>  $answers
     * @return array<string, mixed>
     */
    private function normalizeAnswers(Service $service, array $answers): array
    {
        $clean = [];

        foreach ($service->fields() as $name => $field) {
            if (! $this->validator->isVisible($field, $answers) || ! array_key_exists($name, $answers)) {
                continue;
            }

            $clean[$name] = $field['type'] === 'checkbox' && empty($field['options'])
                ? filter_var($answers[$name], FILTER_VALIDATE_BOOLEAN)
                : $answers[$name];
        }

        return $clean;
    }

    /**
     * @param  array<string, UploadedFile>  $documents
     */
    private function storeDocuments(ServiceRequest $request, array $documents): void
    {
        $schema = collect($request->service->document_schema)->keyBy('key');

        foreach ($documents as $key => $file) {
            if (! $schema->has($key)) {
                continue;
            }

            $request->documents()->where('key', $key)->update(['status' => DocumentStatus::Superseded]);

            $request->documents()->create([
                'key' => $key,
                'label' => $schema[$key]['label'],
                'original_name' => $file->getClientOriginalName(),
                'path' => $file->store("service-requests/{$request->reference}", 'local'),
                'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
                'size' => $file->getSize(),
                'status' => DocumentStatus::Pending,
            ]);
        }
    }

    private function markSubmitted(ServiceRequest $request, User $user, string $comment): void
    {
        $request->update(['status' => RequestStatus::Submitted, 'submitted_at' => now()]);
        $this->log($request, $user, 'submitted', RequestStatus::PendingPayment, RequestStatus::Submitted, $comment);
        $request->user->notify(new ServiceRequestUpdated($request, $comment));
    }

    private function refund(ServiceRequest $request): void
    {
        $request->payments()->where('status', PaymentStatus::Paid)->update([
            'status' => PaymentStatus::Refunded,
            'refunded_at' => now(),
        ]);
    }

    private function log(ServiceRequest $request, ?User $user, string $action, ?RequestStatus $from, ?RequestStatus $to, ?string $comment, bool $internal = false): void
    {
        $request->activities()->create([
            'user_id' => $user?->id,
            'action' => $action,
            'from_status' => $from?->value,
            'to_status' => $to?->value,
            'comment' => $comment,
            'is_internal' => $internal,
        ]);
    }

    private function newReference(): string
    {
        do {
            $reference = 'SR-'.now()->format('Ymd').'-'.strtoupper(Str::random(5));
        } while (ServiceRequest::where('reference', $reference)->exists());

        return $reference;
    }

    private function newCertificateNumber(ServiceRequest $request): string
    {
        $prefix = Str::of($request->service->name)->explode(' ')->map(fn ($w) => Str::upper($w[0] ?? ''))->implode('');

        return $prefix.'-'.now()->format('Y').'-'.str_pad((string) $request->id, 6, '0', STR_PAD_LEFT);
    }
}
