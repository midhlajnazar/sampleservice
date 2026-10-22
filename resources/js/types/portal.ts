export type Option = {
    value: string;
    label: string;
    fee?: number;
};

export type ShowIf = {
    field: string;
    value: string | string[];
};

export type FieldType =
    | 'text'
    | 'email'
    | 'tel'
    | 'number'
    | 'date'
    | 'textarea'
    | 'select'
    | 'radio'
    | 'checkbox';

export type FormField = {
    name: string;
    label: string;
    type: FieldType;
    required?: boolean;
    options?: Option[];
    placeholder?: string;
    help?: string;
    min?: number;
    max?: number;
    pattern?: string;
    show_if?: ShowIf;
};

export type FormSection = {
    title: string;
    fields: FormField[];
};

export type DocumentRequirement = {
    key: string;
    label: string;
    description?: string;
    required: boolean;
    mimes: string[];
    max_kb: number;
    show_if?: ShowIf;
};

export type Service = {
    id: number;
    name: string;
    slug: string;
    category: string;
    icon: string;
    summary: string;
    description: string | null;
    base_fee: number;
    vat_rate: number;
    currency: string;
    processing_days: number;
    is_active: boolean;
    eligibility: string[];
    form_schema?: FormSection[];
    document_schema?: DocumentRequirement[];
    requests_count?: number;
};

export type StatusValue =
    | 'pending_payment'
    | 'submitted'
    | 'under_review'
    | 'info_requested'
    | 'approved'
    | 'rejected'
    | 'completed'
    | 'cancelled';

export type Status = {
    value: StatusValue;
    label: string;
};

export type RequestDocument = {
    id: number;
    key: string;
    label: string;
    original_name: string;
    mime_type: string;
    size: number;
    status: 'pending' | 'accepted' | 'rejected' | 'superseded';
    review_note: string | null;
    url: string;
    uploaded_at: string;
};

export type Payment = {
    id: number;
    transaction_reference: string;
    amount: number;
    currency: string;
    status: 'pending' | 'paid' | 'failed' | 'refunded';
    gateway: string;
    card_brand: string | null;
    card_last4: string | null;
    failure_reason: string | null;
    authorization_code: string | null;
    paid_at: string | null;
    refunded_at: string | null;
    created_at: string;
    service_request?: {
        reference: string;
        service: string;
        applicant: string;
    };
};

export type Activity = {
    id: number;
    action: string;
    from_status: Status | null;
    to_status: Status | null;
    comment: string | null;
    is_internal: boolean;
    actor: string;
    actor_is_admin: boolean;
    created_at: string;
};

export type FeeBreakdown = {
    breakdown: { label: string; amount: number }[];
    subtotal: number;
    vat: number;
    total: number;
    currency: string;
};

export type ServiceRequest = {
    id: number;
    reference: string;
    status: Status;
    channel: string;
    service?: Service;
    applicant?: {
        id: number;
        name: string;
        email: string;
        phone: string | null;
        emirates_id: string | null;
    };
    assignee?: { id: number; name: string } | null;
    form_data: Record<string, unknown>;
    answers?: { section: string; label: string; value: string }[];
    fees: FeeBreakdown;
    decision_note: string | null;
    certificate_number: string | null;
    valid_until: string | null;
    documents?: RequestDocument[];
    payments?: Payment[];
    activities?: Activity[];
    submitted_at: string | null;
    decided_at: string | null;
    created_at: string;
    updated_at: string;
};

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type Paginated<T> = {
    data: T[];
    links: {
        first: string;
        last: string;
        prev: string | null;
        next: string | null;
    };
    meta: {
        current_page: number;
        last_page: number;
        from: number | null;
        to: number | null;
        total: number;
        per_page: number;
        links: PaginationLink[];
    };
};
