<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\ServiceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Notification::fake();
        $this->seed(ServiceSeeder::class);
    }

    public function test_token_can_be_issued_and_used(): void
    {
        User::factory()->create(['email' => 'api@example.com']);

        $token = $this->postJson('/api/v1/auth/token', [
            'email' => 'api@example.com', 'password' => 'password', 'device_name' => 'phpunit',
        ])->assertCreated()->json('token');

        $this->withToken($token)->getJson('/api/v1/user')
            ->assertOk()
            ->assertJsonPath('data.email', 'api@example.com');

        $this->postJson('/api/v1/auth/token', [
            'email' => 'api@example.com', 'password' => 'wrong', 'device_name' => 'phpunit',
        ])->assertUnprocessable();
    }

    public function test_services_are_public_and_include_schema(): void
    {
        $this->getJson('/api/v1/services')->assertOk()->assertJsonCount(3, 'data');
        $this->getJson('/api/v1/services/good-conduct-certificate')
            ->assertOk()
            ->assertJsonStructure(['data' => ['form_schema', 'document_schema']]);
        $this->postJson('/api/v1/services/good-conduct-certificate/quote', ['fields' => ['language' => 'both']])
            ->assertOk()
            ->assertJsonPath('data.total', 157.5);
    }

    public function test_full_request_lifecycle_over_api(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['requests:read', 'requests:write', 'payments:write']);

        $reference = $this->post('/api/v1/requests', [
            'service' => 'good-conduct-certificate',
            'fields' => ['purpose' => 'employment', 'language' => 'en', 'copies' => 1, 'residency_status' => 'national', 'declaration' => 1],
            'documents' => [
                'emirates_id_front' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'),
                'emirates_id_back' => UploadedFile::fake()->create('b.pdf', 10, 'application/pdf'),
                'passport_copy' => UploadedFile::fake()->create('c.pdf', 10, 'application/pdf'),
                'personal_photo' => UploadedFile::fake()->image('p.jpg'),
            ],
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.status.value', 'pending_payment')
            ->assertJsonPath('data.channel', 'api')
            ->json('data.reference');

        $this->postJson("/api/v1/requests/{$reference}/pay", [
            'card_holder' => 'T', 'card_number' => '4000000000009995', 'expiry' => '12/40', 'cvv' => '123',
        ])->assertStatus(402)->assertJsonPath('data.failure_reason', 'Insufficient funds.');

        $this->postJson("/api/v1/requests/{$reference}/pay", [
            'card_holder' => 'T', 'card_number' => '4242424242424242', 'expiry' => '12/40', 'cvv' => '123',
        ])->assertCreated()->assertJsonPath('request_status.value', 'submitted');

        $this->getJson('/api/v1/requests')->assertOk()->assertJsonPath('data.0.reference', $reference);
    }

    public function test_abilities_are_enforced(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['requests:read']);

        $this->postJson('/api/v1/requests', ['service' => 'good-conduct-certificate'])->assertForbidden();
        $this->getJson('/api/v1/admin/requests')->assertForbidden();
    }

    public function test_admin_can_process_over_api(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Admin]), ['admin']);

        $this->getJson('/api/v1/admin/requests')->assertOk();
    }

    public function test_unauthenticated_requests_get_json_401(): void
    {
        $this->getJson('/api/v1/requests')->assertUnauthorized();
    }
}
