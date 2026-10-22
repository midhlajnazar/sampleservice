<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Enums\RequestStatus;
use App\Enums\UserRole;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\User;
use Database\Seeders\ServiceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ServiceRequestWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Notification::fake();
        $this->seed(ServiceSeeder::class);
    }

    /**
     * @return array<string, mixed>
     */
    private function drivingPayload(array $overrides = []): array
    {
        return array_replace_recursive([
            'fields' => [
                'license_number' => '7654321', 'traffic_file_number' => '50123456', 'emirate' => 'dubai',
                'license_categories' => ['light'], 'license_expiry' => now()->addMonth()->toDateString(),
                'renewal_years' => '5', 'residency_status' => 'resident', 'nationality' => 'in',
                'eye_test_done' => 'yes', 'eye_test_centre' => 'yateem', 'eye_test_date' => now()->subDay()->toDateString(),
                'has_medical_condition' => 'no', 'delivery_method' => 'courier', 'delivery_emirate' => 'dubai',
                'delivery_address' => 'Villa 1', 'contact_phone' => '0501234567', 'declaration' => '1',
            ],
            'documents' => [
                'emirates_id_front' => UploadedFile::fake()->create('eid-front.pdf', 50, 'application/pdf'),
                'emirates_id_back' => UploadedFile::fake()->create('eid-back.pdf', 50, 'application/pdf'),
                'passport_copy' => UploadedFile::fake()->create('passport.pdf', 50, 'application/pdf'),
                'visa_copy' => UploadedFile::fake()->create('visa.pdf', 50, 'application/pdf'),
                'current_license' => UploadedFile::fake()->create('license.pdf', 50, 'application/pdf'),
                'eye_test_certificate' => UploadedFile::fake()->create('eye.pdf', 50, 'application/pdf'),
                'personal_photo' => UploadedFile::fake()->image('photo.jpg'),
            ],
        ], $overrides);
    }

    public function test_applicant_can_apply_pay_and_admin_can_approve(): void
    {
        $applicant = User::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($applicant)
            ->post(route('requests.store', 'driving-license-renewal'), $this->drivingPayload())
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $request = ServiceRequest::firstOrFail();
        $this->assertSame(RequestStatus::PendingPayment, $request->status);
        $this->assertCount(7, $request->documents);
        // 300 base + 200 (5 years) + 20 courier = 520, + 5% VAT
        $this->assertEquals(546.00, (float) $request->total_amount);

        $this->post(route('payments.process', $request), [
            'card_holder' => 'Test', 'card_number' => '4000 0000 0000 0002', 'expiry' => '12/40', 'cvv' => '123',
        ])->assertSessionHasErrors('card_number');
        $this->assertSame(RequestStatus::PendingPayment, $request->fresh()->status);

        $this->post(route('payments.process', $request), [
            'card_holder' => 'Test', 'card_number' => '4242 4242 4242 4242', 'expiry' => '12/40', 'cvv' => '123',
        ])->assertRedirect(route('requests.show', $request));
        $this->assertSame(RequestStatus::Submitted, $request->fresh()->status);

        $this->actingAs($admin)
            ->post(route('admin.requests.transition', $request), ['status' => 'under_review'])
            ->assertSessionHasNoErrors();
        $this->post(route('admin.requests.transition', $request), ['status' => 'approved', 'comment' => 'OK'])
            ->assertSessionHasNoErrors();

        $request->refresh();
        $this->assertSame(RequestStatus::Approved, $request->status);
        $this->assertNotNull($request->certificate_number);
        $this->assertTrue($request->valid_until->isSameDay(now()->addYears(5)));
    }

    public function test_conditional_documents_are_required_only_when_visible(): void
    {
        $payload = $this->drivingPayload(['fields' => ['residency_status' => 'national']]);
        unset($payload['documents']['visa_copy']);

        $this->actingAs(User::factory()->create())
            ->post(route('requests.store', 'driving-license-renewal'), $payload)
            ->assertSessionHasNoErrors();

        $payload = $this->drivingPayload();
        unset($payload['documents']['visa_copy']);

        $this->post(route('requests.store', 'driving-license-renewal'), $payload)
            ->assertSessionHasErrors('documents.visa_copy');
    }

    public function test_info_request_and_applicant_response(): void
    {
        $applicant = User::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->actingAs($applicant)->post(route('requests.store', 'driving-license-renewal'), $this->drivingPayload());
        $request = ServiceRequest::firstOrFail();
        $this->post(route('payments.process', $request), ['card_holder' => 'T', 'card_number' => '4242424242424242', 'expiry' => '12/40', 'cvv' => '123']);

        $this->actingAs($admin)
            ->post(route('admin.requests.transition', $request), ['status' => 'info_requested'])
            ->assertSessionHasErrors('comment');
        $this->post(route('admin.requests.transition', $request), ['status' => 'info_requested', 'comment' => 'Upload a clearer visa'])
            ->assertSessionHasNoErrors();

        $this->actingAs($applicant)
            ->post(route('requests.respond', $request), [
                'comment' => 'Here it is',
                'documents' => ['visa_copy' => UploadedFile::fake()->create('visa2.pdf', 40, 'application/pdf')],
            ])
            ->assertSessionHasNoErrors();

        $request->refresh();
        $this->assertSame(RequestStatus::UnderReview, $request->status);
        $this->assertSame(1, $request->documents()->where('key', 'visa_copy')->where('status', 'superseded')->count());
    }

    public function test_rejection_refunds_payment(): void
    {
        $applicant = User::factory()->create();
        $this->actingAs($applicant)->post(route('requests.store', 'driving-license-renewal'), $this->drivingPayload());
        $request = ServiceRequest::firstOrFail();
        $this->post(route('payments.process', $request), ['card_holder' => 'T', 'card_number' => '4242424242424242', 'expiry' => '12/40', 'cvv' => '123']);

        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]))
            ->post(route('admin.requests.transition', $request), ['status' => 'rejected', 'comment' => 'Fines outstanding']);

        $this->assertSame(PaymentStatus::Refunded, $request->payments()->first()->status);
    }

    public function test_applicants_cannot_see_others_requests_or_the_admin_panel(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($owner)->post(route('requests.store', 'driving-license-renewal'), $this->drivingPayload());
        $request = ServiceRequest::firstOrFail();

        $other = User::factory()->create();
        $this->actingAs($other)->get(route('requests.show', $request))->assertForbidden();
        $this->actingAs($other)->get(route('documents.show', $request->documents()->first()))->assertForbidden();
        $this->actingAs($other)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_pages_render(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->actingAs($admin)->post(route('requests.store', 'driving-license-renewal'), $this->drivingPayload());
        $request = ServiceRequest::firstOrFail();
        $service = Service::firstOrFail();

        foreach ([
            route('home'), route('dashboard'), route('services.index'), route('services.show', $service),
            route('requests.create', $service), route('requests.index'), route('requests.show', $request),
            route('payments.checkout', $request), route('api-tokens.index'),
            route('admin.dashboard'), route('admin.requests.index'), route('admin.requests.show', $request),
            route('admin.services.index'), route('admin.services.edit', $service), route('admin.payments.index'),
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }
}
