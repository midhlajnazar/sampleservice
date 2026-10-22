<?php

namespace Database\Seeders;

use App\Enums\RequestStatus;
use App\Models\Service;
use App\Models\User;
use App\Services\ServiceRequestManager;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;

/**
 * A handful of applications in different workflow states so the admin panel
 * has something to show straight after seeding.
 */
class DemoRequestSeeder extends Seeder
{
    public function run(ServiceRequestManager $manager): void
    {
        Notification::fake();

        $applicant = User::where('email', 'test@example.com')->firstOrFail();
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $trade = Service::where('slug', 'trade-license-renewal')->firstOrFail();
        $driving = Service::where('slug', 'driving-license-renewal')->firstOrFail();

        $tradeAnswers = [
            'trade_license_number' => '1234567', 'trade_name' => 'Sample Trading LLC', 'license_type' => 'commercial',
            'legal_form' => 'llc', 'emirate' => 'dubai', 'license_expiry' => now()->addDays(10)->toDateString(),
            'renewal_years' => '2', 'office_type' => 'physical', 'ejari_number' => 'EJ-2026-55821',
            'processing_speed' => 'standard', 'add_ons' => ['establishment_card'], 'owner_name' => 'Test User',
            'owner_nationality' => 'in', 'residency_status' => 'resident', 'contact_email' => 'test@example.com',
            'contact_phone' => '0501234567', 'declaration' => true,
        ];
        $tradeDocs = $this->files(['current_license', 'emirates_id_front', 'emirates_id_back', 'passport_copy', 'visa_copy', 'tenancy_contract', 'moa']);

        $drivingAnswers = [
            'license_number' => '7654321', 'traffic_file_number' => '50123456', 'emirate' => 'abu_dhabi',
            'license_categories' => ['light'], 'license_expiry' => now()->addMonth()->toDateString(), 'renewal_years' => '5',
            'residency_status' => 'resident', 'nationality' => 'in', 'eye_test_done' => 'yes', 'eye_test_centre' => 'yateem',
            'eye_test_date' => now()->subWeek()->toDateString(), 'has_medical_condition' => 'no', 'delivery_method' => 'courier',
            'delivery_emirate' => 'abu_dhabi', 'delivery_address' => 'Villa 12, Street 4, Khalifa City', 'contact_phone' => '0501234567',
            'declaration' => true,
        ];
        $drivingDocs = $this->files(['emirates_id_front', 'emirates_id_back', 'passport_copy', 'visa_copy', 'current_license', 'eye_test_certificate', 'personal_photo']);

        // 1. Awaiting payment.
        $manager->create($applicant, $driving, $drivingAnswers, $drivingDocs);

        // 2. Paid and waiting in the admin queue.
        $submitted = $manager->create($applicant, $trade, $tradeAnswers, $tradeDocs);
        $manager->pay($submitted, $applicant, '4242424242424242', 'TEST USER');

        // 3. Admin asked for more information.
        $info = $manager->create($applicant, $driving, $drivingAnswers, $drivingDocs, 'api');
        $manager->pay($info, $applicant, '5555555555554444', 'TEST USER');
        $manager->transition($info, RequestStatus::UnderReview, $admin);
        $manager->transition($info, RequestStatus::InfoRequested, $admin, 'The eye test certificate is not legible. Please upload a clearer scan.');

        // 4. Approved and completed.
        $done = $manager->create($applicant, $trade, ['renewal_years' => '1', 'processing_speed' => 'express', 'add_ons' => []] + $tradeAnswers, $tradeDocs);
        $manager->pay($done, $applicant, '4242424242424242', 'TEST USER');
        $manager->transition($done, RequestStatus::UnderReview, $admin);
        $manager->transition($done, RequestStatus::Approved, $admin, 'All documents verified.');
        $manager->transition($done, RequestStatus::Completed, $admin, 'License issued and sent by email.');

        // 5. A failed payment followed by a successful one, then rejected (refunded).
        $rejected = $manager->create($applicant, $driving, $drivingAnswers, $drivingDocs);
        $manager->pay($rejected, $applicant, '4000000000000002', 'TEST USER');
        $manager->pay($rejected, $applicant, '4242424242424242', 'TEST USER');
        $manager->transition($rejected, RequestStatus::Rejected, $admin, 'Outstanding traffic fines must be settled before renewal.');
    }

    /**
     * @param  list<string>  $keys
     * @return array<string, UploadedFile>
     */
    private function files(array $keys): array
    {
        return collect($keys)->mapWithKeys(fn ($key) => [
            $key => $key === 'personal_photo'
                ? UploadedFile::fake()->image("{$key}.jpg", 300, 400)
                : UploadedFile::fake()->create("{$key}.pdf", 120, 'application/pdf'),
        ])->all();
    }
}
