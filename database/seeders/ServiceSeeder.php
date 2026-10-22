<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->services() as $service) {
            Service::updateOrCreate(['slug' => $service['slug']], $service);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function services(): array
    {
        $emirates = $this->options(['abu_dhabi' => 'Abu Dhabi', 'dubai' => 'Dubai', 'sharjah' => 'Sharjah', 'ajman' => 'Ajman', 'umm_al_quwain' => 'Umm Al Quwain', 'ras_al_khaimah' => 'Ras Al Khaimah', 'fujairah' => 'Fujairah']);
        $nationalities = $this->options(['ae' => 'United Arab Emirates', 'in' => 'India', 'pk' => 'Pakistan', 'ph' => 'Philippines', 'eg' => 'Egypt', 'gb' => 'United Kingdom', 'us' => 'United States', 'other' => 'Other']);
        $residency = [
            'name' => 'residency_status', 'label' => 'Residency status', 'type' => 'radio', 'required' => true,
            'options' => $this->options(['national' => 'UAE National', 'resident' => 'UAE Resident (visa holder)', 'gcc' => 'GCC National']),
        ];
        $declaration = [
            'name' => 'declaration', 'label' => 'I confirm that the information and documents provided are true and accurate.',
            'type' => 'checkbox', 'required' => true,
        ];
        $image = ['jpg', 'jpeg', 'png'];
        $doc = ['pdf', 'jpg', 'jpeg', 'png'];
        $identityDocs = [
            ['key' => 'emirates_id_front', 'label' => 'Emirates ID (front)', 'required' => true, 'mimes' => $doc, 'max_kb' => 4096],
            ['key' => 'emirates_id_back', 'label' => 'Emirates ID (back)', 'required' => true, 'mimes' => $doc, 'max_kb' => 4096],
            ['key' => 'passport_copy', 'label' => 'Passport copy', 'description' => 'Bio-data page, valid for at least 6 months.', 'required' => true, 'mimes' => $doc, 'max_kb' => 4096],
            ['key' => 'visa_copy', 'label' => 'Residence visa copy', 'required' => true, 'mimes' => $doc, 'max_kb' => 4096, 'show_if' => ['field' => 'residency_status', 'value' => 'resident']],
        ];

        return [
            [
                'name' => 'Trade License Renewal',
                'slug' => 'trade-license-renewal',
                'category' => 'Business',
                'icon' => 'briefcase',
                'summary' => 'Renew your commercial, professional or industrial trade license for 1 to 3 years.',
                'description' => 'Renew an existing trade license before or within 30 days after expiry. You will need a valid tenancy contract (Ejari) for physical offices, and identity documents for the license owner.',
                'base_fee' => 1500,
                'vat_rate' => 5,
                'processing_days' => 3,
                'sort_order' => 1,
                'eligibility' => [
                    'License must be active or expired for less than 12 months.',
                    'No outstanding fines on the establishment.',
                    'A valid tenancy contract for physical offices.',
                ],
                'form_schema' => [
                    ['title' => 'License details', 'fields' => [
                        ['name' => 'trade_license_number', 'label' => 'Trade license number', 'type' => 'text', 'required' => true, 'placeholder' => 'e.g. 1234567', 'pattern' => '^[0-9]{6,8}$', 'help' => '6 to 8 digits, as printed on your current license.'],
                        ['name' => 'trade_name', 'label' => 'Trade name', 'type' => 'text', 'required' => true, 'placeholder' => 'e.g. Sample Trading LLC'],
                        ['name' => 'license_type', 'label' => 'License type', 'type' => 'select', 'required' => true, 'options' => $this->options(['commercial' => 'Commercial', 'professional' => 'Professional', 'industrial' => 'Industrial', 'tourism' => 'Tourism'])],
                        ['name' => 'legal_form', 'label' => 'Legal form', 'type' => 'select', 'required' => true, 'options' => $this->options(['llc' => 'Limited Liability Company (LLC)', 'sole' => 'Sole Establishment', 'civil' => 'Civil Company', 'branch' => 'Branch of Foreign Company'])],
                        ['name' => 'emirate', 'label' => 'Issuing emirate', 'type' => 'select', 'required' => true, 'options' => $emirates],
                        ['name' => 'license_expiry', 'label' => 'Current license expiry date', 'type' => 'date', 'required' => true],
                    ]],
                    ['title' => 'Renewal options', 'fields' => [
                        ['name' => 'renewal_years', 'label' => 'Renewal period', 'type' => 'select', 'required' => true, 'options' => [
                            ['value' => '1', 'label' => '1 year'],
                            ['value' => '2', 'label' => '2 years', 'fee' => 1350],
                            ['value' => '3', 'label' => '3 years', 'fee' => 2700],
                        ]],
                        ['name' => 'office_type', 'label' => 'Office type', 'type' => 'radio', 'required' => true, 'options' => $this->options(['physical' => 'Physical office (Ejari)', 'flexi' => 'Flexi-desk / business centre', 'home' => 'Home-based business'])],
                        ['name' => 'ejari_number', 'label' => 'Ejari / tenancy contract number', 'type' => 'text', 'required' => true, 'show_if' => ['field' => 'office_type', 'value' => 'physical']],
                        ['name' => 'processing_speed', 'label' => 'Processing speed', 'type' => 'radio', 'required' => true, 'options' => [
                            ['value' => 'standard', 'label' => 'Standard (3 working days)'],
                            ['value' => 'express', 'label' => 'Express (same day)', 'fee' => 250],
                        ]],
                        ['name' => 'add_ons', 'label' => 'Additional services', 'type' => 'checkbox', 'options' => [
                            ['value' => 'establishment_card', 'label' => 'Renew establishment card', 'fee' => 350],
                            ['value' => 'chamber', 'label' => 'Chamber of Commerce membership', 'fee' => 400],
                            ['value' => 'printed_copy', 'label' => 'Printed license copy by courier', 'fee' => 30],
                        ]],
                    ]],
                    ['title' => 'Owner & contact', 'fields' => [
                        ['name' => 'owner_name', 'label' => 'License owner full name', 'type' => 'text', 'required' => true],
                        ['name' => 'owner_nationality', 'label' => 'Owner nationality', 'type' => 'select', 'required' => true, 'options' => $nationalities],
                        $residency,
                        ['name' => 'contact_email', 'label' => 'Contact email', 'type' => 'email', 'required' => true],
                        ['name' => 'contact_phone', 'label' => 'Mobile number', 'type' => 'tel', 'required' => true, 'placeholder' => '05XXXXXXXX', 'pattern' => '^05[0-9]{8}$'],
                        ['name' => 'remarks', 'label' => 'Remarks', 'type' => 'textarea', 'placeholder' => 'Anything the reviewer should know (optional)'],
                        $declaration,
                    ]],
                ],
                'document_schema' => [
                    ['key' => 'current_license', 'label' => 'Current trade license', 'required' => true, 'mimes' => ['pdf'], 'max_kb' => 5120],
                    ...$identityDocs,
                    ['key' => 'tenancy_contract', 'label' => 'Tenancy contract (Ejari)', 'required' => true, 'mimes' => ['pdf'], 'max_kb' => 5120, 'show_if' => ['field' => 'office_type', 'value' => 'physical']],
                    ['key' => 'moa', 'label' => 'Memorandum of Association', 'description' => 'Required for LLCs and civil companies.', 'required' => true, 'mimes' => ['pdf'], 'max_kb' => 10240, 'show_if' => ['field' => 'legal_form', 'value' => ['llc', 'civil']]],
                    ['key' => 'other', 'label' => 'Other supporting document', 'required' => false, 'mimes' => $doc, 'max_kb' => 5120],
                ],
            ],
            [
                'name' => 'Driving License Renewal',
                'slug' => 'driving-license-renewal',
                'category' => 'Transport',
                'icon' => 'car',
                'summary' => 'Renew your UAE driving license online with an eye test and doorstep delivery.',
                'description' => 'Renew your driving license up to 6 months before expiry. A recent eye test from an approved optician is required. Residents must hold a valid residence visa.',
                'base_fee' => 300,
                'vat_rate' => 5,
                'processing_days' => 2,
                'sort_order' => 2,
                'eligibility' => [
                    'Valid Emirates ID.',
                    'Eye test passed within the last 3 months.',
                    'No outstanding traffic fines.',
                ],
                'form_schema' => [
                    ['title' => 'License details', 'fields' => [
                        ['name' => 'license_number', 'label' => 'Driving license number', 'type' => 'text', 'required' => true, 'placeholder' => 'e.g. 1234567'],
                        ['name' => 'traffic_file_number', 'label' => 'Traffic file number', 'type' => 'text', 'required' => true, 'pattern' => '^[0-9]{8}$', 'help' => '8 digits.'],
                        ['name' => 'emirate', 'label' => 'Licensing emirate', 'type' => 'select', 'required' => true, 'options' => $emirates],
                        ['name' => 'license_categories', 'label' => 'License categories', 'type' => 'checkbox', 'required' => true, 'options' => $this->options(['light' => 'Light vehicle', 'motorcycle' => 'Motorcycle', 'heavy' => 'Heavy vehicle', 'bus' => 'Bus'])],
                        ['name' => 'license_expiry', 'label' => 'Current expiry date', 'type' => 'date', 'required' => true],
                        ['name' => 'renewal_years', 'label' => 'Renewal period', 'type' => 'select', 'required' => true, 'options' => [
                            ['value' => '2', 'label' => '2 years'],
                            ['value' => '5', 'label' => '5 years', 'fee' => 200],
                            ['value' => '10', 'label' => '10 years (UAE nationals only)', 'fee' => 450],
                        ]],
                        $residency,
                        ['name' => 'nationality', 'label' => 'Nationality', 'type' => 'select', 'required' => true, 'options' => $nationalities],
                    ]],
                    ['title' => 'Medical', 'fields' => [
                        ['name' => 'eye_test_done', 'label' => 'Have you completed an eye test in the last 3 months?', 'type' => 'radio', 'required' => true, 'options' => $this->options(['yes' => 'Yes', 'no' => 'No, book one for me'])],
                        ['name' => 'eye_test_centre', 'label' => 'Eye test centre', 'type' => 'select', 'required' => true, 'show_if' => ['field' => 'eye_test_done', 'value' => 'yes'], 'options' => $this->options(['yateem' => 'Yateem Optician', 'magrabi' => 'Magrabi Optical', 'grand' => 'Grand Optics', 'other' => 'Other approved centre'])],
                        ['name' => 'eye_test_date', 'label' => 'Eye test date', 'type' => 'date', 'required' => true, 'date_rule' => 'before_or_equal:today', 'show_if' => ['field' => 'eye_test_done', 'value' => 'yes']],
                        ['name' => 'eye_test_booking', 'label' => 'Preferred eye test date', 'type' => 'date', 'required' => true, 'date_rule' => 'after:today', 'show_if' => ['field' => 'eye_test_done', 'value' => 'no']],
                        ['name' => 'eye_test_booking_fee', 'label' => 'Eye test booking', 'type' => 'radio', 'required' => true, 'show_if' => ['field' => 'eye_test_done', 'value' => 'no'], 'options' => [
                            ['value' => 'book', 'label' => 'Book eye test at partner centre', 'fee' => 140],
                        ]],
                        ['name' => 'has_medical_condition', 'label' => 'Do you have a medical condition that may affect driving?', 'type' => 'radio', 'required' => true, 'options' => $this->options(['no' => 'No', 'yes' => 'Yes'])],
                        ['name' => 'medical_details', 'label' => 'Please describe the condition', 'type' => 'textarea', 'required' => true, 'show_if' => ['field' => 'has_medical_condition', 'value' => 'yes']],
                    ]],
                    ['title' => 'Delivery', 'fields' => [
                        ['name' => 'delivery_method', 'label' => 'How would you like to receive your license?', 'type' => 'select', 'required' => true, 'options' => [
                            ['value' => 'collect', 'label' => 'Collect from service centre'],
                            ['value' => 'courier', 'label' => 'Courier (2-3 days)', 'fee' => 20],
                            ['value' => 'express', 'label' => 'Express courier (same day)', 'fee' => 35],
                        ]],
                        ['name' => 'delivery_emirate', 'label' => 'Delivery emirate', 'type' => 'select', 'required' => true, 'options' => $emirates, 'show_if' => ['field' => 'delivery_method', 'value' => ['courier', 'express']]],
                        ['name' => 'delivery_address', 'label' => 'Delivery address', 'type' => 'textarea', 'required' => true, 'placeholder' => 'Building, street, area', 'show_if' => ['field' => 'delivery_method', 'value' => ['courier', 'express']]],
                        ['name' => 'contact_phone', 'label' => 'Mobile number', 'type' => 'tel', 'required' => true, 'placeholder' => '05XXXXXXXX', 'pattern' => '^05[0-9]{8}$'],
                        $declaration,
                    ]],
                ],
                'document_schema' => [
                    ...$identityDocs,
                    ['key' => 'current_license', 'label' => 'Current driving license (both sides)', 'required' => true, 'mimes' => $doc, 'max_kb' => 4096],
                    ['key' => 'eye_test_certificate', 'label' => 'Eye test certificate', 'required' => true, 'mimes' => $doc, 'max_kb' => 4096, 'show_if' => ['field' => 'eye_test_done', 'value' => 'yes']],
                    ['key' => 'medical_report', 'label' => 'Medical fitness report', 'required' => true, 'mimes' => ['pdf'], 'max_kb' => 5120, 'show_if' => ['field' => 'has_medical_condition', 'value' => 'yes']],
                    ['key' => 'personal_photo', 'label' => 'Personal photo', 'description' => 'White background, passport style.', 'required' => true, 'mimes' => $image, 'max_kb' => 2048],
                ],
            ],
            [
                'name' => 'Good Conduct Certificate',
                'slug' => 'good-conduct-certificate',
                'category' => 'Police & Security',
                'icon' => 'shield-check',
                'summary' => 'Get a police clearance certificate for employment, immigration or study.',
                'description' => 'A certificate confirming you have no criminal record in the UAE. Issued electronically and valid for 3 months.',
                'base_fee' => 100,
                'vat_rate' => 5,
                'processing_days' => 1,
                'sort_order' => 3,
                'eligibility' => ['Valid Emirates ID or entry permit.'],
                'form_schema' => [
                    ['title' => 'Certificate details', 'fields' => [
                        ['name' => 'purpose', 'label' => 'Purpose', 'type' => 'select', 'required' => true, 'options' => $this->options(['employment' => 'Employment', 'immigration' => 'Immigration', 'study' => 'Study abroad', 'other' => 'Other'])],
                        ['name' => 'purpose_other', 'label' => 'Please specify', 'type' => 'text', 'required' => true, 'show_if' => ['field' => 'purpose', 'value' => 'other']],
                        ['name' => 'addressed_to', 'label' => 'Addressed to', 'type' => 'text', 'placeholder' => 'To whom it may concern'],
                        ['name' => 'language', 'label' => 'Certificate language', 'type' => 'radio', 'required' => true, 'options' => [
                            ['value' => 'ar', 'label' => 'Arabic'],
                            ['value' => 'en', 'label' => 'English'],
                            ['value' => 'both', 'label' => 'Arabic and English', 'fee' => 50],
                        ]],
                        ['name' => 'copies', 'label' => 'Number of printed copies', 'type' => 'number', 'required' => true, 'min' => 0, 'max' => 5],
                        $residency,
                        $declaration,
                    ]],
                ],
                'document_schema' => [
                    ...$identityDocs,
                    ['key' => 'personal_photo', 'label' => 'Personal photo', 'required' => true, 'mimes' => $image, 'max_kb' => 2048],
                ],
            ],
        ];
    }

    /**
     * @param  array<string, string>  $map
     * @return list<array{value: string, label: string}>
     */
    private function options(array $map): array
    {
        return array_map(fn ($value, $label) => ['value' => (string) $value, 'label' => $label], array_keys($map), $map);
    }
}
